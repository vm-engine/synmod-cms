<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $field_id
 * @property string $valuable_type
 * @property int $valuable_id
 * @property string $locale
 * @property string|null $value
 * @property string|null $value_index
 * @property string|null $value_number
 * @property string|null $value_date
 */
class CmsFieldValue extends Model
{
    protected $table = 'cms_field_values';

    protected $fillable = ['field_id', 'valuable_type', 'valuable_id', 'locale', 'value', 'value_index', 'value_number', 'value_date'];

    /**
     * @return BelongsTo<CmsField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(CmsField::class, 'field_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function valuable(): MorphTo
    {
        return $this->morphTo();
    }
}
