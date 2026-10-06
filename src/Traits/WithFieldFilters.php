<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Url;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Cms\Support\CmsFieldFilter;
use VmEngine\Cms\Support\CustomFieldContainer;

/**
 * Custom field filter/sort state for a frontend listing component, kept in
 * the URL (`?f[...]&sort=&dir=`) and edited by `<x-cms::field-filters>`.
 */
trait WithFieldFilters
{
    /** @var array<string, mixed> */
    #[Url]
    public array $f = [];

    #[Url]
    public string $sort = '';

    #[Url]
    public string $dir = 'asc';

    /** Filter bar submit: the listing re-renders with the bound values. */
    public function applyFieldFilters(): void
    {
        $this->resetListingPage();
    }

    public function resetFieldFilters(): void
    {
        $this->f = [];
        $this->sort = '';
        $this->dir = 'asc';
        $this->resetListingPage();
    }

    protected function fieldFilter((Model&CustomFieldContainer)|null $owner): CmsFieldFilter
    {
        $schema = app(CmsCustomFieldService::class)->schemaOf($owner);

        // The datepicker entangles f.{name}.min/max, which must exist on the component.
        foreach ($schema->where('filterable', true) as $field) {
            if (in_array($field->type, [CmsFieldType::Date, CmsFieldType::Datetime], true)) {
                $current = $this->f[$field->name] ?? null;
                $this->f[$field->name] = ['min' => null, 'max' => null, ...(is_array($current) ? $current : [])];
            }
        }

        return new CmsFieldFilter($schema, $this->f, $this->sort ?: null, $this->dir);
    }

    private function resetListingPage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
