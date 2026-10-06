<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Cms\Support\FrontendViewResolver;
use VmEngine\Cms\Traits\WithFieldFilters;

new class extends Component
{
    use WithFieldFilters;

    public int $page_id;

    public ?CmsPage $page = null;

    public function mount(int $page_id): void
    {
        $this->page_id = $page_id;
        $this->page = app(CmsPageService::class)->findPublishedOrFailById(
            $page_id,
            ['parent'],
        );

        app(CmsAccessService::class)->authorizeView($this->page);
    }

    public function title(): string
    {
        return $this->page?->translatedMetaTitle() ?? '';
    }

    public function description(): ?string
    {
        return $this->page?->translatedMetaDescription();
    }

    public function render()
    {
        $filter = $this->fieldFilter($this->page?->fieldSchemaOwner());
        if ($this->page !== null) {
            $children = $filter->apply($this->page->children()->getQuery()->published())->get();
            $this->page->setRelation('children', app(CmsAccessService::class)->filterVisiblePages($children));
        }

        $template = FrontendViewResolver::view(
            $this->page?->resolveCustomView(),
            'cms::components.frontend.page-viewer.page-viewer',
        );

        return view($template, ['page' => $this->page, 'fieldFilter' => $filter])
            ->title($this->title())
            ->layoutData(['description' => $this->description()])
            ->layout(FrontendViewResolver::layout($this->page?->resolveCustomLayout()));
    }
};
