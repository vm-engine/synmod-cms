<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Services\CmsFallbackService;

class CmsMenuItemObserver
{
    public function __construct(private readonly CmsFallbackService $fallback) {}

    public function created(CmsMenuItem $item): void
    {
        if ($item->target_type === 'album_list') {
            $this->fallback->syncGalleryIndex($item->target_url ?: 'gallery');
        }

        if ($item->target_type === 'download_list') {
            $this->fallback->syncDownloadIndex($item->target_url ?: 'downloads');
        }
    }

    public function updated(CmsMenuItem $item): void
    {
        $oldType = $item->getOriginal('target_type');

        // Handle album_list changes
        if ($item->target_type === 'album_list' || $oldType === 'album_list') {
            $oldSlug = $item->getOriginal('target_url') ?: 'gallery';
            $newSlug = $item->target_url ?: 'gallery';

            if ($oldSlug !== $newSlug) {
                $this->fallback->forgetGalleryIndex($oldSlug);
            }

            if ($item->target_type === 'album_list') {
                $this->fallback->syncGalleryIndex($newSlug);
            }
        }

        // Handle download_list changes
        if ($item->target_type === 'download_list' || $oldType === 'download_list') {
            $oldSlug = $item->getOriginal('target_url') ?: 'downloads';
            $newSlug = $item->target_url ?: 'downloads';

            if ($oldSlug !== $newSlug) {
                $this->fallback->forgetDownloadIndex($oldSlug);
            }

            if ($item->target_type === 'download_list') {
                $this->fallback->syncDownloadIndex($newSlug);
            }
        }
    }

    public function deleted(CmsMenuItem $item): void
    {
        if ($item->target_type === 'album_list') {
            $this->fallback->forgetGalleryIndex($item->target_url ?: 'gallery');
        }

        if ($item->target_type === 'download_list') {
            $this->fallback->forgetDownloadIndex($item->target_url ?: 'downloads');
        }
    }
}
