<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Models\CmsFieldValue;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\CustomFieldContainer;
use VmEngine\Cms\Support\CustomFieldContent;

/**
 * Single read/write path for CMS custom fields: definitions (cms_fields) on a
 * container, values (cms_field_values) on items, per locale.
 */
class CmsCustomFieldService
{
    /** @var array<string, Collection<int, CmsField>> */
    private array $schemaCache = [];

    /**
     * Definitions owned by a container (memoized per request).
     *
     * @return Collection<int, CmsField>
     */
    public function schemaOf((Model&CustomFieldContainer)|null $owner): Collection
    {
        if ($owner === null || ! $owner->exists) {
            return new Collection;
        }

        return $this->schemaCache[$owner->getMorphClass().':'.$owner->getKey()] ??= $owner->customFields()->get();
    }

    /**
     * Plain-array definitions (form state) of a schema.
     *
     * @param  Collection<int, CmsField>  $schema
     * @return list<array<string, mixed>>
     */
    public function definitions(Collection $schema): array
    {
        return array_values($schema->map(fn (CmsField $field): array => $field->toDefinition())->all());
    }

    /**
     * Definitions that apply to an item (via its schema owner).
     *
     * @return Collection<int, CmsField>
     */
    public function schemaFor(CustomFieldContent $item): Collection
    {
        return $this->schemaOf($item->fieldSchemaOwner());
    }

    /**
     * Validation rules for the schema repeater (dev only).
     *
     * @return array<string, mixed>
     */
    public function schemaRules(string $prefix = 'fieldSchema'): array
    {
        return [
            $prefix => 'array',
            "{$prefix}.*.name" => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', 'distinct'],
            "{$prefix}.*.label" => 'required|string|max:255',
            "{$prefix}.*.type" => ['required', Rule::enum(CmsFieldType::class)],
            "{$prefix}.*.options" => 'array',
            "{$prefix}.*.options.*.value" => 'required|string|max:191',
            "{$prefix}.*.options.*.label" => 'required|string|max:255',
            "{$prefix}.*.default" => 'nullable',
            "{$prefix}.*.help" => 'nullable|string|max:500',
            "{$prefix}.*.required" => 'boolean',
            "{$prefix}.*.searchable" => 'boolean',
            "{$prefix}.*.filterable" => 'boolean',
            "{$prefix}.*.sortable" => 'boolean',
        ];
    }

