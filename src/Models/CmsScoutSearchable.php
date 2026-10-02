<?php

declare(strict_types=1);

namespace VmEngine\Cms\Models;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Scout\Searchable as ScoutIndexable;

/**
 * Scout-indexed variant of CmsSearchable, same table. Only referenced when
 * CmsSearchable::usesScout() (synapps.modules.cms.search.engine = scout AND
 * laravel/scout installed), so this file is never autoloaded otherwise. Excluded from
 * PHPStan like synapse's ScoutSearchable.
 */
class CmsScoutSearchable extends CmsSearchable
{
    use ScoutIndexable;

    public function searchableAs(): string
    {
        return 'cms_searchables';
    }

    /**
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'searchable_type' => $this->searchable_type,
            'locale' => $this->locale,
            'title' => $this->title,
            'excerpt' => $this->excerpt,
            'content' => mb_substr((string) $this->content, 0, (int) config('synapps.modules.cms.search.scout_content_limit', 5000)),
        ];
    }

    /**
     * Engine-ranked results for one locale (filtered inside the engine).
     *
     * @return LengthAwarePaginator<int, CmsSearchable>
     */
    /**
     * @param  list<string>  $types
     */
    public static function searchVisible(string $query, string $locale, array $types, int $perPage): LengthAwarePaginator
    {
        // ponytail: publish-window filter runs on hydration (->query), so the
        // engine's total can overcount scheduled/expired rows; push the dates
        // into the engine's filters if that shows up.
        return static::search($query)
            ->where('locale', $locale)
            ->when($types !== [], fn ($search) => $search->whereIn('searchable_type', $types))
            ->query(fn (Builder $builder) => $builder->visible())
            ->paginate($perPage);
    }
}
