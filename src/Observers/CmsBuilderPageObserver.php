<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsBuilderPageObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsBuilderPage $page): void
    {
        $this->fallback->syncBuilderPage($page);
    }

    public function updated(CmsBuilderPage $page): void
    {
        // In `updated` the original still holds the previous slug.
        if ($page->wasChanged('slug')) {
            $this->fallback->forgetBuilderPage($page, (string) $page->getOriginal('slug'));
        }

        $this->fallback->syncBuilderPage($page);
    }

    public function deleted(CmsBuilderPage $page): void
    {
        $this->fallback->forgetBuilderPage($page);
    }
}
