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
        if ($category->wasChanged('slug')) {
            $category->loadMissing(['section', 'articles']);
            $this->fallback->forgetCategoryByOldSlug(
                $category->section->slug,
                $category->getOriginal('slug'),
                $category,
            );
        }

        $this->fallback->syncCategory($category);

        // Re-sync articles so their routes reflect the new category slug
        $category->loadMissing('articles');
        foreach ($category->articles as $article) {
            $this->fallback->syncArticle($article);
        }
    }

    public function deleted(CmsCategory $category): void
    {
        $this->fallback->forgetCategory($category);
    }
}
