<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\Cms\Services\CmsArticleService;

class TagArticles extends Component
{
    /** @var Collection<int, CmsTag> */
    public Collection $tagModels;

    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    public function __construct(
        CmsArticleService $articleService,
        public string $tags = '',
        public string $match = 'any',
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showSection = true,
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
    ) {
        $slugs = array_filter(array_map('trim', explode(',', $this->tags)));
        $this->tagModels = $articleService->getTagsBySlugs($slugs);

        $this->articles = $articleService->getPublishedArticlesByTagsForWidget(
            tagIds: $this->tagModels->pluck('id')->all(),
            match: $this->match,
            sort: $this->sort,
            limit: $this->limit,
        );
    }

    public function render(): View
    {
        return view('cms::components.widget.tag-articles.tag-articles');
    }
}
