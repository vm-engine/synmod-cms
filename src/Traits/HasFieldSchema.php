<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Cms\Support\CustomFieldContainer;

/**
 * Field-definition state for a container form object, edited by the
 * dev-only field-schema-manager. Non-devs can never change the schema:
 * rules and saves are skipped, and value inputs follow the stored schema.
 */
trait HasFieldSchema
{
    /** @var list<array<string, mixed>> */
    public array $fieldSchema = [];

    protected function fillFieldSchema(Model&CustomFieldContainer $owner): void
    {
        $service = app(CmsCustomFieldService::class);
        $this->fieldSchema = $service->definitions($service->schemaOf($owner));
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldSchemaRules(): array
    {
        return is_dev() ? app(CmsCustomFieldService::class)->schemaRules() : [];
    }

    protected function saveFieldSchema(Model&CustomFieldContainer $owner): void
    {
        if (! is_dev()) {
            return;
        }

        $this->normalizeSchemaDefaults();

        $service = app(CmsCustomFieldService::class);
        if ($service->syncSchema($owner, $this->fieldSchema)) {
            $service->reindexOwner($owner);
        }
    }

    /** Checkbox-list defaults are typed as "a, b" in the schema manager. */
    protected function normalizeSchemaDefaults(): void
    {
        foreach ($this->fieldSchema as $i => $definition) {
            if (($definition['type'] ?? '') === 'checkboxes' && is_string($definition['default'] ?? null)) {
                $this->fieldSchema[$i]['default'] = array_values(array_filter(array_map('trim', explode(',', $definition['default']))));
            }
        }
    }
}
