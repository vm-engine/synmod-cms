<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsPageService;

class PageChildren extends Component
{
    public ?CmsPage $page;

    /** @var Collection<int, CmsPage> */
    public Collection $children;

    public function __construct(
        CmsPageService $pageService,
        public string $slug = '',
        public string $layout = 'grid',
        public int $limit = 12,
        public bool $showTitle = true,
        public bool $showCover = true,
        public bool $showExcerpt = true,
        public bool $recursive = false,
    ) {
        $this->page = $pageService->findPublishedByFullSlug($this->slug);

        /** @var Collection<int, CmsPage> $children */
        $children = new Collection;

        if ($this->page) {
            $children = $this->recursive
                ? $pageService->getPublishedDescendants($this->page, $this->limit)
                : $pageService->getPublishedChildren($this->page, $this->limit);
        }

        $this->children = $children;
    }

    public function render(): View
    {
        return view('cms::components.widget.page-children.page-children');
    }
}
