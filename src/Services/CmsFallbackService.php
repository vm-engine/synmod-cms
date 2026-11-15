<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Database\Eloquent\Collection;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Support\CmsUrl;
use VmEngine\SynFallback\Facades\Fallback;

class CmsFallbackService
{
    public function __construct(private readonly CmsSearchIndexer $search) {}

    public function syncPage(CmsPage $page): void
    {
        if ($page->status !== 'published') {
            $this->forgetPage($page);

            return;
        }

        $path = (string) CmsUrl::path($page);

        Fallback::register(
            slug: $path,
            handler: 'cms::frontend.page-viewer',
            module: 'cms',
            params: ['page_id' => $page->id],
            handlerType: 'livewire',
        );

        $this->search->index($page, $path);
    }

    public function forgetPage(CmsPage $page): void
    {
        Fallback::forget((string) CmsUrl::path($page));

        $this->search->forget($page);
    }

    public function syncBuilderPage(CmsBuilderPage $page): void
    {
        if ($page->status !== 'published') {
            $this->forgetBuilderPage($page);

            return;
        }

        Fallback::register(
            slug: (string) CmsUrl::path($page),
            handler: 'cms::frontend.builder-page-viewer',
            module: 'cms',
            params: ['builder_page_id' => $page->id],
            handlerType: 'livewire',
        );
    }

    /** Pass $slug to forget a previous path (slug rename). */
    public function forgetBuilderPage(CmsBuilderPage $page, ?string $slug = null): void
    {
        Fallback::forget($slug ?? (string) CmsUrl::path($page));
    }

    public function syncSection(CmsSection $section): void
    {
        if ($section->status !== 'published') {
            $this->forgetSection($section);

            return;
        }

        Fallback::register(
            slug: (string) CmsUrl::path($section),
            handler: 'cms::frontend.section-index',
            module: 'cms',
            params: ['section_id' => $section->id],
            handlerType: 'livewire',
        );
    }

    public function forgetSection(CmsSection $section): void
    {
        Fallback::forget((string) CmsUrl::path($section));

        // Section deletes DB-cascade its categories and articles.
        $this->search->pruneOrphans();
    }

    public function syncCategory(CmsCategory $category): void
    {
        $category->loadMissing('section');
        $section = $category->section;

        if ($section->status !== 'published' || $category->status !== 'published') {
            $this->forgetCategory($category);

            return;
        }

        $path = (string) CmsUrl::path($category);

        Fallback::register(
            slug: $path,
            handler: 'cms::frontend.category-index',
            module: 'cms',
            params: ['category_id' => $category->id],
            handlerType: 'livewire',
        );

        $this->search->index($category, $path);
    }

    public function forgetCategory(CmsCategory $category): void
    {
        $category->loadMissing('section');
        Fallback::forget((string) CmsUrl::path($category));

        $this->search->forget($category);
    }

    /**
     * Forget a category's old slug (before rename) and all article routes under it.
     */
    public function forgetCategoryByOldSlug(string $sectionSlug, string $oldCategorySlug, CmsCategory $category): void
    {
        Fallback::forget($sectionSlug.'/'.$oldCategorySlug);

        foreach ($category->articles as $article) {
            Fallback::forget($sectionSlug.'/'.$oldCategorySlug.'/'.$article->slug);
        }
    }

    public function syncArticle(CmsArticle $article): void
    {
        $article->loadMissing(['section', 'category']);
        $section = $article->section;

        if ($article->status->value !== 'published') {
            $this->forgetArticle($article);

            return;
        }

        $path = (string) CmsUrl::path($article);

        Fallback::register(
            slug: $path,
            handler: 'cms::frontend.article-viewer',
            module: 'cms',
            params: ['article_id' => $article->id],
            handlerType: 'livewire',
        );

        $this->search->index($article, $path);
    }

    public function forgetArticle(CmsArticle $article): void
    {
        $article->loadMissing(['section', 'category']);
        Fallback::forget((string) CmsUrl::path($article));

        $this->search->forget($article);
    }

