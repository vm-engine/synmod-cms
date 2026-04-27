<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public ?string $custom_view = null;

    public ?string $custom_layout = null;

    public function mount(?string $custom_view = null, ?string $custom_layout = null): void
    {
        $this->custom_view = $custom_view;
        $this->custom_layout = $custom_layout;
    }

    public function render()
    {
        $albums = app(CmsGalleryService::class)->getActiveNormalAlbums(['images']);

        $template = FrontendViewResolver::view(
            $this->custom_view,
            'cms::components.frontend.gallery-index.gallery-index',
        );

        return view($template, ['albums' => $albums])
            ->title(__('cms::labels.gallery'))
            ->layout(FrontendViewResolver::layout($this->custom_layout));
    }
};
