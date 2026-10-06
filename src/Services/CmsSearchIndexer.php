<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\CmsUrl;

/**
 * Write path of the public search index (cms_searchables). Called by
 * CmsFallbackService (routed content) and the item observers — so the index
 * always matches the registered fallback routes.
 */
class CmsSearchIndexer
{
    /** @var array<class-string<Model>, string> */
    public const TYPES = [
        CmsPage::class => 'page',
        CmsArticle::class => 'article',
        CmsCategory::class => 'category',
        CmsAlbum::class => 'album',
        CmsDownload::class => 'download',
        CmsAlbumImage::class => 'album_item',
        CmsDownloadFile::class => 'download_file',
    ];

    /** Routed type => the item type that lives under it. */
    private const ITEM_TYPES = ['album' => 'album_item', 'download' => 'download_file'];

    private bool $enabled = false;

    public function isEnabled(): bool
    {
        // Only a positive result is memoized: the table may be migrated mid-process.
        return $this->enabled = $this->enabled || Schema::hasTable('cms_searchables');
    }

    /**
     * (Re)index routed content at $path: one row per enabled locale, then its items.
     */
    public function index(CmsPage|CmsArticle|CmsCategory|CmsAlbum|CmsDownload $model, string $path): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        // Shared index: only content every visitor may see.
        if (! app(CmsAccessService::class)->isPublic($model)) {
            $this->forget($model);

            return;
        }

        $type = self::TYPES[$model::class];
        $bodyField = $model instanceof CmsPage || $model instanceof CmsArticle ? 'body' : 'description';
        $hasExcerpt = $model instanceof CmsPage || $model instanceof CmsArticle;

        foreach (CmsLocale::all() as $locale) {
            $this->upsert($type, (int) $model->getKey(), $locale, [
                'title' => Str::limit((string) $model->translate('title', $locale), 250),
                'excerpt' => $hasExcerpt ? $model->translate('excerpt', $locale) : null,
                'content' => trim(self::plainText($model->translate($bodyField, $locale)).' '.app(CmsCustomFieldService::class)->searchText($model, $locale)),
                'url' => CmsUrl::localePath($path, $locale),
                'image' => $model->cover_image ?: null,
                'parent_label' => null,
                'parent_id' => null,
                'published_at' => $model instanceof CmsPage || $model instanceof CmsArticle ? $model->published_at : null,
                'expires_at' => $model instanceof CmsArticle ? $model->expires_at : null,
            ]);
        }

        if ($model instanceof CmsAlbum) {
            $model->images()->with('file')->get()->each(fn (CmsAlbumImage $image) => $this->indexItem($image));
        } elseif ($model instanceof CmsDownload) {
            $model->files()->with('file')->get()->each(fn (CmsDownloadFile $file) => $this->indexItem($file));
        }
    }

    /**
     * Index an album image / download file under its parent's rows (one per
     * locale). Not indexed when the parent isn't, or when it has no text.
     */
    public function indexItem(CmsAlbumImage|CmsDownloadFile $item): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        [$parentType, $parentId, $type, $textField, $anchor] = $item instanceof CmsAlbumImage
            ? ['album', $item->album_id, 'album_item', 'caption', 'item']
            : ['download', $item->download_id, 'download_file', 'description', 'file'];

        $parents = CmsSearchable::query()
            ->where('searchable_type', $parentType)->where('searchable_id', $parentId)
            ->get()->keyBy('locale');

        if ($parents->isEmpty()) {
            $this->forgetItem($item);

            return;
        }

        foreach ($parents as $locale => $parent) {
            $title = $item->translate('title', (string) $locale);
            $text = $item->translate($textField, (string) $locale);
            $headline = filled($title) ? $title : $text;

            if (blank($headline)) {
                $this->deleteWhere(fn (Builder $q) => $q->where('searchable_type', $type)
                    ->where('searchable_id', $item->id)->where('locale', $locale));

                continue;
            }

            $this->upsert($type, $item->id, (string) $locale, [
                'title' => Str::limit((string) $headline, 250),
                'excerpt' => filled($title) ? $text : null,
                'content' => trim($text.' '.$parent->title.' '.app(CmsCustomFieldService::class)->searchText($item, (string) $locale)),
                'url' => $parent->url.'#'.$anchor.'-'.$item->id,
                'image' => $item->file?->isImage() ? ($item->file->getThumbnailUrl() ?: $item->file->getUrl()) : null,
                'parent_label' => $parent->title,
                'parent_id' => $parentId,
                'published_at' => null,
                'expires_at' => null,
            ]);
        }
    }

    /**
     * Remove a routed model's rows and the rows of its items.
     */
    public function forget(Model $model): void
    {
        if (! $this->isEnabled() || ! isset(self::TYPES[$model::class])) {
            return;
        }

        $type = self::TYPES[$model::class];
        $this->deleteWhere(fn (Builder $q) => $q->where('searchable_type', $type)->where('searchable_id', $model->getKey()));

        if (isset(self::ITEM_TYPES[$type])) {
            $this->deleteWhere(fn (Builder $q) => $q->where('searchable_type', self::ITEM_TYPES[$type])->where('parent_id', $model->getKey()));
        }
    }

    public function forgetItem(CmsAlbumImage|CmsDownloadFile $item): void
    {
        if ($this->isEnabled()) {
            $type = self::TYPES[$item::class];
            $this->deleteWhere(fn (Builder $q) => $q->where('searchable_type', $type)->where('searchable_id', $item->id));
        }
    }

    /**
     * Delete rows whose source row is gone (DB-level cascades fire no events).
     */
    public function pruneOrphans(): void
    {
        if (! $this->isEnabled()) {
            return;
        }

        foreach (self::TYPES as $class => $type) {
            $source = new $class;
            $this->deleteWhere(fn (Builder $q) => $q->where('searchable_type', $type)
                ->whereNotIn('searchable_id', $source->newQuery()->select($source->getKeyName())));
        }
    }

    /**
     * Remove every row of the given routed types and their item types.
     *
     * @param  list<string>  $types
     */
    public function clear(array $types): void
    {
        $withItems = [...$types, ...array_values(array_intersect_key(self::ITEM_TYPES, array_flip($types)))];
        $this->deleteWhere(fn (Builder $q) => $q->whereIn('searchable_type', $withItems));
    }

    /**
     * HTML → single-spaced plain text for matching and snippets.
     */
    public static function plainText(?string $html): string
    {
        $text = html_entity_decode(strip_tags(CmsMediaGalleryRenderer::strip((string) $html)), ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', str_replace("\u{A0}", ' ', $text)));
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function upsert(string $type, int $id, string $locale, array $values): void
    {
        CmsSearchable::modelClass()::updateOrCreate(
            ['searchable_type' => $type, 'searchable_id' => $id, 'locale' => $locale],
            $values,
        );
    }

    /**
     * Bulk delete on the database engine; per model under Scout so its events
     * remove the records from the engine too.
     *
     * @param  Closure(Builder<CmsSearchable>): mixed  $constraint
     */
    private function deleteWhere(Closure $constraint): void
    {
        $query = CmsSearchable::modelClass()::query();
        $constraint($query);

        CmsSearchable::usesScout() ? $query->get()->each->delete() : $query->delete();
    }
}