    public function syncAlbum(CmsAlbum $album): void
    {
        // Only normal albums get a public page; banner albums are frontend-only via component
        if ($album->type !== AlbumType::Normal || $album->status !== 'active') {
            $this->forgetAlbum($album);

            return;
        }

        $path = (string) CmsUrl::path($album);

        Fallback::register(
            slug: $path,
            handler: 'cms::frontend.album-viewer',
            module: 'cms',
            params: ['album_id' => $album->id],
            handlerType: 'livewire',
        );

        $this->search->index($album, $path);
    }

    public function forgetAlbum(CmsAlbum $album): void
    {
        Fallback::forget((string) CmsUrl::path($album));

        $this->search->forget($album);
    }

    /**
     * @param  array<string, mixed>  $extraParams
     */
    public function syncGalleryIndex(string $slug, array $extraParams = []): void
    {
        Fallback::register(
            slug: $slug,
            handler: 'cms::frontend.gallery-index',
            module: 'cms',
            params: $extraParams,
            handlerType: 'livewire',
        );
    }

    public function forgetGalleryIndex(string $slug): void
    {
        // Only remove the fallback if no other album_list item resolves to this slug.
        $effectiveSlug = $slug ?: 'gallery';

        $stillUsed = CmsMenuItem::where('target_type', 'album_list')
            ->get()
            ->contains(fn ($item) => ($item->target_url ?: 'gallery') === $effectiveSlug);

        if (! $stillUsed) {
            Fallback::forget($effectiveSlug);
        }
    }

    /**
     * Register the public search page at the configured slug (cms:search-reindex / cms:setup).
     */
    public function syncSearchPage(): void
    {
        Fallback::register(
            slug: (string) config('synapps.modules.cms.search.slug', 'search'),
            handler: 'cms::frontend.search-results',
            module: 'cms',
            handlerType: 'livewire',
        );
    }

    public function getDownloadPrefix(): string
    {
        return CmsUrl::downloadPrefix();
    }

    public function syncDownload(CmsDownload $download): void
    {
        if ($download->status !== 'active') {
            $this->forgetDownload($download);

            return;
        }

        $path = (string) CmsUrl::path($download);

        Fallback::register(
            slug: $path,
            handler: 'cms::frontend.download-viewer',
            module: 'cms',
            params: ['download_id' => $download->id],
            handlerType: 'livewire',
        );

        $this->search->index($download, $path);
    }

    public function forgetDownload(CmsDownload $download): void
    {
        Fallback::forget((string) CmsUrl::path($download));

        $this->search->forget($download);
    }

    /**
     * @param  array<string, mixed>  $extraParams
     */
    public function syncDownloadIndex(string $slug, array $extraParams = []): void
    {
        $effectiveSlug = $slug ?: 'downloads';

        Fallback::register(
            slug: $effectiveSlug,
            handler: 'cms::frontend.download-index',
            module: 'cms',
            params: $extraParams,
            handlerType: 'livewire',
        );

        // Re-sync all active downloads so their routes use the new prefix
        CmsDownload::active()->get()->each(fn ($d) => $this->syncDownload($d));
    }

    public function forgetDownloadIndex(string $slug): void
    {
        $effectiveSlug = $slug ?: 'downloads';

        $stillUsed = CmsMenuItem::where('target_type', 'download_list')
            ->get()
            ->contains(fn ($item) => ($item->target_url ?: 'downloads') === $effectiveSlug);

        if (! $stillUsed) {
            Fallback::forget($effectiveSlug);
        }

        // Re-sync all active downloads so their routes reflect the new effective prefix
        CmsDownload::active()->get()->each(fn ($d) => $this->syncDownload($d));
    }

    /**
     * Re-sync all child pages when a parent page's slug changes.
     */
    public function syncPageDescendants(CmsPage $page): void
    {
        foreach ($page->children as $child) {
            $child->full_slug = $page->full_slug.'/'.$child->slug;
            $child->saveQuietly();
            $this->syncPage($child);
            $this->syncPageDescendants($child);
        }
    }

    /**
     * Re-sync all articles in a section when section slug changes.
     */
    public function syncSectionArticles(CmsSection $section): void
    {
        foreach ($section->categories as $category) {
            $this->syncCategory($category);
            foreach ($category->articles as $article) {
                $this->syncArticle($article);
            }
        }

        /** @var Collection<int, CmsArticle> $directArticles */
        $directArticles = $section->articles()->whereNull('category_id')->get();
        foreach ($directArticles as $article) {
            $this->syncArticle($article);
        }
    }
}
