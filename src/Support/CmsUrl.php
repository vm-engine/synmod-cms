<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Illuminate\Database\Eloquent\Model;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynFallback\Facades\Fallback;

/**
 * Public URLs of CMS content: the single source of truth for routed paths
 * (CmsFallbackService registers routes with path()) and for links (to()).
 */
final class CmsUrl
{
    /**
     * The fallback-route path (no leading slash, no locale) of routed content.
     * Album images / download files resolve to their parent's path.
     */
    public static function path(Model $model): ?string
    {
        return match (true) {
            $model instanceof CmsPage => $model->full_slug,
            $model instanceof CmsSection => $model->slug,
            $model instanceof CmsCategory => $model->section->slug.'/'.$model->slug,
            $model instanceof CmsArticle => $model->section->slug
                .($model->category_id !== null && $model->category ? '/'.$model->category->slug : '')
                .'/'.$model->slug,
            $model instanceof CmsAlbum => 'gallery/'.$model->slug,
            $model instanceof CmsDownload => self::downloadPrefix().'/'.$model->slug,
            $model instanceof CmsAlbumImage => self::path($model->album),
            $model instanceof CmsDownloadFile => self::path($model->download),
            default => null,
        };
    }

    /**
     * Root-relative link to content (or a raw path) in $locale (default: current).
     */
    public static function to(Model|string $target, ?string $locale = null, ?string $fragment = null): string
    {
        $path = is_string($target) ? trim($target, '/') : self::path($target);

        if ($path === null) {
            return '#';
        }

        return self::localePath($path, $locale).($fragment !== null ? '#'.$fragment : '');
    }

    /**
     * Absolute URL (sitemap, hreflang).
     */
    public static function absolute(string $path, ?string $locale = null): string
    {
        return Fallback::url(trim($path, '/'), $locale ?? app()->getLocale());
    }

    /**
     * Locale-prefixed root-relative path, e.g. "/id/gallery/trip" ("/gallery/trip" when single-language).
     */
    public static function localePath(string $path, ?string $locale = null): string
    {
        return '/'.ltrim((string) parse_url(self::absolute($path, $locale), PHP_URL_PATH), '/');
    }

    /**
     * Prefix an editor-typed site-internal href ("/contact?x#y") with the
     * current locale. External, protocol-relative, anchor, mailto, backend and
     * already-prefixed hrefs are returned unchanged.
     */
    public static function localizeHref(string $href): string
    {
        if (! CmsLocale::isMultiLanguage() || ! str_starts_with($href, '/') || str_starts_with($href, '//')) {
            return $href;
        }

        $cut = strcspn($href, '?#');
        $path = trim(substr($href, 0, $cut), '/');
        $suffix = substr($href, $cut);
        [$locale] = Fallback::splitLocale($path);
        $backend = trim((string) config('synapps.auth.routes.backend_prefix', '/apps'), '/');

        if ($locale !== null || ($backend !== '' && ($path === $backend || str_starts_with($path, $backend.'/')))) {
            return $href;
        }

        return self::localePath($path).$suffix;
    }

    /**
     * Download categories live under the download-list menu item's slug.
     */
    public static function downloadPrefix(): string
    {
        $prefix = CmsMenuItem::query()
            ->where('target_type', 'download_list')
            ->whereNotNull('target_url')
            ->where('target_url', '!=', '')
            ->value('target_url');

        return is_string($prefix) && $prefix !== '' ? trim($prefix, '/') : 'downloads';
    }

    /**
     * The current page in every enabled language — the language switcher's
     * and hreflang's data. Empty when multi-language is off, only one
     * language is enabled, or the current page has no locale prefix.
     *
     * @return list<array{code: string, name: string, url: string, absolute: string, active: bool, icon: array{type: 'image'|'class'|'emoji', value: string}|null}>
     */
    public static function alternates(): array
    {
        $locales = CmsLocale::all();

        if (count($locales) < 2) {
            return [];
        }

        [$requestPath, $query] = self::currentPathAndQuery();
        [$locale, $path] = Fallback::splitLocale($requestPath);

        if ($locale === null) {
            return [];
        }

        $suffix = $query !== '' ? '?'.$query : '';
        $current = app()->getLocale();

        return array_map(fn (string $code): array => [
            'code' => $code,
            'name' => CmsLocale::displayName($code),
            'url' => self::localePath($path, $code).$suffix,
            'absolute' => self::absolute($path, $code).$suffix,
            'active' => $code === $current,
            'icon' => CmsLocale::icon($code),
        ], $locales);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function currentPathAndQuery(): array
    {
        $request = request();

        // Rendered during a Livewire update (/livewire/update): use the page it came from.
        if ($request->hasHeader('X-Livewire')) {
            $referer = (string) $request->headers->get('referer');

            return [trim((string) parse_url($referer, PHP_URL_PATH), '/'), (string) parse_url($referer, PHP_URL_QUERY)];
        }

        return [$request->path(), (string) $request->getQueryString()];
    }
}
