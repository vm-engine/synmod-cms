<?php

declare(strict_types=1);

namespace VmEngine\Cms\Enums;

enum CmsVisibility: string
{
    case Public = 'public';
    case Protected = 'protected';
    case Private = 'private';
    case Inherit = 'inherit';

    public function label(): string
    {
        return __('cms::access.visibility_'.$this->value);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(bool $withInherit = false): array
    {
        $cases = $withInherit
            ? [self::Inherit, self::Public, self::Protected, self::Private]
            : [self::Public, self::Protected, self::Private];

        return array_map(fn (self $v) => ['value' => $v->value, 'label' => $v->label()], $cases);
    }
}
