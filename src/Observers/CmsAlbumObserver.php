<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsAlbumObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsAlbum $album): void
    {
        $this->fallback->syncAlbum($album);
    }

    public function updated(CmsAlbum $album): void
    {
        $this->fallback->syncAlbum($album);
    }

    public function deleted(CmsAlbum $album): void
    {
        $this->fallback->forgetAlbum($album);
    }
}
