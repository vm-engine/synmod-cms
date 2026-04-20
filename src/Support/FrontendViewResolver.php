<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

class FrontendViewResolver
{
    public static function layout(?string $custom = null): string
    {
        if ($custom && view()->exists($custom)) {
            return $custom;
        }

        $themeLayout = theme_service()->resolveThemeLayout('frontend');

        return view()->exists($themeLayout)
            ? $themeLayout
            : 'synapps::components.layouts.layout';
    }

    public static function view(?string $custom, string $default): string
    {
        return ($custom && view()->exists($custom)) ? $custom : $default;
    }
}
