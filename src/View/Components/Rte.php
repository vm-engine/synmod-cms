<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Enums\RteDriver;

/**
 * Renders the rich-text editor chosen in CMS settings (<x-cms::jodit> or <x-cms::tinymce>).
 */
class Rte extends Component
{
    public string $driver;

    public function __construct(
        public string $name,
        public string $value = '',
        public int $height = 400,
    ) {
        $this->driver = RteDriver::current()->value;
    }

    public function render(): View
    {
        return view('cms::components.rte');
    }
}
