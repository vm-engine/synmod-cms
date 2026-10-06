<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsPage;

/**
 * Swaps [gallery:key] tags in purified rich text for the owner's gallery
 * markup. Runs at view time, after HTMLPurifier, so gallery HTML is never
 * purified away. A tag alone in a paragraph replaces the whole <p>
 * (no block element inside <p>); unknown/empty/disabled tags render nothing.
 */
class CmsMediaGalleryRenderer
{
    private const PATTERN = '~<p[^>]*>(?:\s|&nbsp;|\x{00A0})*\[gallery:([a-z0-9-]+)\](?:\s|&nbsp;|\x{00A0})*</p>|\[gallery:([a-z0-9-]+)\]~iu';

    public function render(CmsPage|CmsArticle|CmsDownloadFile $owner, string $html): string
    {
        if (! str_contains($html, '[gallery:')) {
            return $html;
        }

        if (! $owner->mediaGalleriesEnabled()) {
            return self::strip($html);
        }

        $galleries = $owner->mediaGalleries()->with('images.file')->get()->keyBy('key');

        return (string) preg_replace_callback(self::PATTERN, function (array $match) use ($galleries): string {
            $gallery = $galleries->get(strtolower((string) ($match[1] ?? $match[2])));

            return $gallery instanceof CmsMediaGallery ? $this->renderGallery($gallery) : '';
        }, $html, flags: PREG_UNMATCHED_AS_NULL);
    }

    public function renderGallery(CmsMediaGallery $gallery): string
    {
        $slides = $gallery->slides();

        if ($slides === []) {
            return '';
        }

        $view = $gallery->layout === 'grid' ? '_media-gallery-grid' : '_media-gallery-slider';

        return view('cms::components.frontend.partials.'.$view, ['gallery' => $gallery, 'slides' => $slides])->render();
    }

    /** Remove every gallery tag (search text, feature off). */
    public static function strip(string $html): string
    {
        return (string) preg_replace(self::PATTERN, '', $html);
    }
}
