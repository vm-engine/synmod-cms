<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public string $indexUrl = '/downloads';

    public ?string $custom_view = null;

    public ?string $custom_layout = null;

    public function mount(?string $custom_view = null, ?string $custom_layout = null): void
    {
        // The current path IS the index URL; derive prefix from it
        $this->indexUrl = '/'.ltrim(request()->getPathInfo(), '/');
        $this->custom_view = $custom_view;
        $this->custom_layout = $custom_layout;
    }

    public function render()
    {
        $isLoggedIn = auth()->check();

        $query = CmsDownload::withCount('files')->active()->orderBy('sort_order');

        // Guests only see public downloads
        if (! $isLoggedIn) {
            $query->publicAccess();
        }

        $downloads = $query->get();

        $template = FrontendViewResolver::view(
            $this->custom_view,
            'cms::components.frontend.download-index.download-index',
        );

        return view($template, [
            'downloads' => $downloads,
            'indexUrl' => $this->indexUrl,
        ])
            ->title(__('cms::labels.downloads'))
            ->layout(FrontendViewResolver::layout($this->custom_layout));
    }
};
