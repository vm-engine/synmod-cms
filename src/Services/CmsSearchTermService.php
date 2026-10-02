<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsSearchTerm;

/**
 * Popular public search terms: recording (search page only), the cached
 * popular list, and the backend moderation actions.
 */
class CmsSearchTermService
{
    private const SESSION_KEY = 'cms_search_terms_recorded';

    public static function normalize(string $term): string
    {
        return Str::limit(Str::lower((string) preg_replace('/\s+/u', ' ', trim($term))), 100, '');
    }

    /**
     * Count a search once per session per locale+term.
     */
    public function record(string $term, string $locale): void
    {
        $term = self::normalize($term);
        $key = $locale.'|'.$term;
        /** @var list<string> $recorded */
        $recorded = session(self::SESSION_KEY, []);

        if ($term === '' || in_array($key, $recorded, true)) {
            return;
        }

        $row = CmsSearchTerm::firstOrCreate(['locale' => $locale, 'term' => $term]);
        $row->increment('hits', 1, ['last_searched_at' => now()]);

        session([self::SESSION_KEY => [...$recorded, $key]]);
    }

    /**
     * @return list<string>
     */
    public function popular(string $locale): array
    {
        return Cache::remember(self::cacheKey($locale), 600, fn (): array => array_values(CmsSearchTerm::query()
            ->where('locale', $locale)
            ->where('is_hidden', false)
            ->where('hits', '>=', (int) config('synapps.modules.cms.search.popular_min_hits', 3))
            ->orderByDesc('hits')
            ->orderBy('term')
            ->limit((int) config('synapps.modules.cms.search.popular_limit', 8))
            ->pluck('term')
            ->all()));
    }

    /**
     * @param  array{q?: string, locale?: string}  $filters
     * @return LengthAwarePaginator<int, CmsSearchTerm>
     */
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return CmsSearchTerm::query()
            ->when($filters['q'] ?? '', fn ($q, string $term) => $q->where('term', 'like', "%{$term}%"))
            ->when($filters['locale'] ?? '', fn ($q, string $locale) => $q->where('locale', $locale))
            ->orderByDesc('hits')
            ->orderBy('term')
            ->paginate($perPage);
    }

    public function toggleHidden(int $id): CmsSearchTerm
    {
        $term = CmsSearchTerm::findOrFail($id);
        $term->update(['is_hidden' => ! $term->is_hidden]);
        Cache::forget(self::cacheKey($term->locale));

        return $term;
    }

    public function delete(int $id): void
    {
        $term = CmsSearchTerm::findOrFail($id);
        $term->delete();
        Cache::forget(self::cacheKey($term->locale));
    }

    private static function cacheKey(string $locale): string
    {
        return "cms:search:popular:{$locale}";
    }
}
