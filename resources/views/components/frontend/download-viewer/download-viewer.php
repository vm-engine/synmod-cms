<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Enums\DownloadAccess;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public int $download_id;

    public ?CmsDownload $download = null;

    public string $indexUrl = '/downloads';

    public function mount(int $download_id): void
    {
        $this->download_id = $download_id;
        $this->download = app(CmsDownloadService::class)->findActiveOrFailById($download_id, ['files.file']);

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
        return $this->download?->translatedMetaTitle() ?? '';
    }

    public function render()
    {
        $template = FrontendViewResolver::view(
            $this->download?->resolveCustomView(),
            'cms::components.frontend.download-viewer.download-viewer',
        );

        return view($template, ['download' => $this->download])
            ->title($this->title())
            ->layout(FrontendViewResolver::layout($this->download?->resolveCustomLayout()));
    }
};
