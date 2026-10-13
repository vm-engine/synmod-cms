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
     * The CMS toolbar: everything here survives the cms_rte purifier. The file-manager
     * buttons are added by the "cms-fm" extension (resources/js/cms.js).
     *
     * @return list<string>
     */
    public function buttons(): array
    {
        return ['undo', 'redo', '|', 'paragraph', '|', 'bold', 'italic', '|', 'left', 'center', 'right', '|', 'ul', 'ol', '|', 'link'];
    }

    /**
     * Config handed to the "cms-fm" editor extension.
     *
     * @return array{pickerKey: string, labels: array{image: string, file: string, video: string}}
     */
    public function extensionConfig(): array
    {
        return [
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
