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

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return [
            ['value' => self::Normal->value, 'label' => self::Normal->label()],
            ['value' => self::Banner->value, 'label' => self::Banner->label()],
        ];
    }
}
