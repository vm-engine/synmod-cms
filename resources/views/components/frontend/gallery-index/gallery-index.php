<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;

new class extends Component
{
    public function render()
    {
        $albums = CmsAlbum::with('images')->active()->normal()->orderBy('sort_order')->get();

        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.gallery-index.gallery-index', ['albums' => $albums])
            ->title(__('cms::labels.gallery'))
            ->layout($layout);
    }
};
