<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\Locked;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\CustomFieldContent;

/**
 * Custom field value state for a form object (or manager component):
 * inputs bind to `customFieldValues.{locale}.{name}`.
 */
trait HasCustomFieldValues
{
    /**
     * Definitions loaded from the item's schema owner. Locked: the value rules
     * are built from it, so a client must not be able to empty or change it.
     *
     * @var list<array<string, mixed>>
     */
    #[Locked]
    public array $customFieldSchema = [];

    /** @var array<string, array<string, mixed>> */
    public array $customFieldValues = [];

    /**
     * Load schema + values for $item (may be unsaved, e.g. a new article with
     * section_id set). $valuesFrom = the saved item whose stored values to show.
     */
    public function fillCustomFields(CustomFieldContent $item, (Model&CustomFieldContent)|null $valuesFrom = null): void
    {
        $service = app(CmsCustomFieldService::class);
        $schema = $service->schemaFor($item);

        $this->customFieldSchema = $service->definitions($schema);
        $this->customFieldValues = $service->formValues($valuesFrom, $schema);
    }

    /**
     * Definitions the value inputs and rules follow. Container forms override
     * this to follow the dev's live (unsaved) schema.
     *
     * @return list<array<string, mixed>>
     */
    public function inputFieldSchema(): array
    {
        return $this->customFieldSchema;
    }

    /**
     * Give every field of the input schema a value of the right shape in
     * every locale, so inputs that entangle their path (the datepicker) find
     * it and checkbox groups bind an array — needed when a dev adds fields or
     * changes a type live on a container form.
     */
    public function ensureCustomFieldValueKeys(): void
    {
        foreach ($this->inputFieldSchema() as $definition) {
            $name = (string) ($definition['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $isMulti = ($definition['type'] ?? '') === 'checkboxes';

            foreach (CmsLocale::all() as $locale) {
                $value = $this->customFieldValues[$locale][$name] ?? null;

                // Missing, or the wrong shape after a live type change (a checkbox group needs an array).
                if (! array_key_exists($name, $this->customFieldValues[$locale] ?? []) || $isMulti !== is_array($value)) {
                    $this->customFieldValues[$locale][$name] = $isMulti ? [] : null;
                }
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function customFieldRules(): array
    {
        return app(CmsCustomFieldService::class)->valueRules($this->inputFieldSchema());
    }

    /**
     * Name value inputs by their field label ("Price", "Price (ID)") in
     * validation messages instead of the raw key.
     *
     * @return array<string, string>
     */
    public function customFieldAttributes(): array
    {
        $attributes = [];
        $primary = CmsLocale::primary();

        foreach ($this->inputFieldSchema() as $definition) {
            $name = (string) ($definition['name'] ?? '');
            $label = CmsField::translateText((string) ($definition['label'] ?? '')) ?: $name;

            foreach (CmsLocale::all() as $locale) {
                $attributes["customFieldValues.{$locale}.{$name}"] = $locale === $primary ? $label : $label.' ('.strtoupper($locale).')';
            }
        }

        return $attributes;
    }

    protected function saveCustomFields(Model&CustomFieldContent $item): void
    {
        app(CmsCustomFieldService::class)->saveValues($item, $this->customFieldValues);
        $item->customFieldsSaved();
    }
}
