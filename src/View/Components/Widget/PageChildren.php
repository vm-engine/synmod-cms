<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsPage;

class PageChildren extends Component
{
    public ?CmsPage $page;

    /** @var Collection<int, CmsPage> */
    public Collection $children;

    public function __construct(
        public string $slug = '',
        public string $layout = 'grid',
        public int $limit = 12,
        public bool $showTitle = true,
        public bool $showCover = true,
        public bool $showExcerpt = true,
        public bool $recursive = false,
    ) {
        $this->page = CmsPage::where('full_slug', $this->slug)->published()->first();

        /** @var Collection<int, CmsPage> $children */
        $children = new Collection;

        if ($this->page) {
            if ($this->recursive) {
                $children = CmsPage::where('full_slug', 'like', $this->page->full_slug.'/%')
                    ->where('status', 'published')
                    ->where(function ($q): void {
                        $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                    })
                    ->orderBy('sort_order')
                    ->limit($this->limit)
                    ->get();
            } else {
                /** @var Collection<int, CmsPage> $children */
                $children = $this->page->children()
                    ->where('status', 'published')
                    ->where(function ($q): void {
                        $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                    })
                    ->orderBy('sort_order')
                    ->limit($this->limit)
                    ->get();
            }
        }

        $this->children = $children;
    }

    public function render(): View
    {
        return view('cms::components.widget.page-children.page-children');
    }
}
