<?php

declare(strict_types=1);

namespace VmEngine\Cms\Enums;

/**
 * Rich-text editor used by <x-cms::rte>. Stored in dbconf('cms.editor.driver'),
 * set by `cms:setup` and the CMS settings page.
 */
enum RteDriver: string
{
    case Jodit = 'jodit';
    case Tinymce = 'tinymce';

    public function label(): string
    {
        return match ($this) {
            self::Jodit => 'Jodit',
            self::Tinymce => 'TinyMCE',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $driver): array => ['value' => $driver->value, 'label' => $driver->label()],
            self::cases(),
        );
    }

    /**
     * The configured driver; unknown or missing values fall back to Jodit.
     */
    public static function current(): self
    {
        $stored = dbconf('cms.editor.driver', self::Jodit->value);

        return self::tryFrom(is_string($stored) ? $stored : '') ?? self::Jodit;
    }
}
