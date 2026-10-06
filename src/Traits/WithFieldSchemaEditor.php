<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Livewire\Attributes\Computed;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsFieldValue;

/**
 * Actions and view helpers for the dev-only custom field definitions card
 * (`cms::components.backend.partials._field-schema-manager`) on a container
 * form whose form object uses HasFieldSchema. Lives on the form component
 * itself so edits and validation errors share one state and error bag.
 */
trait WithFieldSchemaEditor
{
    /** Livewire trait hook: value keys for fields defined live (before every render). */
    public function renderingWithFieldSchemaEditor(): void
    {
        $this->form->ensureCustomFieldValueKeys();
    }

    public function addField(): void
    {
        abort_unless(is_dev(), 403);

        $this->form->fieldSchema[] = [
            'id' => null, 'name' => '', 'label' => '', 'type' => CmsFieldType::Text->value, 'options' => [],
            'default' => null, 'required' => false, 'help' => '', 'searchable' => false, 'filterable' => false, 'sortable' => false,
        ];
    }

    public function removeField(int $index): void
    {
        abort_unless(is_dev(), 403);

        array_splice($this->form->fieldSchema, $index, 1);
    }

    public function moveField(int $index, int $offset): void
    {
        abort_unless(is_dev(), 403);

        $target = $index + $offset;
        if (isset($this->form->fieldSchema[$index], $this->form->fieldSchema[$target])) {
            [$this->form->fieldSchema[$index], $this->form->fieldSchema[$target]] = [$this->form->fieldSchema[$target], $this->form->fieldSchema[$index]];
        }
    }

    public function addOption(int $index): void
    {
        abort_unless(is_dev(), 403);

        if (isset($this->form->fieldSchema[$index])) {
            $this->form->fieldSchema[$index]['options'][] = ['value' => '', 'label' => ''];
        }
    }

    public function removeOption(int $index, int $optionIndex): void
    {
        abort_unless(is_dev(), 403);

        if (isset($this->form->fieldSchema[$index]['options'])) {
            array_splice($this->form->fieldSchema[$index]['options'], $optionIndex, 1);
        }
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    #[Computed]
    public function fieldTypeOptions(): array
    {
        return CmsFieldType::options();
    }

    /**
     * Saved fields that already have values (their type select is disabled).
     *
     * @return list<int>
     */
    #[Computed]
    public function lockedFieldIds(): array
    {
        $ids = array_values(array_filter(array_map(fn (array $field): int => (int) ($field['id'] ?? 0), $this->form->fieldSchema)));

        return $ids === [] ? [] : array_values(CmsFieldValue::whereIn('field_id', $ids)->distinct()->pluck('field_id')->map(fn (mixed $id): int => (int) $id)->all());
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public function isFieldLocked(array $field): bool
    {
        return in_array((int) ($field['id'] ?? 0), $this->lockedFieldIds, true);
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public function fieldHasOptions(array $field): bool
    {
        return CmsFieldType::tryFrom((string) ($field['type'] ?? ''))?->hasOptions() ?? false;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    public function fieldIsSortable(array $field): bool
    {
        return CmsFieldType::tryFrom((string) ($field['type'] ?? ''))?->isSortable() ?? false;
    }
}
