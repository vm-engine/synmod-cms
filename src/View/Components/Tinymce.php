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
        public string $toolbar = 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image media | code',
        public string $plugins = 'link image code lists table media',
    ) {}

    public function render(): View
    {
        return view('cms::components.tinymce');
    }
}
