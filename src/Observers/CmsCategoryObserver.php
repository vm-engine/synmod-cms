<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsCategoryObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsCategory $category): void
    {
        $this->fallback->syncCategory($category);
    }

    public function updated(CmsCategory $category): void
    {
        $this->fallback->syncCategory($category);
    }

    public function deleted(CmsCategory $category): void
    {
        $this->fallback->forgetCategory($category);
    }
}
