<?php

declare(strict_types=1);

namespace VmEngine\Cms\Observers;

use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Fm\Models\FmFile;

class CmsAlbumImageObserver
{
    public function created(CmsAlbumImage $image): void
    {
        // Auto-set album cover from the first image added if no cover exists
        /** @var CmsAlbum|null $album */
        $album = $image->album()->first();

        if (! $album instanceof CmsAlbum || $album->cover_image !== null) {
            return;
        }

        $image->loadMissing('file');
        $file = $image->file;

        if ($file instanceof FmFile) {
            $album->update(['cover_image' => $file->getUrl()]);
        }
    }

    public function deleted(CmsAlbumImage $image): void
    {
        /** @var CmsAlbum|null $album */
        $album = $image->album()->first();

        if (! $album instanceof CmsAlbum || $album->cover_image === null) {
            return;
        }

        $image->loadMissing('file');
        $file = $image->file;
        $imageUrl = $file instanceof FmFile ? $file->getUrl() : null;

        // Only update cover if this image was the one used as cover
        if ($imageUrl === null || $album->cover_image !== $imageUrl) {
            return;
        }

        // Find the next available image (excluding the deleted one)
        $next = CmsAlbumImage::where('album_id', $album->id)
            ->where('id', '!=', $image->id)
            ->with('file')
            ->orderBy('sort_order')
            ->get()
            ->first(fn (CmsAlbumImage $i): bool => $i->file instanceof FmFile);

        $newCover = $next?->file instanceof FmFile ? $next->file->getUrl() : null;
        $album->update(['cover_image' => $newCover]);
    }
}
