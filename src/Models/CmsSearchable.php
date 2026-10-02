<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One public search entry for one locale. Derived data: rebuilt by
 * CmsSearchIndexer, never edited directly.
 *
 * @property int $id
 * @property string $searchable_type page|article|category|album|download|album_item|download_file
 * @property int $searchable_id
 * @property string $locale
 * @property string $title
 * @property string|null $excerpt
 * @property string|null $content
 * @property string $url path incl. locale prefix and #anchor for items
 * @property string|null $image
 * @property string|null $parent_label
 * @property int|null $parent_id
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 */
class CmsSearchable extends Model
{
    protected $table = 'cms_searchables';

    protected $fillable = [
        'searchable_type', 'searchable_id', 'locale', 'title', 'excerpt', 'content',
        'url', 'image', 'parent_label', 'parent_id', 'published_at', 'expires_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Scout when configured AND installed; the database otherwise.
     */
    public static function usesScout(): bool
    {
        return config('synapps.modules.cms.search.engine') === 'scout' && trait_exists('Laravel\Scout\Searchable');
    }

    /**
     * The class to write rows through, so Scout's model events see them.
     *
     * @return class-string<CmsSearchable>
     */
    public static function modelClass(): string
    {
        return self::usesScout() ? CmsScoutSearchable::class : self::class;
    }

    /**
     * @param  Builder<CmsSearchable>  $builder
     */
    #[Scope]
    protected function visible(Builder $builder): void
    {
        $builder
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
