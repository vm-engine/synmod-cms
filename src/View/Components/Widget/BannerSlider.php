<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Services\CmsGalleryService;

class BannerSlider extends Component
{
    public ?CmsAlbum $albumRecord;

    /** @var array<int, array{url: string, thumb: string, title: string, caption: string, link: string|null}> */
    public array $slides;

    /** @var Collection<int, CmsAlbumImage> */
    public Collection $images;

    /**
     * @param  array<string, mixed>  $with  Extra vars forwarded into the custom $template.
     */
    public function __construct(
        CmsGalleryService $galleryService,
        public string $album = '',
        public bool $autoplay = true,
        public int $interval = 5000,
        public bool $showCaption = true,
        public bool $showControls = true,
        public bool $showIndicators = true,
        public string $height = '480px',
        /** Custom Blade view rendered instead of the default slider (receives $slides, $album record, $images collection, $with). */
        public string $template = '',
        public array $with = [],
    ) {
        $this->albumRecord = $galleryService->findActiveBannerAlbumBySlug($this->album, ['images.file']);

        /** @var Collection<int, CmsAlbumImage> */
        $images = $this->albumRecord
            ? $this->albumRecord->images->filter(fn (CmsAlbumImage $i) => $i->file !== null)->values()
            : new Collection;

        $this->images = $images;

        $this->slides = $this->images
            ->map(fn (CmsAlbumImage $i) => [
                'url' => $i->file?->getUrl() ?? '',
                'thumb' => $i->file?->getThumbnailUrl() ?: ($i->file?->getUrl() ?? ''),
                'title' => $i->title ?? '',
                'caption' => $i->caption ?? '',
                'link' => $i->config['link'] ?? null,
            ])->values()->all();
    }

    public function render(): View
    {
        return view('cms::components.widget.banner-slider.banner-slider');
    }
}
