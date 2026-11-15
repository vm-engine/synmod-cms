<?php

declare(strict_types=1);

use VmEngine\Cms\Support\CmsBuilderClassPalette;

it('groups the curated classes with labels', function () {
    $groups = app(CmsBuilderClassPalette::class)->groups();

    expect($groups)->toHaveKeys(['spacing', 'background', 'text', 'border', 'width', 'visibility'])
        ->and($groups['spacing']['classes'])->toContain('py-8');
});

it('keeps only palette classes, deduplicated, in input order', function () {
    $filtered = app(CmsBuilderClassPalette::class)->filter(['py-8', 'evil"><script>', 'py-8', 'md:hidden', 'not-a-class', 42]);

    expect($filtered)->toBe(['py-8', 'md:hidden']);
});

it('keeps custom classes that are plain CSS class names, deduplicated, and drops the rest', function () {
    $custom = app(CmsBuilderClassPalette::class)->sanitizeCustom('  hero-banner card--dark md:flex w-1/2 p-[3px] "x 9bad _alt hero-banner ');

    expect($custom)->toBe('hero-banner card--dark _alt');
});

it('caps custom classes at a sane count and length', function () {
    $palette = app(CmsBuilderClassPalette::class);

    expect(explode(' ', $palette->sanitizeCustom(implode(' ', array_map(fn (int $i): string => "c{$i}", range(1, 40))))))->toHaveCount(20)
        ->and($palette->sanitizeCustom(str_repeat('a', 65)))->toBe('');
});

it('offers a full width class that lets a region break out of the theme container', function () {
    $palette = app(CmsBuilderClassPalette::class);

    expect($palette->groups()['width']['classes'])->toContain(CmsBuilderClassPalette::FULL_BLEED)
        ->and($palette->filter([CmsBuilderClassPalette::FULL_BLEED]))->toBe([CmsBuilderClassPalette::FULL_BLEED]);
});
