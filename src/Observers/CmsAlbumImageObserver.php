<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Services\CmsGalleryService;

class CmsAlbumImageObserver
{
    public function __construct(private readonly CmsGalleryService $gallery) {}

    public function created(CmsAlbumImage $image): void
    {
        $this->gallery->applyAutoCoverOnImageCreate($image);
    }

    public function deleted(CmsAlbumImage $image): void
    {
        $this->gallery->recomputeCoverOnImageDelete($image);
    }
}
