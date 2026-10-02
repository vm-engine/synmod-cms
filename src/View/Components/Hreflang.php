<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\CmsUrl;

/**
 * <link rel="alternate" hreflang> tags for the layout <head>, plus x-default
 * (the primary locale). Renders nothing when the page has no alternates.
 */
class Hreflang extends Component
{
    /** @var list<array{code: string, name: string, url: string, absolute: string, active: bool, icon: array{type: 'image'|'class'|'emoji', value: string}|null}> */
    public array $languages;

    public string $default = '';

    public function __construct()
    {
        $this->languages = CmsUrl::alternates();
        $primary = CmsLocale::primary();

        foreach ($this->languages as $language) {
            if ($language['code'] === $primary) {
                $this->default = $language['absolute'];
            }
        }
    }

    public function shouldRender(): bool
    {
        return $this->languages !== [];
    }

    public function render(): View
    {
        return view('cms::components.hreflang');
    }
}
