<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Tinymce extends Component
{
    public function __construct(
        public string $name,
        public string $value = '',
        public int $height = 400,
        public string $toolbar = 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image | code',
        public string $plugins = 'link image code lists table media',
    ) {}

    public function render(): View
    {
        // @phpstan-ignore argument.type
        return view('cms::components.tinymce');
    }
}
