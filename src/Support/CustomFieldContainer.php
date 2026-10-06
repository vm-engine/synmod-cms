<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use VmEngine\Cms\Models\CmsField;

/**
 * A container (root page, section, download category, album) that owns custom
 * field definitions (implemented via the DefinesCustomFields trait).
 */
interface CustomFieldContainer
{
    /**
     * @return MorphMany<CmsField, covariant Model>
     */
    public function customFields(): MorphMany;
}
