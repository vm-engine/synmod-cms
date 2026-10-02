<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use VmEngine\Cms\Models\CmsScoutSearchable;
use VmEngine\Cms\Models\CmsSearchable;

/**
 * Read path of the public search index: visible rows of one locale.
 */
class CmsSearchService
{
    /** Result tabs => the searchable types they cover. */
    public const FILTERS = [
        'page' => ['page'],
        'article' => ['article'],
        'category' => ['category'],
        'album' => ['album', 'album_item'],
        'download' => ['download', 'download_file'],
    ];

    /**
     * @param  string|list<string>|null  $type
     * @return LengthAwarePaginator<int, CmsSearchable>
     */
    public function search(string $query, string|array|null $type = null, int $perPage = 10, ?string $locale = null): LengthAwarePaginator
    {
        $locale ??= app()->getLocale();
        $types = $type === null || $type === [] ? [] : (array) $type;

        if (CmsSearchable::usesScout()) {
            return CmsScoutSearchable::searchVisible($query, $locale, $types, $perPage);
        }

        $like = "%{$query}%";

        // ponytail: LIKE full scan over title/excerpt/content; switch on Scout
        // (or add a FULLTEXT path) when the index outgrows it.
        return CmsSearchable::query()
            ->visible()
            ->where('locale', $locale)
            ->when($types !== [], fn ($q) => $q->whereIn('searchable_type', $types))
            ->whereAny(['title', 'excerpt', 'content'], 'like', $like)
            ->orderByRaw('CASE WHEN title LIKE ? THEN 0 ELSE 1 END', [$like])
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * Result counts per tab ('all' + each FILTERS key).
     *
     * @return array<string, int>
     */
    public function typeCounts(string $query, ?string $locale = null): array
    {
        // ponytail: one total() query per tab — engine-agnostic; group by in SQL if it shows up.
        $counts = ['all' => $this->search($query, perPage: 1, locale: $locale)->total()];

        foreach (self::FILTERS as $filter => $types) {
            $counts[$filter] = $this->search($query, $types, 1, $locale)->total();
        }

        return $counts;
    }

    /**
     * Escape $text and wrap case-insensitive matches of $query in <mark>.
     * Splits the raw text first, so entities and tags can never be broken.
     */
    public static function highlight(string $text, string $query): HtmlString
    {
        if (trim($query) === '') {
            return new HtmlString(e($text));
        }

        $parts = preg_split('/('.preg_quote($query, '/').')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$text];
        $html = '';

        foreach ($parts as $index => $part) {
            $html .= $index % 2 === 1 ? '<mark>'.e($part).'</mark>' : e($part);
        }

        return new HtmlString($html);
    }

    /**
     * Plain-text window of $length characters around the first match.
     */
    public static function snippet(?string $text, string $query, int $length = 160): string
    {
        $text = (string) $text;
        $position = $query === '' ? false : mb_stripos($text, $query);

        if ($position === false || mb_strlen($text) <= $length) {
            return Str::limit($text, $length);
        }

        $start = max(0, $position - intdiv($length, 3));

        return ($start > 0 ? '…' : '').Str::limit(mb_substr($text, $start), $length);
    }

    /**
     * Top matches for the as-you-type dropdown.
     *
     * @return Collection<int, CmsSearchable>
     */
    public function suggest(string $query, int $limit = 5, ?string $locale = null): Collection
    {
        return collect($this->search($query, perPage: $limit, locale: $locale)->items());
    }
}
