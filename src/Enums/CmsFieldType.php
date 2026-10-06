<?php

declare(strict_types=1);

namespace VmEngine\Cms\Enums;

use Illuminate\Validation\Rule;

enum CmsFieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Email = 'email';
    case Url = 'url';
    case Date = 'date';
    case Datetime = 'datetime';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Checkboxes = 'checkboxes';

    public function label(): string
    {
        return (string) __('cms::labels.field_type_'.$this->value);
    }

    public function hasOptions(): bool
    {
        return in_array($this, [self::Select, self::Radio, self::Checkboxes], true);
    }

    public function isMulti(): bool
    {
        return $this === self::Checkboxes;
    }

    public function isSortable(): bool
    {
        return $this !== self::Checkboxes;
    }

    /** Typed index column the value is mirrored into (null = value_index only). */
    public function typedColumn(): ?string
    {
        return match ($this) {
            self::Number => 'value_number',
            self::Date, self::Datetime => 'value_date',
            default => null,
        };
    }

    /**
     * Validation for one value (for Checkboxes: the array; items are checked separately).
     *
     * @param  list<string>  $optionValues
     * @return list<mixed>
     */
    public function valueRules(array $optionValues): array
    {
        return match ($this) {
            self::Text => ['string', 'max:255'],
            self::Textarea => ['string', 'max:10000'],
            self::Number => ['numeric'],
            self::Email => ['email', 'max:255'],
            self::Url => ['url', 'max:2048'],
            self::Date => ['date_format:Y-m-d'],
            self::Datetime => ['date'],
            self::Select, self::Radio => [Rule::in($optionValues)],
            self::Checkbox => ['boolean'],
            self::Checkboxes => ['array'],
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $type): array => ['value' => $type->value, 'label' => $type->label()], self::cases());
    }
}
