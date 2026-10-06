<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Throwable;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Services\CmsCustomFieldService;

/**
 * Applies `?f[name]=…&sort=name&dir=asc|desc` to a listing query. Only
 * filterable/sortable fields of the given schema are honoured; anything else
 * is ignored.
 */
final class CmsFieldFilter
{
    /**
     * @param  Collection<int, CmsField>  $schema
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly Collection $schema,
        private readonly array $filters = [],
        private readonly ?string $sort = null,
        private readonly string $direction = 'asc',
    ) {}

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public function apply(Builder $query): Builder
    {
        foreach ($this->schema->where('filterable', true) as $field) {
            $input = $this->filters[$field->name] ?? null;
            if ($input !== null && $input !== '' && $input !== []) {
                $this->applyField($query, $field, $input);
            }
        }

        if ($this->sortField() !== null) {
            app(CmsCustomFieldService::class)->applyOrder($query->reorder(), $this->sortField()->name, $this->direction());
        }

        return $query;
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applyField(Builder $query, CmsField $field, mixed $input): void
    {
        $service = app(CmsCustomFieldService::class);

        match ($field->type) {
            CmsFieldType::Text, CmsFieldType::Textarea, CmsFieldType::Email, CmsFieldType::Url => is_string($input)
                ? $service->applyWhere($query, $field->name, 'like', '%'.addcslashes($input, '%_\\').'%') : null,
            CmsFieldType::Select, CmsFieldType::Radio => is_string($input) && in_array($input, $field->optionValues(), true)
                ? $service->applyWhere($query, $field->name, '=', $input) : null,
            CmsFieldType::Checkbox => in_array($input, ['0', '1'], true) ? $service->applyWhere($query, $field->name, '=', $input) : null,
            CmsFieldType::Checkboxes => ($values = array_values(array_intersect((array) $input, $field->optionValues()))) !== []
                ? $service->applyWhereIn($query, $field->name, $values) : null,
            CmsFieldType::Number => $this->applyRange($query, $field->name, $input, fn ($v) => is_numeric($v) ? $v + 0 : null),
            CmsFieldType::Date, CmsFieldType::Datetime => $this->applyRange($query, $field->name, $input, fn ($v, string $bound) => $this->date($v, $bound, $field->type)),
        };
    }

    /**
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     */
    private function applyRange(Builder $query, string $name, mixed $input, callable $cast): void
    {
        foreach (['min' => '>=', 'max' => '<='] as $bound => $operator) {
            $value = is_array($input) && is_scalar($input[$bound] ?? null) ? $cast((string) $input[$bound], $bound) : null;
            if ($value !== null) {
                app(CmsCustomFieldService::class)->applyWhere($query, $name, $operator, $value);
            }
        }
    }

    private function date(string $value, string $bound, CmsFieldType $type): ?Carbon
    {
        try {
            $date = Carbon::parse($value);
        } catch (Throwable) {
            return null;
        }

        return $type === CmsFieldType::Date ? ($bound === 'min' ? $date->startOfDay() : $date->endOfDay()) : $date;
    }

    /** @return Collection<int, CmsField> */
    public function filterableFields(): Collection
    {
        return $this->schema->where('filterable', true)->values();
    }

    /** @return Collection<int, CmsField> */
    public function sortableFields(): Collection
    {
        return $this->schema->where('sortable', true)->values();
    }

    public function sortField(): ?CmsField
    {
        return $this->sort ? $this->sortableFields()->firstWhere('name', $this->sort) : null;
    }

    public function direction(): string
    {
        return $this->direction === 'desc' ? 'desc' : 'asc';
    }

    public function isActive(): bool
    {
        return $this->filterableFields()->isNotEmpty() || $this->sortableFields()->isNotEmpty();
    }
}
