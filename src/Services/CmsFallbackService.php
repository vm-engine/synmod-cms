<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Database\Eloquent\Collection;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynFallback\Facades\Fallback;

class CmsFallbackService
{
    public function syncPage(CmsPage $page): void
    {
        if ($page->status !== 'published') {
            $this->forgetPage($page);

            return;
        }

        Fallback::register(
            slug: $page->full_slug,
            handler: 'cms::frontend.page-viewer',
            module: 'cms',
            params: ['page_id' => $page->id],
            handlerType: 'livewire',
        );
    }

    public function forgetPage(CmsPage $page): void
    {
        Fallback::forget($page->full_slug);
    }

    public function syncSection(CmsSection $section): void
    {
        if ($section->status !== 'published') {
            $this->forgetSection($section);

            return;
        }

        Fallback::register(
            slug: $section->slug,
            handler: 'cms::frontend.section-index',
            module: 'cms',
            params: ['section_id' => $section->id],
            handlerType: 'livewire',
        );
    }

    public function forgetSection(CmsSection $section): void
    {
        Fallback::forget($section->slug);
    }

    public function syncCategory(CmsCategory $category): void
    {
        $category->loadMissing('section');
        $section = $category->section;

        if ($section->status !== 'published' || $category->status !== 'published') {
            $this->forgetCategory($category);

            return;
        }

        $slug = $section->slug.'/'.$category->slug;

        Fallback::register(
            slug: $slug,
            handler: 'cms::frontend.category-index',
            module: 'cms',
            params: ['category_id' => $category->id],
            handlerType: 'livewire',
        );
    }

    public function forgetCategory(CmsCategory $category): void
    {
        $category->loadMissing('section');
        Fallback::forget($category->section->slug.'/'.$category->slug);
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

        if ($article->category_id !== null && $article->category) {
            $slug = $section->slug.'/'.$article->category->slug.'/'.$article->slug;
        } else {
            $slug = $section->slug.'/'.$article->slug;
        }

        Fallback::register(
            slug: $slug,
            handler: 'cms::frontend.article-viewer',
            module: 'cms',
            params: ['article_id' => $article->id],
            handlerType: 'livewire',
        );
    }

    public function forgetArticle(CmsArticle $article): void
    {
        $article->loadMissing(['section', 'category']);
        $section = $article->section;

        if ($article->category_id !== null && $article->category) {
            Fallback::forget($section->slug.'/'.$article->category->slug.'/'.$article->slug);
        } else {
            Fallback::forget($section->slug.'/'.$article->slug);
        }
    }

    public function syncAlbum(CmsAlbum $album): void
    {
        // Only normal albums get a public page; banner albums are frontend-only via component
        if ($album->type !== AlbumType::Normal || $album->status !== 'active') {
            $this->forgetAlbum($album);

            return;
        }

        Fallback::register(
            slug: 'gallery/'.$album->slug,
            handler: 'cms::frontend.album-viewer',
            module: 'cms',
            params: ['album_id' => $album->id],
            handlerType: 'livewire',
        );
    }

    public function forgetAlbum(CmsAlbum $album): void
    {
        Fallback::forget('gallery/'.$album->slug);
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

    public function getDownloadPrefix(): string
    {
        $item = CmsMenuItem::where('target_type', 'download_list')
            ->whereNotNull('target_url')
            ->where('target_url', '!=', '')
            ->first();

        return $item ? ($item->target_url ?: 'downloads') : 'downloads';
    }

    public function syncDownload(CmsDownload $download): void
    {
        if ($download->status !== 'active') {
            $this->forgetDownload($download);

            return;
        }

        $prefix = $this->getDownloadPrefix();

        Fallback::register(
            slug: $prefix.'/'.$download->slug,
            handler: 'cms::frontend.download-viewer',
            module: 'cms',
            params: ['download_id' => $download->id],
            handlerType: 'livewire',
        );
    }

    public function forgetDownload(CmsDownload $download): void
    {
        $prefix = $this->getDownloadPrefix();
        Fallback::forget($prefix.'/'.$download->slug);
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
