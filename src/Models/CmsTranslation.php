<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * One non-primary-locale translation of a CMS content model.
 *
 * Column names mirror the base content columns so a model's translatable
 * field maps 1:1 onto this table.
 *
 * @property int $id
 * @property string $translatable_type
 * @property int $translatable_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $label
 * @property string|null $excerpt
 * @property string|null $description
 * @property string|null $caption
 * @property string|null $body
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class CmsTranslation extends Model
{
    /** Every column a model may declare as translatable. */
    public const FIELDS = ['title', 'label', 'excerpt', 'description', 'caption', 'body', 'meta_title', 'meta_description'];

    protected $table = 'cms_translations';

    protected $fillable = ['translatable_type', 'translatable_id', 'locale', ...self::FIELDS];

    /**
     * @return MorphTo<Model, $this>
     */
    public function translatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Delete translations whose owner row is gone (DB-level cascades fire no
     * model events) or whose owner class no longer exists.
     */
    public static function pruneOrphans(): int
    {
        $deleted = 0;

        foreach (self::query()->distinct()->pluck('translatable_type') as $type) {
            $rows = self::query()->where('translatable_type', $type);

            if (! is_string($type) || ! is_subclass_of($type, Model::class)) {
                $deleted += $rows->delete();

                continue;
            }

            $owner = new $type;

            $deleted += $rows
                ->whereNotIn('translatable_id', $owner->newQuery()->select($owner->getKeyName()))
                ->delete();
        }

        return $deleted;
    }
}
