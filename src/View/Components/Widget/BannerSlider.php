<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsAlbum;

class BannerSlider extends Component
{
    public ?CmsAlbum $albumRecord;

    /** @var array<int, array{url: string, thumb: string, title: string, caption: string}> */
    public array $slides;

    public function __construct(
        public string $album = '',
        public bool $autoplay = true,
        public int $interval = 5000,
        public bool $showCaption = true,
        public bool $showControls = true,
        public bool $showIndicators = true,
        public string $height = '480px',
    ) {
        $this->albumRecord = CmsAlbum::with('images.file')
            ->where('slug', $this->album)
            ->banner()
            ->active()
            ->first();

        $this->slides = $this->albumRecord
            ? $this->albumRecord->images
                ->filter(fn ($i) => $i->file !== null)
                ->map(fn ($i) => [
                    'url' => $i->file->getUrl(),
                    'thumb' => $i->file->getThumbnailUrl() ?: $i->file->getUrl(),
                    'title' => $i->title ?? '',
                    'caption' => $i->caption ?? '',
                ])->values()->all()
            : [];
    }

    public function render(): View
    {
        return view('cms::components.widget.banner-slider.banner-slider');
    }
}
