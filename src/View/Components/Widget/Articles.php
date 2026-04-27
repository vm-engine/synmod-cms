<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Services\CmsArticleService;

class Articles extends Component
{
    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    public function __construct(
        CmsArticleService $articleService,
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showSection = true,
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
        public bool $showFeaturedBadge = true,
    ) {
        $this->articles = $articleService->getPublishedArticlesForWidget($this->sort, $this->limit);
    }

    public function render(): View
    {
        return view('cms::components.widget.articles.articles');
    }
}
