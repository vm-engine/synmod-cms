<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\CmsLocale;

/**
 * One input (text, textarea or rich-text editor) per secondary locale, bound
 * to {model}.{locale}.{field} and shown by the enclosing cmsLocaleTabs scope.
 */
class TranslationInput extends Component
{
    /** @var list<string> */
    public array $locales;

    /**
     * @param  array<string, array<string, string>>  $values  the form's translations (seeds the RTE)
     */
    public function __construct(
        public string $field,
        public string $label,
        public string $type = 'text',
        public string $placeholder = '',
        public array $values = [],
        public int $rows = 3,
        public int $height = 400,
        public string $model = 'form.translations',
    ) {
        $this->locales = CmsLocale::secondary();
    }

    public function shouldRender(): bool
    {
        return $this->locales !== [];
    }

    public function render(): View
    {
        return view('cms::components.translation-input');
    }
}
