<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsDownload;

new class extends Component
{
    public string $indexUrl = '/downloads';

    public function mount(): void
    {
        // The current path IS the index URL; derive prefix from it
        $this->indexUrl = '/'.ltrim(request()->getPathInfo(), '/');
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

        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.download-index.download-index', [
            'downloads' => $downloads,
            'indexUrl' => $this->indexUrl,
        ])
            ->title(__('cms::labels.downloads'))
            ->layout($layout);
    }
};
