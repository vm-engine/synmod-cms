<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Enums\DownloadAccess;
use VmEngine\Cms\Models\CmsDownload;

new class extends Component
{
    public int $download_id;

    public ?CmsDownload $download = null;

    public string $indexUrl = '/downloads';

    public function mount(int $download_id): void
    {
        $this->download_id = $download_id;
        $this->download = CmsDownload::with('files.file')->active()->findOrFail($download_id);

        // Enforce private access
        if ($this->download->access === DownloadAccess::Private && ! auth()->check()) {
            redirect()->route('auth.login')->send();
        }

        // Derive the download index URL by stripping /{download-slug} from the current path
        $path = ltrim(request()->getPathInfo(), '/');
        $parts = explode('/', $path);
        if (count($parts) >= 2) {
            $this->indexUrl = '/'.implode('/', array_slice($parts, 0, count($parts) - 1));
        }
    }

    public function title(): string
    {
        return $this->download ? ($this->download->meta_title ?: $this->download->title) : '';
    }

    public function render()
    {
        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.download-viewer.download-viewer')
            ->title($this->title())
            ->layout($layout);
    }
};
