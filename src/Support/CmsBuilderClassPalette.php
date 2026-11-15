<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

/**
 * The curated Tailwind classes editors may apply to page builder regions and widgets.
 * Only these are compiled (cms:setup adds the palette file as a Tailwind @source),
 * so anything outside it is rejected.
 */
final class CmsBuilderClassPalette
{
    public const PATH = __DIR__.'/../../resources/builder/class-palette.json';

    /**
     * Width class that stretches a region past the theme container to the viewport edges.
     * The page wrappers clip the scrollbar-wide overflow it causes (`[html:has(&)]:overflow-x-clip [body:has(&)]:overflow-x-clip`).
     */
    public const FULL_BLEED = 'mx-[calc(50%-50vw)]';

    /** @var array<string, array{label: string, classes: list<string>}>|null */
    private ?array $groups = null;

    /**
     * @return array<string, array{label: string, classes: list<string>}>
     */
    public function groups(): array
    {
        if ($this->groups === null) {
            /** @var array<string, array{label: string, classes: list<string>}> $decoded */
            $decoded = json_decode((string) file_get_contents(self::PATH), true, flags: JSON_THROW_ON_ERROR);
            $this->groups = $decoded;
        }

        return $this->groups;
    }

    /**
     * @param  array<int|string, mixed>  $classes
     * @return list<string>
     */
    public function filter(array $classes): array
    {
        $allowed = array_merge(...array_column($this->groups(), 'classes'));

        return array_values(array_unique(array_filter(
            $classes,
            fn (mixed $class): bool => is_string($class) && in_array($class, $allowed, true),
        )));
    }

    /**
     * Editor-typed semantic classes (e.g. "hero-banner"), styled by the theme's own CSS.
     * Only plain class names pass: anything with Tailwind syntax (`md:`, `/`, `[...]`)
     * is dropped since it would never be compiled. Space-separated, at most 20.
     */
    public function sanitizeCustom(mixed $classes): string
    {
        $tokens = preg_split('/\s+/', is_string($classes) ? $classes : '', -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $valid = array_filter($tokens, fn (string $class): bool => preg_match('/^-?[_a-zA-Z][_a-zA-Z0-9-]{0,63}$/', $class) === 1);

        return implode(' ', array_slice(array_values(array_unique($valid)), 0, 20));
    }
}
