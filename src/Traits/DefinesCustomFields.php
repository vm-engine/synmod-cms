<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use VmEngine\Cms\Models\CmsField;

/**
 * A container (root page, section, download category, album) that owns custom
 * field definitions. Values cascade from cms_fields at the database level.
 */
trait DefinesCustomFields
{
    public static function bootDefinesCustomFields(): void
    {
        static::deleted(function (Model $model): void {
            CmsField::where('owner_type', $model->getMorphClass())->where('owner_id', $model->getKey())->delete();
        });
    }

    /**
     * @return MorphMany<CmsField, $this>
     */
    public function customFields(): MorphMany
    {
        return $this->morphMany(CmsField::class, 'owner')->orderBy('sort_order')->orderBy('id');
    }
}
