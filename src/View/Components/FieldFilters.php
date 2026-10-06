<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Support\CmsFieldFilter;

/**
 * Default GET filter/sort bar for a listing. Themes can override the view
 * `cms::components.field-filters` or build their own form on `?f[...]`.
 */
class FieldFilters extends Component
{
    public function __construct(public ?CmsFieldFilter $filter = null) {}

    public function shouldRender(): bool
    {
        return $this->filter?->isActive() ?? false;
    }

    public function isRange(CmsField $field): bool
    {
        return in_array($field->type, [CmsFieldType::Number, CmsFieldType::Date, CmsFieldType::Datetime], true);
    }

    public function isDate(CmsField $field): bool
    {
        return in_array($field->type, [CmsFieldType::Date, CmsFieldType::Datetime], true);
    }

    /** Livewire path of a filter input on the listing's #[Url] $f. */
    public function model(CmsField $field, ?string $bound = null): string
    {
        return 'f.'.$field->name.($bound !== null ? '.'.$bound : '');
    }

    /** flatpickr options (JSON) for a date/datetime range input. */
    public function datepickerConfig(CmsField $field): string
    {
        return (string) json_encode($field->type === CmsFieldType::Datetime
            ? ['enableTime' => true, 'time_24hr' => true, 'dateFormat' => 'Y-m-d H:i']
            : ['dateFormat' => 'Y-m-d']);
    }

    public function isChoice(CmsField $field): bool
    {
        return in_array($field->type, [CmsFieldType::Select, CmsFieldType::Radio, CmsFieldType::Checkbox], true);
    }

    public function isMulti(CmsField $field): bool
    {
        return $field->type === CmsFieldType::Checkboxes;
    }

    public function inputType(CmsField $field): string
    {
        return match ($field->type) {
            CmsFieldType::Number => 'number',
            default => 'text',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function choices(CmsField $field): array
    {
        if ($field->type === CmsFieldType::Checkbox) {
            return [['value' => '1', 'label' => __('cms::labels.filter_yes')], ['value' => '0', 'label' => __('cms::labels.filter_no')]];
        }

        return array_map(fn (string $value): array => ['value' => $value, 'label' => $field->optionLabel($value)], $field->optionValues());
    }

    public function render(): View
    {
        return view('cms::components.field-filters');
    }
}
