<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Services\CmsMenuService;

new class extends Component
{
    public string $position = 'header';

    public function mount(string $position = 'header'): void
    {
        $this->position = $position;
    }

    public function render()
    {
        $service = app(CmsMenuService::class);
        $items = $service->buildTree($this->position);

        // The view is shared with <x-cms::menu>, which also passes a custom template + vars.
        return $this->view(['items' => $items, 'template' => '', 'with' => []]);
    }
};
