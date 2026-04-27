<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsArticleService;

class SectionArticles extends Component
{
    public ?CmsSection $sectionModel;

    /** @var Collection<int, CmsArticle> */
    public Collection $articles;

    /**
     * @param  array<string, mixed>  $with  Extra vars forwarded into the custom $template.
     */
    public function __construct(
        CmsArticleService $articleService,
        public string $section = '',
        public int $limit = 6,
        public string $sort = 'latest',
        public string $layout = 'grid',
        public bool $showCategory = true,
        public bool $showExcerpt = true,
        public bool $showCover = true,
        public bool $showDate = true,
        /** Custom Blade view rendered per article (receives $article + $section). Fallback to default card when empty. */
        public string $card = '',
        /** Custom Blade view rendered instead of the default section markup (receives $section, $articles, $card + $with). */
        public string $template = '',
        /** Extra classes applied to the outer <div>. Pass '' (default) to keep built-in classes. */
        public string $wrapperClass = '',
        /** Extra classes applied to the grid <div>. */
        public string $gridClass = '',
        /** Skip the default <div> wrappers entirely — caller provides its own container. */
        public bool $bare = false,
        public array $with = [],
    ) {
        $this->sectionModel = $articleService->findPublishedSectionBySlug($this->section);

        $this->articles = $articleService->getPublishedArticlesInSectionForWidget(
            sectionId: $this->sectionModel?->id,
            sort: $this->sort,
            limit: $this->limit,
        );
    }

    public function render(): View
    {
        return view('cms::components.widget.section-articles.section-articles');
    }
}
