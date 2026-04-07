<?php

declare(strict_types=1);

namespace VmEngine\Cms\Enums;

enum DownloadAccess: string
{
    case Public = 'public';
    case Private = 'private';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public',
            self::Private => 'Private (Login Required)',
        };
    }

    public static function options(): array
    {
        return [
            self::Public->value => self::Public->label(),
            self::Private->value => self::Private->label(),
        ];
    }
}
