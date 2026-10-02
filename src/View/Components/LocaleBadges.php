<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsTranslation;
use VmEngine\Cms\Support\CmsLocale;

/**
 * Small badges listing a model's secondary-locale translations (eager-load `translations`).
 */
class LocaleBadges extends Component
{
    /** @var list<string> */
    public array $locales = [];

    public function __construct(public Model $model)
    {
        $secondary = CmsLocale::secondary();

        if ($secondary !== []) {
            /** @var iterable<CmsTranslation> $translations */
            $translations = $model->getAttribute('translations') ?? [];
            foreach ($translations as $translation) {
                if (in_array($translation->locale, $secondary, true)) {
                    $this->locales[] = $translation->locale;
                }
            }
        }
    }

    public function shouldRender(): bool
    {
        return $this->locales !== [];
    }

    public function render(): View
    {
        return view('cms::components.locale-badges');
    }
}
