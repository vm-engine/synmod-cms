<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsDownloadObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsDownload $download): void
    {
        $this->fallback->syncDownload($download);
    }

    public function updated(CmsDownload $download): void
    {
        $this->fallback->syncDownload($download);
    }

    public function deleted(CmsDownload $download): void
    {
        $this->fallback->forgetDownload($download);
    }
}
