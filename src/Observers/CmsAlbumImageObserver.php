<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Services\CmsSearchIndexer;

class CmsAlbumImageObserver
{
    public function __construct(
        private readonly CmsGalleryService $gallery,
        private readonly CmsSearchIndexer $search,
    ) {}

    public function created(CmsAlbumImage $image): void
    {
        $this->gallery->applyAutoCoverOnImageCreate($image);
        $this->search->indexItem($image);
    }

    public function updated(CmsAlbumImage $image): void
    {
        $this->search->indexItem($image);
    }

    public function deleted(CmsAlbumImage $image): void
    {
        $this->gallery->recomputeCoverOnImageDelete($image);
        $this->search->forgetItem($image);
    }
}
