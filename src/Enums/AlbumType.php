<?php

declare(strict_types=1);

namespace VmEngine\Cms\Enums;

enum AlbumType: string
{
    case Normal = 'normal';
    case Banner = 'banner';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal Gallery',
            self::Banner => 'Banner Slider',
        };
    }

    public static function options(): array
    {
        return [
            self::Normal->value => self::Normal->label(),
            self::Banner->value => self::Banner->label(),
        ];
    }
}
