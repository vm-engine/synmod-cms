<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;

new class extends Component
{
    public int $album_id;

    public ?CmsAlbum $album = null;

    public function mount(int $album_id): void
    {
        $this->album_id = $album_id;
        $this->album = CmsAlbum::with('images.file')->active()->normal()->findOrFail($album_id);
    }

    public function title(): string
    {
        return $this->album ? ($this->album->meta_title ?: $this->album->title) : '';
    }

    public function render()
    {
        return $this->view()->title($this->title());
    }
};
