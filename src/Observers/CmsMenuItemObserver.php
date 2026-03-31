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
        if ($item->target_type !== 'album_list') {
            return;
        }

        $this->fallback->syncGalleryIndex($item->target_url ?: 'gallery');
    }

    public function updated(CmsMenuItem $item): void
    {
        if ($item->target_type !== 'album_list' && $item->getOriginal('target_type') !== 'album_list') {
            return;
        }

        $oldSlug = $item->getOriginal('target_url') ?: 'gallery';
        $newSlug = $item->target_url ?: 'gallery';

        // If slug changed, forget the old one (if no other item still uses it)
        if ($oldSlug !== $newSlug) {
            $this->fallback->forgetGalleryIndex($oldSlug);
        }

        // If this is now an album_list item, register the new slug
        if ($item->target_type === 'album_list') {
            $this->fallback->syncGalleryIndex($newSlug);
        }
    }

    public function deleted(CmsMenuItem $item): void
    {
        if ($item->target_type !== 'album_list') {
            return;
        }

        $this->fallback->forgetGalleryIndex($item->target_url ?: 'gallery');
    }
}
