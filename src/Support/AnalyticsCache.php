<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Closure;
use Illuminate\Support\Facades\Cache;

/**
 * 1-hour cache for Google report results. Keys carry a version number, so
 * flush() invalidates everything on any cache driver (no tags needed).
 */
final class AnalyticsCache
{
    private const VERSION_KEY = 'cms.analytics.version';

    private const TTL_SECONDS = 3600;

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        return Cache::remember('cms.analytics.v'.self::version().'.'.$key, self::TTL_SECONDS, $callback);
    }

    public static function flush(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
