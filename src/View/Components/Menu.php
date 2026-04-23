<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Services\CmsMenuService;

class Menu extends Component
{
    /**
     * @param  array<string, mixed>  $with  Extra vars forwarded into the custom $template.
     */
    public function __construct(
        public string $position = 'header',
        /** Custom Blade view rendered instead of the default markup (receives $items, $position + $with). */
        public string $template = '',
        public array $with = [],
    ) {}

    public function render(): View
    {
        $service = app(CmsMenuService::class);
        $items = $service->buildTree($this->position);

        return view('cms::components.frontend.menu.menu', [
            'items' => $items,
            'position' => $this->position,
            'template' => $this->template,
            'with' => $this->with,
        ]);
    }
}
