<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use VmEngine\Cms\Models\CmsFieldValue;

/**
 * Content that stores values for its container's custom fields
 * (implemented via the HasCustomFields trait).
 */
interface CustomFieldContent
{
    /** The container whose custom field definitions apply to this item. */
    public function fieldSchemaOwner(): (Model&CustomFieldContainer)|null;

    /**
     * @return MorphMany<CmsFieldValue, covariant Model>
     */
    public function fieldValues(): MorphMany;

    /**
     * Stored value rows (eager-loadable `fieldValues` relation).
     *
     * @return Collection<int, CmsFieldValue>
     */
    public function fieldValueRows(): Collection;

    public function customFieldsSaved(): void;
}
