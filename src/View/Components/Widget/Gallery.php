<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;

class Gallery extends Component
{
    public ?CmsAlbum $albumRecord;

    /** @var Collection<int, CmsAlbumImage> */
    public Collection $images;

    /**
     * @param  array<string, mixed>  $with  Extra vars forwarded into the custom $card/$view.
     */
    public function __construct(
        public string $album = '',
        public int $limit = 0,
        public bool $showTitle = true,
        public bool $showCaption = true,
        /** Custom Blade view rendered per image (receives $image + $album + $with). */
        public string $card = '',
        /** Fully custom view replacing the entire gallery (receives $images + $album + $with). */
        public string $template = '',
        /** Extra classes on the outer <div>. */
        public string $wrapperClass = '',
        /** Extra classes on the grid container. */
        public string $gridClass = '',
        /** Skip the default wrappers entirely. */
        public bool $bare = false,
        public array $with = [],
    ) {
        $this->albumRecord = CmsAlbum::with('images.file')
            ->where('slug', $this->album)
            ->active()
            ->first();

        /** @var Collection<int, CmsAlbumImage> */
        $images = $this->albumRecord
            ? $this->albumRecord->images
                ->filter(fn (CmsAlbumImage $i) => $i->file !== null)
                ->values()
            : new Collection;

        if ($this->limit > 0) {
            $images = $images->take($this->limit);
        }

        $this->images = $images;
    }

    public function render(): View
    {
        return view('cms::components.widget.gallery.gallery');
    }
}
