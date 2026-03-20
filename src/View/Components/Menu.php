<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Services\CmsMenuService;

class Menu extends Component
{
    public function __construct(
        public string $position = 'header',
    ) {}

    public function render(): View
    {
        $service = app(CmsMenuService::class);
        $items = $service->buildTree($this->position);

        // @phpstan-ignore argument.type
        return view('cms::components.frontend.menu.menu', ['items' => $items, 'position' => $this->position]);
    }
}
