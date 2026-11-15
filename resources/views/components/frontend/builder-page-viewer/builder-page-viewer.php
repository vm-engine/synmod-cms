<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public int $builder_page_id;

    public CmsBuilderPage $page;

    public function mount(int $builder_page_id): void
    {
        $this->builder_page_id = $builder_page_id;
        $this->page = app(CmsBuilderPageService::class)->findPublishedOrFailById($builder_page_id);
    }

    /**
     * @return array{name: string, view: string, regions: array<string, string>}
     */
    #[Computed()]
    public function template(): array
    {
        return app(CmsBuilderTemplates::class)->find($this->page->template);
    }

    public function render()
    {
        return $this->view()
            ->title($this->page->translatedMetaTitle())
            ->layoutData(['description' => $this->page->translatedMetaDescription(), 'ogImage' => $this->page->og_image])
            ->layout(FrontendViewResolver::layout());
    }
};
