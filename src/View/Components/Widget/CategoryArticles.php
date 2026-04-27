<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsArticleService;

class CategoryArticles extends Component
{
    public ?CmsSection $sectionModel;

    public ?CmsCategory $categoryModel;

    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    public function __construct(
        CmsArticleService $articleService,
        public string $section = '',
        public string $category = '',
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
    ) {
        $this->sectionModel = $articleService->findPublishedSectionBySlug($this->section);

        $this->categoryModel = $this->sectionModel
            ? $articleService->findPublishedCategoryBySlugInSection($this->category, $this->sectionModel->id)
            : null;

        $this->articles = $articleService->getPublishedArticlesInCategoryForWidget(
            sectionId: $this->sectionModel?->id,
            categoryId: $this->categoryModel?->id,
            sort: $this->sort,
            limit: $this->limit,
        );
    }

    public function render(): View
    {
        return view('cms::components.widget.category-articles.category-articles');
    }
}
