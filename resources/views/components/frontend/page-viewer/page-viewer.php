<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsPage;

new class extends Component
{
    public int $page_id;

    public ?CmsPage $page = null;

    public function mount(int $page_id): void
    {
        $this->page_id = $page_id;
        $this->page = CmsPage::with(['parent', 'children' => fn ($q) => $q->published()])
            ->published()
            ->findOrFail($page_id);
    }

    public function title(): string
    {
        return $this->page ? ($this->page->meta_title ?: $this->page->title) : '';
    }

    public function render()
    {
        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.page-viewer.page-viewer')
            ->title($this->title())
            ->layout($layout);
    }
};
