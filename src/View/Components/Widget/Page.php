<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components\Widget;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsPage;

class Page extends Component
{
    public ?CmsPage $page;

    /**
     * @param  array<string, mixed>  $with  Extra vars forwarded into the custom $template.
     */
    public function __construct(
        public string $slug = '',
        public bool $showTitle = true,
        public bool $showCover = true,
        /** Custom Blade view rendered instead of the default markup (receives $page + $with). */
        public string $template = '',
        /** Extra classes applied to the default outer <div>. */
        public string $wrapperClass = '',
        /** Skip the outer <div> wrapper. */
        public bool $bare = false,
        public array $with = [],
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
