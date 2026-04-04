<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsPage;

class Page extends Component
{
    public ?CmsPage $page;

    public function __construct(
        public string $slug = '',
        public bool $showTitle = true,
        public bool $showCover = true,
    ) {
        $this->page = CmsPage::where('full_slug', $this->slug)
            ->published()
            ->first();
    }

    public function render(): View
    {
        return view('cms::components.widget.page.page');
    }
}
