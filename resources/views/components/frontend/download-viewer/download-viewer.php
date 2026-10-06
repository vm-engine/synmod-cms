<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Cms\Support\FrontendViewResolver;
use VmEngine\Cms\Traits\WithFieldFilters;

new class extends Component
{
    use WithFieldFilters;

    public int $download_id;

    public ?CmsDownload $download = null;

    public string $indexUrl = '/downloads';

    public function mount(int $download_id): void
    {
        $this->download_id = $download_id;
        $this->download = app(CmsDownloadService::class)->findActiveOrFailById($download_id, ['files.file']);

        app(CmsAccessService::class)->authorizeView($this->download);

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

    public function description(): ?string
    {
        return $this->download?->translatedMetaDescription();
    }

    public function render()
    {
        $filter = $this->fieldFilter($this->download);
        $this->download?->setRelation('files', $filter->apply($this->download->files()->with('file')->getQuery())->get());

        $template = FrontendViewResolver::view(
            $this->download?->resolveCustomView(),
            'cms::components.frontend.download-viewer.download-viewer',
        );

        return view($template, ['download' => $this->download, 'fieldFilter' => $filter])
            ->title($this->title())
            ->layoutData(['description' => $this->description()])
            ->layout(FrontendViewResolver::layout($this->download?->resolveCustomLayout()));
    }
};
