<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use VmEngine\Cms\Models\CmsFieldValue;
use VmEngine\Cms\Services\CmsCustomFieldService;

/**
 * Content that stores values for the custom fields of its schema owner.
 */
trait HasCustomFields
{
    public static function bootHasCustomFields(): void
    {
        // Also sweeps rows of DB-cascaded siblings (e.g. child pages), which fire no events.
        static::deleted(function (Model $model): void {
            CmsFieldValue::where('valuable_type', $model->getMorphClass())
                ->whereNotIn('valuable_id', $model->newQueryWithoutScopes()->select($model->getKeyName()))
                ->delete();
        });
    }

    /**
     * @return MorphMany<CmsFieldValue, $this>
     */
    public function fieldValues(): MorphMany
    {
        return $this->morphMany(CmsFieldValue::class, 'valuable');
    }

    /**
     * @return Collection<int, CmsFieldValue>
     */
    public function fieldValueRows(): Collection
    {
        return $this->fieldValues;
    }

    /**
     * Re-run the updated observers (fallback route + search index) after a
     * values-only change, which leaves the base row clean.
     */
    public function customFieldsSaved(): void
    {
        if ($this->exists) {
            $this->touchQuietly();
            $this->fireModelEvent('updated', false);
        }
    }

    /** One custom field value (current locale → primary → default). */
    public function field(string $name, ?string $locale = null): mixed
    {
        return app(CmsCustomFieldService::class)->values($this, $locale)[$name] ?? null;
    }

    /**
     * All custom field values keyed by name.
     *
     * @return array<string, mixed>
     */
    public function fields(?string $locale = null): array
    {
        return app(CmsCustomFieldService::class)->values($this, $locale);
    }

    /**
     * whereField('price', '<', 100) / whereField('colour', 'g'). Pass int|float
     * for number fields and a DateTimeInterface for date fields; 'like' matches text.
     *
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function whereField(Builder $builder, string $name, mixed $operator, mixed $value = null): void
    {
        [$operator, $value] = func_num_args() === 3 ? ['=', $operator] : [(string) $operator, $value];

        app(CmsCustomFieldService::class)->applyWhere($builder, $name, $operator, $value);
    }

    /**
     * Any of $values (checkboxes/select).
     *
     * @param  Builder<static>  $builder
     * @param  list<string>  $values
     */
    #[Scope]
    protected function whereFieldIn(Builder $builder, string $name, array $values): void
    {
        app(CmsCustomFieldService::class)->applyWhereIn($builder, $name, $values);
    }

    /**
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function orderByField(Builder $builder, string $name, string $direction = 'asc'): void
    {
        app(CmsCustomFieldService::class)->applyOrder($builder, $name, $direction);
    }
}
