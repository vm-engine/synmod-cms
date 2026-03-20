<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsPageObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsPage $page): void
    {
        $this->fallback->syncPage($page);
    }

    public function updated(CmsPage $page): void
    {
        // If full_slug changed, we need to re-sync descendants too
        if ($page->wasChanged('full_slug')) {
            $this->fallback->syncPageDescendants($page);
        }

        $this->fallback->syncPage($page);
    }

    public function deleted(CmsPage $page): void
    {
        $this->fallback->forgetPage($page);
    }
}
