<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\CmsLocale;

/**
 * Language tab bar for a translatable form. Must sit inside an element with
 * x-data="cmsLocaleTabs('<primary>')" (set on the <form>).
 */
class LocaleTabs extends Component
{
    public string $primary;

    /** @var list<string> */
    public array $locales;

    /**
     * @param  array<string, array<string, string>>  $values  the form's translations, for the filled marker
     */
    public function __construct(
        public array $values = [],
        public string $model = 'form.translations',
    ) {
        $this->primary = CmsLocale::primary();
        $this->locales = CmsLocale::all();
    }

    public function shouldRender(): bool
    {
        return CmsLocale::secondary() !== [];
    }

    public function isFilled(string $locale): bool
    {
        return array_filter($this->values[$locale] ?? [], fn (string $value): bool => trim($value) !== '') !== [];
    }

    public function render(): View
    {
        return view('cms::components.locale-tabs');
    }
}
