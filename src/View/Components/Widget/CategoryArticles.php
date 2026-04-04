<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;

class CategoryArticles extends Component
{
    public ?CmsSection $sectionModel;

    public ?CmsCategory $categoryModel;

    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    public function __construct(
        public string $section = '',
        public string $category = '',
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
    ) {
        $this->sectionModel = CmsSection::where('slug', $this->section)
            ->published()
            ->first();

        $this->categoryModel = $this->sectionModel
            ? CmsCategory::where('slug', $this->category)
                ->where('section_id', $this->sectionModel->id)
                ->published()
                ->first()
            : null;

        $query = CmsArticle::published();

        if ($this->sectionModel) {
            $query->where('section_id', $this->sectionModel->id);
        }

        if ($this->categoryModel) {
            $query->where('category_id', $this->categoryModel->id);
        }

        match ($this->sort) {
            'featured' => $query->featured()->orderBy('published_at', 'desc'),
            'oldest' => $query->orderBy('published_at', 'asc'),
            'az' => $query->orderBy('title', 'asc'),
            'za' => $query->orderBy('title', 'desc'),
            default => $query->orderBy('published_at', 'desc'),
        };

        $this->articles = $query->limit($this->limit)->get();
    }

    public function render(): View
    {
        return view('cms::components.widget.category-articles.category-articles');
    }
}
