<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Models\CmsArticle;
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
        // Slug or visibility changes affect every category/article route and index entry
        if ($section->wasChanged(['slug', 'visibility'])) {
            $this->fallback->syncSectionArticles($section);
        }

        $this->fallback->syncSection($section);
    }

    public function deleted(CmsSection $section): void
    {
        // Articles are DB-cascaded (no model events), so drop their grants here.
        CmsAccessGrant::where('grantable_type', (new CmsArticle)->getMorphClass())
            ->whereNotIn('grantable_id', CmsArticle::query()->select('id'))
            ->delete();

        $this->fallback->forgetSection($section);
    }
}
