<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use VmEngine\SynFallback\Facades\Fallback;

/**
 * Content-locale resolution for CMS translations.
 *
 * The primary locale is stored in the base content columns; every other
 * enabled locale is stored in cms_translations.
 */
final class CmsLocale
{
    public static function primary(): string
    {
        return (string) (config('synapps.modules.cms.primary_locale') ?: config('app.locale'));
    }

    public static function isMultiLanguage(): bool
    {
        return Fallback::isMultiLanguage();
    }

    /**
     * Enabled content locales, primary first.
     *
     * @return list<string>
     */
    public static function all(): array
    {
        $primary = self::primary();

        if (! self::isMultiLanguage()) {
            return [$primary];
        }

        $enabled = array_filter((array) config('synapps.apps.enabled_languages', []), 'is_string');

        return array_values(array_unique([$primary, ...$enabled]));
    }

    /**
     * Enabled locales other than the primary one.
     *
     * @return list<string>
     */
    public static function secondary(): array
    {
        return array_values(array_diff(self::all(), [self::primary()]));
    }

    /** Bare language code => region for the default flag emoji. */
    private const DEFAULT_REGIONS = [
        'en' => 'GB', 'id' => 'ID', 'ja' => 'JP', 'zh' => 'CN', 'ko' => 'KR', 'ms' => 'MY', 'th' => 'TH', 'vi' => 'VN',
        'ar' => 'SA', 'es' => 'ES', 'fr' => 'FR', 'de' => 'DE', 'pt' => 'PT', 'it' => 'IT', 'nl' => 'NL', 'ru' => 'RU',
    ];

    /**
     * The language's own name ("English", "Indonesia", "日本語"):
     * module.json language_names override, else intl, else the upper-cased code.
     */
    public static function displayName(string $code): string
    {
        $override = ((array) config('synapps.modules.cms.language_names', []))[$code] ?? null;

        if (is_string($override) && $override !== '') {
            return $override;
        }

        if (class_exists(\Locale::class)) {
            $name = (string) \Locale::getDisplayName($code, $code);

            if ($name !== '' && strcasecmp($name, $code) !== 0) {
                return mb_strtoupper(mb_substr($name, 0, 1)).mb_substr($name, 1);
            }
        }

        return strtoupper($code);
    }

    /**
     * Icon for the language switcher: module.json language_icons entry
     * (image path/URL, CSS class or emoji), else a flag emoji derived from the
     * locale's region or a built-in map, else null.
     *
     * @return array{type: 'image'|'class'|'emoji', value: string}|null
     */
    public static function icon(string $code): ?array
    {
        $icon = ((array) config('synapps.modules.cms.language_icons', []))[$code] ?? null;

        if (is_string($icon) && $icon !== '') {
            return ['type' => self::iconType($icon), 'value' => $icon];
        }

        $region = preg_match('/^[a-z]{2,3}[_-]([a-z]{2})$/i', $code, $match)
            ? strtoupper($match[1])
            : (self::DEFAULT_REGIONS[strtolower($code)] ?? null);

        return $region === null ? null : ['type' => 'emoji', 'value' => self::flagEmoji($region)];
    }

    /**
     * @return 'image'|'class'|'emoji'
     */
    private static function iconType(string $icon): string
    {
        if (preg_match('#^(/|https?://|data:image/)#i', $icon) || preg_match('/\.(svg|png|webp|jpe?g|gif)(\?.*)?$/i', $icon)) {
            return 'image';
        }

        return preg_match('/^(fi|ph|fa)[\s-]/', $icon) || str_contains($icon, ' ') ? 'class' : 'emoji';
    }

    private static function flagEmoji(string $region): string
    {
        return implode('', array_map(
            fn (string $letter): string => (string) mb_chr(0x1F1E6 + ord($letter) - ord('A')),
            str_split($region),
        ));
    }
}
