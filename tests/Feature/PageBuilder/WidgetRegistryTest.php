<?php

declare(strict_types=1);

use VmEngine\Cms\Support\CmsWidgetRegistry;

it('registers the eight cms widgets', function () {
    expect(array_keys(app(CmsWidgetRegistry::class)->all()))->toBe([
        'page', 'page-children', 'articles', 'section-articles',
        'category-articles', 'tag-articles', 'banner-slider', 'gallery',
    ]);
});

it('sanitizes props: drops unknown keys, casts types, rejects bad select values', function () {
    $props = app(CmsWidgetRegistry::class)->sanitizeProps('articles', [
        'limit' => '3',
        'showCover' => '0',
        'layout' => 'carousel',
        'template' => 'evil::view',
    ]);

    expect($props)->toBe([
        'limit' => 3,
        'sort' => 'latest',
        'layout' => 'grid',
        'showSection' => true,
        'showExcerpt' => true,
        'showCover' => false,
        'showDate' => true,
        'showFeaturedBadge' => true,
    ]);
});

it('renders every widget with its default props', function (string $type) {
    expect(app(CmsWidgetRegistry::class)->render($type, []))->toBeString();
})->with(['page', 'page-children', 'articles', 'section-articles', 'category-articles', 'tag-articles', 'banner-slider', 'gallery']);

it('returns null instead of throwing when a widget fails', function () {
    $registry = app(CmsWidgetRegistry::class);
    $registry->register('broken', ['label' => 'Broken', 'icon' => 'ph ph-x', 'component' => 'cms::does-not-exist', 'props' => []]);

    expect($registry->renderSafely('broken', []))->toBeNull();
});

it('throws for an unknown widget type', function () {
    app(CmsWidgetRegistry::class)->get('nope');
})->throws(InvalidArgumentException::class);
