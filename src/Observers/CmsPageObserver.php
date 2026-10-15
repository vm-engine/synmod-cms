<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsFallbackService;
use VmEngine\Cms\Support\CmsPagePaletteIndex;

class CmsPageObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsPage $page): void
    {
        app(CmsAccessService::class)->forgetPageTree();
        $this->fallback->syncPage($page);
        CmsPagePaletteIndex::put($page);
    }

    public function updated(CmsPage $page): void
    {
        app(CmsAccessService::class)->forgetPageTree();

        // Slug, parent or visibility changes affect every descendant's route/index entry
        if ($page->wasChanged(['full_slug', 'parent_id', 'visibility'])) {
            $this->fallback->syncPageDescendants($page);
        }

        $this->fallback->syncPage($page);
        CmsPagePaletteIndex::put($page);
    }

    public function deleted(CmsPage $page): void
    {
        app(CmsAccessService::class)->forgetPageTree();
        $this->fallback->forgetPage($page);
        CmsPagePaletteIndex::forget($page->id);
    }
}
