<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsTranslation;
use VmEngine\Cms\Support\CmsLocale;

/**
 * Inline media galleries (and their images) of pages, articles and
 * download files. Every lookup is scoped to the owner, so ids from the
 * browser can never reach another item's galleries.
 */
class CmsMediaGalleryService
{
    /** Models that can own galleries (they use HasMediaGalleries). */
    public const OWNER_TYPES = [CmsPage::class, CmsArticle::class, CmsDownloadFile::class];

    public function __construct(private readonly CmsActivityLogger $logger) {}

    public function findOwnerOrFail(string $type, int $id): CmsPage|CmsArticle|CmsDownloadFile
    {
        return match ($type) {
            CmsPage::class => CmsPage::findOrFail($id),
            CmsArticle::class => CmsArticle::findOrFail($id),
            CmsDownloadFile::class => CmsDownloadFile::findOrFail($id),
            default => abort(404),
        };
    }

    /**
     * @return Collection<int, CmsMediaGallery>
     */
    public function galleriesOf(CmsPage|CmsArticle|CmsDownloadFile $owner): Collection
    {
        return $owner->mediaGalleries()->withCount('images')->get();
    }

    public function findInOwnerOrFail(CmsPage|CmsArticle|CmsDownloadFile $owner, int $id): CmsMediaGallery
    {
        return $owner->mediaGalleries()->findOrFail($id);
    }

    /**
     * @param  array{title: string, key: string, layout: string}  $data
     */
    public function create(CmsPage|CmsArticle|CmsDownloadFile $owner, array $data): CmsMediaGallery
    {
        $gallery = $owner->mediaGalleries()->create([
            ...$data,
            'sort_order' => ((int) $owner->mediaGalleries()->max('sort_order')) + 1,
        ]);
        $this->logger->logCreate('media_gallery', $gallery, $data);

        return $gallery;
    }

    /**
     * @param  array{title: string, key: string, layout: string}  $data
     */
    public function update(CmsMediaGallery $gallery, array $data): void
    {
        $oldData = $gallery->toArray();
        $gallery->update($data);
        $this->logger->logUpdate('media_gallery', $gallery, $oldData);
    }

    public function delete(CmsMediaGallery $gallery): void
    {
        $this->logger->logDelete('media_gallery', $gallery);
        $gallery->delete();
    }

    public function addImage(CmsMediaGallery $gallery, int $fileId): CmsMediaGalleryImage
    {
        $image = $gallery->images()->create([
            'file_id' => $fileId,
            'sort_order' => ((int) $gallery->images()->max('sort_order')) + 1,
        ]);
        $this->logger->logCreate('media_gallery_image', $image, ['gallery_id' => $gallery->id, 'file_id' => $fileId]);

        return $image;
    }

    public function removeImage(CmsMediaGallery $gallery, int $imageId): void
    {
        $image = $gallery->images()->findOrFail($imageId);
        $this->logger->logDelete('media_gallery_image', $image);
        $image->delete();
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function reorderImages(CmsMediaGallery $gallery, array $ids): void
    {
        $ids = array_map('intval', array_values($ids));

        DB::transaction(function () use ($gallery, $ids): void {
            foreach ($ids as $order => $id) {
                $gallery->images()->whereKey($id)->update(['sort_order' => $order]);
            }
        });

        $this->logger->logReorder('media_gallery', 'images', $gallery, $ids);
    }

    /**
     * Galleries whose owner no longer exists — left behind when articles or
     * download files go by FK cascade (section/download delete), which fires
     * no model events — plus rows of an unknown owner type.
     *
     * @return Collection<int, CmsMediaGallery>
     */
    public function orphans(): Collection
    {
        return CmsMediaGallery::query()
            ->where(function ($query): void {
                $query->whereNotIn('owner_type', self::OWNER_TYPES);

                foreach (self::OWNER_TYPES as $type) {
                    $owner = new $type;
                    $query->orWhere(fn ($q) => $q
                        ->where('owner_type', $type)
                        ->whereNotIn('owner_id', $owner->newQueryWithoutScopes()->select($owner->getKeyName())));
                }
            })
            ->get();
    }

    /** Delete orphaned galleries (images go by FK cascade) and their caption translations. */
    public function pruneOrphans(): int
    {
        $ids = $this->orphans()->modelKeys();

        if ($ids !== []) {
            CmsMediaGallery::whereKey($ids)->delete();
            CmsTranslation::pruneOrphans();
        }

        return count($ids);
    }

    /**
     * Save captions for the gallery's images: primary locale to the base
     * column, other enabled locales to translations. Unknown ids are ignored.
     *
     * @param  array<int|string, array<string, string|null>>  $captions  image id => locale => caption
     */
    public function saveCaptions(CmsMediaGallery $gallery, array $captions): void
    {
        $primary = CmsLocale::primary();

        DB::transaction(function () use ($gallery, $captions, $primary): void {
            foreach ($gallery->images()->get() as $image) {
                $byLocale = $captions[$image->id] ?? [];
                $image->update(['caption' => trim((string) ($byLocale[$primary] ?? '')) ?: null]);

                $translations = [];
                foreach (CmsLocale::secondary() as $locale) {
                    $translations[$locale] = ['caption' => (string) ($byLocale[$locale] ?? '')];
                }
                $image->setTranslations($translations);
            }
        });
    }
}
