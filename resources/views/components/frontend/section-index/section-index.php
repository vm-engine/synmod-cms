<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;

new class extends Component
{
    use WithPagination;

    public int $section_id;

    public ?CmsSection $section = null;

    public function mount(int $section_id): void
    {
        $this->section_id = $section_id;
        $this->section = CmsSection::with('categories')->published()->findOrFail($section_id);
    }

    public function title(): string
    {
        return $this->section ? ($this->section->meta_title ?: $this->section->title) : '';
    }

    public function render()
    {
        $config = $this->section?->config ?? [];
        $perPage = $config['items_per_page'] ?? 12;
        $orderBy = $config['order_by'] ?? 'published_at';
        $orderDir = $config['order_dir'] ?? 'desc';

        $articles = CmsArticle::published()
            ->where('section_id', $this->section_id)
            ->with(['category', 'tags'])
            ->orderBy($orderBy, $orderDir)
            ->paginate($perPage)
            ->onEachSide(1);

        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.section-index.section-index', ['articles' => $articles])
            ->title($this->title())
            ->layout($layout);
    }
};
