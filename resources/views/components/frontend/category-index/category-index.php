<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;

new class extends Component
{
    use WithPagination;

    public int $category_id;

    public ?CmsCategory $category = null;

    public function mount(int $category_id): void
    {
        $this->category_id = $category_id;
        $this->category = CmsCategory::with('section')->published()->findOrFail($category_id);
    }

    public function title(): string
    {
        return $this->category ? ($this->category->meta_title ?: $this->category->title) : '';
    }

    public function render()
    {
        $config = $this->category?->effectiveConfig() ?? [];
        $perPage = $config['items_per_page'] ?? 12;
        $orderBy = $config['order_by'] ?? 'published_at';
        $orderDir = $config['order_dir'] ?? 'desc';

        $articles = CmsArticle::published()
            ->where('category_id', $this->category_id)
            ->with(['section', 'tags'])
            ->orderBy($orderBy, $orderDir)
            ->paginate($perPage)
            ->onEachSide(1);

        return $this->view(['articles' => $articles])->title($this->title());
    }
};
