<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Support\CmsLocale;

/**
 * Custom field value inputs, one tab per locale, bound to {model}.{locale}.{name}.
 */
class CustomFieldInputs extends Component
{
    public string $primary;

    /** @var list<string> */
    public array $locales;

    /**
     * @param  list<array<string, mixed>>  $schema  definition arrays
     */
    public function __construct(
        public array $schema = [],
        public string $model = 'form.customFieldValues',
    ) {
        $this->primary = CmsLocale::primary();
        $this->locales = CmsLocale::all();
        $this->schema = array_values(array_filter($schema, fn (array $d): bool => ($d['name'] ?? '') !== '' && CmsFieldType::tryFrom((string) ($d['type'] ?? '')) !== null));
    }

    public function shouldRender(): bool
    {
        return $this->schema !== [];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function type(array $definition): string
    {
        return (string) $definition['type'];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function label(array $definition): string
    {
        return CmsField::translateText((string) ($definition['label'] ?? '')) ?: (string) $definition['name'];
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function help(array $definition): string
    {
        return CmsField::translateText((string) ($definition['help'] ?? ''));
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return list<array{value: string, label: string}>
     */
    public function options(array $definition): array
    {
        return array_values(array_map(fn (array $o): array => ['value' => (string) $o['value'], 'label' => CmsField::translateText((string) $o['label']) ?: (string) $o['value']], (array) ($definition['options'] ?? [])));
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function isRequired(array $definition, string $locale): bool
    {
        return $locale === $this->primary && ! empty($definition['required']);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function path(string $locale, array $definition): string
    {
        return "{$this->model}.{$locale}.{$definition['name']}";
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function inputId(string $locale, array $definition): string
    {
        return str_replace('.', '-', $this->path($locale, $definition));
    }

    /**
     * flatpickr options (JSON) for a date/datetime input, matching the
     * stored formats (Y-m-d / Y-m-d H:i).
     *
     * @param  array<string, mixed>  $definition
     */
    public function datepickerConfig(array $definition): string
    {
        return (string) json_encode($definition['type'] === 'datetime'
            ? ['enableTime' => true, 'time_24hr' => true, 'dateFormat' => 'Y-m-d H:i']
            : ['dateFormat' => 'Y-m-d']);
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    public function htmlInputType(array $definition): string
    {
        return match ($definition['type']) {
            'number' => 'number',
            'email' => 'email',
            'url' => 'url',
            default => 'text',
        };
    }

    public function render(): View
    {
        return view('cms::components.custom-field-inputs');
    }
}