    /**
     * Replace a container's definitions with $definitions (list order = sort order).
     * Existing rows are matched by `id`, so a rename keeps values.
     *
     * @param  list<array<string, mixed>>  $definitions
     * @return bool whether the set of searchable fields changed (caller reindexes)
     */
    public function syncSchema(Model&CustomFieldContainer $owner, array $definitions): bool
    {
        if ($owner instanceof CmsPage && $owner->parent_id !== null) {
            throw new InvalidArgumentException('Only root pages can define custom fields.');
        }

        $changed = DB::transaction(function () use ($owner, $definitions): bool {
            $existing = $owner->customFields()->get()->keyBy('id');
            $searchableBefore = $existing->where('searchable', true)->keys()->sort()->values()->all();
            $keepIds = array_values(array_filter(array_map(fn (array $d): int => (int) ($d['id'] ?? 0), $definitions)));

            // Delete first so a freed name can be reused in the same save.
            $owner->customFields()->whereNotIn('id', $keepIds)->delete();

            foreach ($definitions as $index => $definition) {
                $attributes = $this->definitionAttributes($definition, $index);
                $field = $existing->get((int) ($definition['id'] ?? 0));

                if ($field === null) {
                    $owner->customFields()->create($attributes);

                    continue;
                }

                if ($field->type !== $attributes['type'] && $field->values()->exists()) {
                    throw ValidationException::withMessages(["fieldSchema.{$index}.type" => __('cms::labels.field_type_locked')]);
                }

                $field->update($attributes);
            }

            $searchableAfter = $owner->customFields()->where('searchable', true)->pluck('id')->sort()->values()->all();

            return $searchableBefore !== $searchableAfter;
        });

        $this->schemaCache = [];

        return $changed;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @return array<string, mixed>
     */
    private function definitionAttributes(array $definition, int $index): array
    {
        $type = CmsFieldType::from((string) $definition['type']);
        $options = $type->hasOptions() ? array_values((array) ($definition['options'] ?? [])) : null;

        if ($type->hasOptions() && $options === []) {
            throw ValidationException::withMessages(["fieldSchema.{$index}.options" => __('cms::labels.field_options_required')]);
        }

        $default = $definition['default'] ?? null;

        return [
            'name' => (string) $definition['name'],
            'label' => (string) $definition['label'],
            'type' => $type,
            'options' => $options,
            'default' => match (true) {
                $type->isMulti() => json_encode(array_values((array) $default)),
                $type === CmsFieldType::Checkbox => $default ? '1' : '0',
                $default === null || $default === '' => null,
                default => (string) $default,
            },
            'required' => (bool) ($definition['required'] ?? false),
            'help' => ($definition['help'] ?? '') ?: null,
            'searchable' => (bool) ($definition['searchable'] ?? false),
            'filterable' => (bool) ($definition['filterable'] ?? false),
            'sortable' => $type->isSortable() && (bool) ($definition['sortable'] ?? false),
            'sort_order' => $index,
        ];
    }

    /**
     * Replace all of an item's values. Fields outside the current schema are
     * dropped (e.g. after the item moved to another section).
     *
     * @param  array<string, array<string, mixed>>  $byLocale  [locale => [name => value]]
     */
    public function saveValues(Model&CustomFieldContent $item, array $byLocale): void
    {
        $rows = [];
        $now = now();

        foreach ($this->schemaFor($item) as $field) {
            foreach (CmsLocale::all() as $locale) {
                foreach ($this->normalize($field, $byLocale[$locale][$field->name] ?? null) as $raw) {
                    $rows[] = [
                        'field_id' => $field->id,
                        'valuable_type' => $item->getMorphClass(),
                        'valuable_id' => $item->getKey(),
                        'locale' => $locale,
                        'value' => $raw,
                        'value_index' => mb_substr($raw, 0, 191),
                        'value_number' => $field->type === CmsFieldType::Number ? $raw : null,
                        'value_date' => $field->type->typedColumn() === 'value_date' ? Carbon::parse($raw)->format('Y-m-d H:i:s') : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        DB::transaction(function () use ($item, $rows): void {
            $item->fieldValues()->delete();
            if ($rows !== []) {
                CmsFieldValue::insert($rows);
            }
        });

        $item->unsetRelation('fieldValues');
    }

    /**
     * Form input → stored raw strings (empty = no row = fall back).
     *
     * @return list<string>
     */
    private function normalize(CmsField $field, mixed $value): array
    {
        if ($field->type->isMulti()) {
            return array_values(array_unique(array_intersect(array_map('strval', (array) $value), $field->optionValues())));
        }

        if ($field->type === CmsFieldType::Checkbox) {
            return $value === null ? [] : [filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0'];
        }

        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? [] : [$value];
    }

    /**
     * Typed values for the given (default: current) locale, falling back to
     * the primary locale, then the field default.
     *
     * @return array<string, mixed>
     */
    public function values(Model&CustomFieldContent $item, ?string $locale = null): array
    {
        $locale ??= app()->getLocale();
        $primary = CmsLocale::primary();
        $rows = $item->exists ? $item->fieldValueRows()->groupBy('field_id') : collect();
        $values = [];

        foreach ($this->schemaFor($item) as $field) {
            $fieldRows = $rows->get($field->id, collect());
            $chosen = $fieldRows->where('locale', $locale);
            if ($chosen->isEmpty()) {
                $chosen = $fieldRows->where('locale', $primary);
            }

            $values[$field->name] = $chosen->isEmpty()
                ? $field->defaultValue()
                : $field->castValues($this->rawValues($chosen));
        }

        return $values;
    }

    /**
     * @param  Collection<int, CmsFieldValue>  $rows
     * @return list<string|null>
     */
    private function rawValues(Collection $rows): array
    {
        return array_values($rows->map(fn (CmsFieldValue $row): ?string => $row->value)->all());
    }

    /**
     * Form state [locale => [name => value]]: stored values (no fallback);
     * missing primary values get the field default.
     *
     * @param  Collection<int, CmsField>  $schema
     * @return array<string, array<string, mixed>>
     */
    public function formValues((Model&CustomFieldContent)|null $item, Collection $schema): array
    {
        $rows = $item?->exists ? $item->fieldValues()->get() : new Collection;
        $primary = CmsLocale::primary();
        $values = [];

        foreach (CmsLocale::all() as $locale) {
            foreach ($schema as $field) {
                $stored = $this->rawValues($rows->where('locale', $locale)->where('field_id', $field->id));

                $values[$locale][$field->name] = match (true) {
                    $stored !== [] => $field->castValues($stored),
                    $locale === $primary => $field->defaultValue(),
                    $field->type->isMulti() => [],
                    default => null,
                };
            }
        }

        return $values;
    }

    /**
     * Rules for `customFieldValues.{locale}.{name}` from definition arrays;
     * `required` applies to the primary locale only.
     *
     * @param  list<array<string, mixed>>  $schema
     * @return array<string, mixed>
     */
    public function valueRules(array $schema, string $prefix = 'customFieldValues'): array
    {
        $rules = [$prefix => 'array'];
        $primary = CmsLocale::primary();

        foreach ($schema as $definition) {
            $type = CmsFieldType::tryFrom((string) ($definition['type'] ?? ''));
            $name = (string) ($definition['name'] ?? '');
            if ($type === null || $name === '') {
                continue;
            }

            $optionValues = array_map('strval', array_column((array) ($definition['options'] ?? []), 'value'));

            foreach (CmsLocale::all() as $locale) {
                $required = $locale === $primary && ! empty($definition['required']) && $type !== CmsFieldType::Checkbox;
                $rules["{$prefix}.{$locale}.{$name}"] = [$required ? 'required' : 'nullable', ...$type->valueRules($optionValues)];

                if ($type->isMulti()) {
                    $rules["{$prefix}.{$locale}.{$name}.*"] = [Rule::in($optionValues)];
                }
            }
        }

        return $rules;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function applyWhere(Builder $query, string $name, string $operator, mixed $value): void
    {
        $column = match (true) {
            strtolower($operator) === 'like' => 'value',
            is_int($value) || is_float($value) => 'value_number',
            $value instanceof DateTimeInterface => 'value_date',
            default => 'value_index',
        };

        $value = $value instanceof DateTimeInterface ? Carbon::instance($value)->format('Y-m-d H:i:s') : $value;

        $query->whereExists(fn (QueryBuilder $sub) => $this->valueQuery($sub, $query->getModel(), $name)->where("cfv.{$column}", $operator, $value));
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  list<string>  $values
     */
    public function applyWhereIn(Builder $query, string $name, array $values): void
    {
        $query->whereExists(fn (QueryBuilder $sub) => $this->valueQuery($sub, $query->getModel(), $name)->whereIn('cfv.value_index', $values));
    }

    /**
     * One sub-select per typed column: only one is non-null for a given field,
     * so numbers and dates sort by type rather than as strings.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     */
    public function applyOrder(Builder $query, string $name, string $direction = 'asc'): void
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        foreach (['value_number', 'value_date', 'value_index'] as $column) {
            $query->orderBy(
                $this->valueQuery(DB::query(), $query->getModel(), $name)->select("cfv.{$column}")->limit(1),
                $direction,
            );
        }
    }

    /**
     * Rows of field $name for the outer model row, in the current locale or —
     * when that locale has no row — the primary locale.
     */
    private function valueQuery(QueryBuilder $sub, Model $model, string $name): QueryBuilder
    {
        $locale = app()->getLocale();

        return $sub->from('cms_field_values as cfv')
            ->join('cms_fields as cf', 'cf.id', '=', 'cfv.field_id')
            ->where('cf.name', $name)
            ->where('cfv.valuable_type', $model->getMorphClass())
            ->whereColumn('cfv.valuable_id', $model->getQualifiedKeyName())
            ->where(fn (QueryBuilder $q) => $q->where('cfv.locale', $locale)
                ->orWhere(fn (QueryBuilder $q) => $q->where('cfv.locale', CmsLocale::primary())
                    ->whereNotExists(fn (QueryBuilder $q) => $q->from('cms_field_values as cfv2')
                        ->whereColumn('cfv2.field_id', 'cfv.field_id')
                        ->whereColumn('cfv2.valuable_type', 'cfv.valuable_type')
                        ->whereColumn('cfv2.valuable_id', 'cfv.valuable_id')
                        ->where('cfv2.locale', $locale))));
    }

    /** Searchable field values of an item for one locale, space-joined. */
    public function searchText(Model $item, string $locale): string
    {
        if (! $item instanceof CustomFieldContent) {
            return '';
        }

        $schema = $this->schemaFor($item)->where('searchable', true);
        if ($schema->isEmpty()) {
            return '';
        }

        $values = $this->values($item, $locale);

        return trim(implode(' ', $schema->map(fn (CmsField $field): string => $field->displayText($values[$field->name] ?? null))->all()));
    }

    /** Re-sync routes + search rows of everything under a container. */
    public function reindexOwner(Model $owner): void
    {
        $fallback = app(CmsFallbackService::class);

        if ($owner instanceof CmsPage) {
            $fallback->syncPage($owner);
            $fallback->syncPageDescendants($owner);
        } elseif ($owner instanceof CmsSection) {
            $fallback->syncSectionArticles($owner);
        } elseif ($owner instanceof CmsAlbum) {
            $fallback->syncAlbum($owner);
        } elseif ($owner instanceof CmsDownload) {
            $fallback->syncDownload($owner);
        }
    }
}
