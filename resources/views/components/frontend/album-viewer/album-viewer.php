<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;

new class extends Component
{
    public int $album_id;

    public ?CmsAlbum $album = null;

    public string $galleryUrl = '/gallery';

    public function mount(int $album_id): void
    {
        $this->album_id = $album_id;
        $this->album = CmsAlbum::with('images.file')->active()->normal()->findOrFail($album_id);

        // Derive the gallery index URL by stripping /{album-slug} from the current path
        $path = ltrim(request()->getPathInfo(), '/');
        $parts = explode('/', $path);
        if (count($parts) >= 2) {
            $this->galleryUrl = '/'.implode('/', array_slice($parts, 0, count($parts) - 1));
        }
    }

    public function title(): string
    {
        return $this->album ? ($this->album->meta_title ?: $this->album->title) : '';
    }

    public function render()
    {
        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.album-viewer.album-viewer')
            ->title($this->title())
            ->layout($layout);
    }
};
