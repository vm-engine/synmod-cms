<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\CmsUrl;

/**
 * Frontend language switcher (no JS). Customize via BEM CSS + attributes,
 * props (variant/display/trigger/hideCurrent/globeIcon), a custom `template`
 * view (receives $languages, $current, $display + $with), or
 * CmsUrl::alternates() directly.
 */
class LanguageSwitcher extends Component
{
    /** @var list<array{code: string, name: string, url: string, absolute: string, active: bool, icon: array{type: 'image'|'class'|'emoji', value: string}|null}> */
    public array $languages;

    /** @var array{code: string, name: string, url: string, absolute: string, active: bool, icon: array{type: 'image'|'class'|'emoji', value: string}|null}|null */
    public ?array $current;

    /**
     * @param  'dropdown'|'list'  $variant
     * @param  'name'|'code'|'both'|'icon'|'icon-name'|'icon-code'  $display
     * @param  'current'|'globe'  $trigger
     * @param  array<string, mixed>  $with  extra vars for a custom template
     */
    public function __construct(
        public string $variant = 'dropdown',
        public string $display = 'name',
        public string $trigger = 'current',
        public bool $hideCurrent = false,
        public string $globeIcon = 'ph ph-globe',
        public string $wrapperClass = '',
        public string $template = '',
        public array $with = [],
    ) {
        $this->languages = CmsUrl::alternates();
        $this->current = null;

        foreach ($this->languages as $language) {
            if ($language['active']) {
                $this->current = $language;
            }
        }
    }

    public function shouldRender(): bool
    {
        return $this->languages !== [];
    }

    /**
     * @return list<array{code: string, name: string, url: string, absolute: string, active: bool, icon: array{type: 'image'|'class'|'emoji', value: string}|null}>
     */
    public function items(): array
    {
        return $this->hideCurrent
            ? array_values(array_filter($this->languages, fn (array $language): bool => ! $language['active']))
            : $this->languages;
    }

    public function render(): View
    {
        return view('cms::components.language-switcher');
    }
}
