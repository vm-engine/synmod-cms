<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsSectionObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsSection $section): void
    {
        $this->fallback->syncSection($section);
    }

    public function updated(CmsSection $section): void
    {
        // If slug changed, re-sync all categories and articles
        if ($section->wasChanged('slug')) {
            $this->fallback->syncSectionArticles($section);
        }

        $this->fallback->syncSection($section);
    }

    public function deleted(CmsSection $section): void
    {
        $this->fallback->forgetSection($section);
    }
}
