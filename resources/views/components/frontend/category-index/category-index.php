<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    use WithPagination;

    public int $category_id;

    public ?CmsCategory $category = null;

    public function mount(int $category_id): void
    {
        $this->category_id = $category_id;
        $this->category = app(CmsArticleService::class)->findPublishedCategoryOrFailById(
            $category_id,
            ['section'],
        );

        app(CmsAccessService::class)->authorizeView($this->category);
    }

    public function title(): string
    {
        return $this->category?->translatedMetaTitle() ?? '';
    }

    public function description(): ?string
    {
        return $this->category?->translatedMetaDescription();
    }

    public function render()
    {
        $config = $this->category?->effectiveConfig() ?? [];
        $perPage = $config['items_per_page'] ?? 12;
        $orderBy = $config['order_by'] ?? 'published_at';
        $orderDir = $config['order_dir'] ?? 'desc';

        $articles = app(CmsArticleService::class)->paginatePublishedArticlesInCategory(
            categoryId: $this->category_id,
            orderBy: $orderBy,
            orderDir: $orderDir,
            perPage: $perPage,
        );

        $template = FrontendViewResolver::view(
            $this->category?->resolveCustomView(),
            'cms::components.frontend.category-index.category-index',
        );

        return view($template, ['category' => $this->category, 'articles' => $articles])
            ->title($this->title())
            ->layoutData(['description' => $this->description()])
            ->layout(FrontendViewResolver::layout($this->category?->resolveCustomLayout()));
    }
};
