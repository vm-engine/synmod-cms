<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsArticleObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsArticle $article): void
    {
        $this->fallback->syncArticle($article);
    }

    public function updated(CmsArticle $article): void
    {
        $this->fallback->syncArticle($article);
    }

    public function deleted(CmsArticle $article): void
    {
        $this->fallback->forgetArticle($article);
    }
}
