<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Jodit extends Component
{
    public function __construct(
        public string $name,
        public string $value = '',
        public int $height = 400,
    ) {}

    /**
     * Config handed to the cmsJodit Alpine component (see resources/js/cms.js).
     *
     * @return array{field: string, height: int, pickerKey: string, labels: array{image: string, file: string, video: string}}
     */
    public function editorConfig(): array
    {
        return [
            'field' => $this->name,
            'height' => $this->height,
            'pickerKey' => 'jodit-'.$this->name,
            'labels' => [
                'image' => (string) __('cms::labels.rte_insert_image'),
                'file' => (string) __('cms::labels.rte_insert_file'),
                'video' => (string) __('cms::labels.rte_insert_video'),
            ],
        ];
    }

    public function render(): View
    {
        return view('cms::components.jodit');
    }
}
