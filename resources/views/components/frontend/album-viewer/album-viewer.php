<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public int $album_id;

    public ?CmsAlbum $album = null;

    public string $galleryUrl = '/gallery';

    public function mount(int $album_id): void
    {
        $this->album_id = $album_id;
        $this->album = app(CmsGalleryService::class)->findActiveNormalAlbumOrFailById(
            $album_id,
            ['images.file'],
        );

        app(CmsAccessService::class)->authorizeView($this->album);

        // Derive the gallery index URL by stripping /{album-slug} from the current path
        $path = ltrim(request()->getPathInfo(), '/');
        $parts = explode('/', $path);
        if (count($parts) >= 2) {
            $this->galleryUrl = '/'.implode('/', array_slice($parts, 0, count($parts) - 1));
        }
    }

    public function title(): string
    {
        return $this->album?->translatedMetaTitle() ?? '';
    }

    public function description(): ?string
    {
        return $this->album?->translatedMetaDescription();
    }

    public function render()
    {
        $template = FrontendViewResolver::view(
            $this->album?->resolveCustomView(),
            'cms::components.frontend.album-viewer.album-viewer',
        );

        return view($template, ['album' => $this->album])
            ->title($this->title())
            ->layoutData(['description' => $this->description()])
            ->layout(FrontendViewResolver::layout($this->album?->resolveCustomLayout()));
    }
};
