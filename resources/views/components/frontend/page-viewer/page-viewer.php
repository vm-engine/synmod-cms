<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public int $page_id;

    public ?CmsPage $page = null;

    public function mount(int $page_id): void
    {
        $this->page_id = $page_id;
        $this->page = app(CmsPageService::class)->findPublishedOrFailById(
            $page_id,
            ['parent', 'children' => fn ($q) => $q->published()],
        );
    }

    public function title(): string
    {
        return $this->page?->translatedMetaTitle() ?? '';
    }

    public function render()
    {
        $template = FrontendViewResolver::view(
            $this->page?->resolveCustomView(),
            'cms::components.frontend.page-viewer.page-viewer',
        );

        return view($template, ['page' => $this->page])
            ->title($this->title())
            ->layout(FrontendViewResolver::layout($this->page?->resolveCustomLayout()));
    }
};
