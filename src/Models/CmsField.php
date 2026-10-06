<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Enums\CmsFieldType;

/**
 * A dev-defined custom field, owned by a root page, section, download category or album.
 *
 * @property int $id
 * @property string $owner_type
 * @property int $owner_id
 * @property string $name
 * @property string $label
 * @property CmsFieldType $type
 * @property list<array{value: string, label: string}>|null $options
 * @property string|null $default
 * @property bool $required
 * @property string|null $help
 * @property bool $searchable
 * @property bool $filterable
 * @property bool $sortable
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CmsField extends Model
{
    protected $table = 'cms_fields';

    protected $fillable = [
        'owner_type', 'owner_id', 'name', 'label', 'type', 'options', 'default',
        'required', 'help', 'searchable', 'filterable', 'sortable', 'sort_order',
    ];

    protected $casts = [
        'type' => CmsFieldType::class,
        'options' => 'array',
        'required' => 'boolean',
        'searchable' => 'boolean',
        'filterable' => 'boolean',
        'sortable' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return HasMany<CmsFieldValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(CmsFieldValue::class, 'field_id');
    }

    /** `__key` → __('key'); anything else as-is. */
    public static function translateText(?string $text): string
    {
        $text = (string) $text;

        return str_starts_with($text, '__') ? (string) __(substr($text, 2)) : $text;
    }

    public function displayLabel(): string
    {
        return self::translateText($this->label) ?: $this->name;
    }

    public function displayHelp(): ?string
    {
        return $this->help ? self::translateText($this->help) : null;
    }

    /**
     * @return list<string>
     */
    public function optionValues(): array
    {
        return array_map('strval', array_column($this->options ?? [], 'value'));
    }

    public function optionLabel(string $value): string
    {
        foreach ($this->options ?? [] as $option) {
            if ((string) $option['value'] === $value) {
                return self::translateText($option['label']) ?: $value;
            }
        }

        return $value;
    }

    public function defaultValue(): mixed
    {
        if ($this->type->isMulti()) {
            return array_values(array_map('strval', (array) json_decode((string) $this->default, true)));
        }

        return $this->default === null || $this->default === '' ? null : $this->castValues([$this->default]);
    }

    /**
     * Stored raw strings (one per row) → the typed value.
     *
     * @param  list<string|null>  $raw
     */
    public function castValues(array $raw): mixed
    {
        if ($this->type->isMulti()) {
            return array_map('strval', $raw);
        }

        $value = $raw[0] ?? null;

        return match (true) {
            $value === null => null,
            $this->type === CmsFieldType::Number => is_numeric($value) ? $value + 0 : null,
            $this->type === CmsFieldType::Checkbox => $value === '1',
            default => (string) $value,
        };
    }

    /**
     * Searchable text for one value (option types → their labels).
     */
    public function displayText(mixed $value): string
    {
        $values = is_array($value) ? $value : [$value];

        return implode(' ', array_map(fn (mixed $v): string => match (true) {
            $this->type->hasOptions() => $this->optionLabel((string) $v),
            $this->type === CmsFieldType::Checkbox => '',
            default => (string) $v,
        }, $values));
    }

    /**
     * Plain-array form of the definition (form state for the schema manager / value inputs).
     *
     * @return array{id: int, name: string, label: string, type: string, options: list<array{value: string, label: string}>, default: mixed, required: bool, help: string, searchable: bool, filterable: bool, sortable: bool}
     */
    public function toDefinition(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'label' => $this->label,
            'type' => $this->type->value,
            'options' => $this->options ?? [],
            'default' => $this->type->isMulti() ? $this->defaultValue() : $this->default,
            'required' => $this->required,
            'help' => (string) $this->help,
            'searchable' => $this->searchable,
            'filterable' => $this->filterable,
            'sortable' => $this->sortable,
        ];
    }
}
