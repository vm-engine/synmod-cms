<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Support\CmsBuilderTemplates;
use VmEngine\Cms\Support\CmsWidgetRegistry;

it('offers the package templates with their regions', function () {
    $templates = app(CmsBuilderTemplates::class)->all();

    expect($templates)->toHaveKeys(['full', 'sidebar-right'])
        ->and(array_keys($templates['sidebar-right']['regions']))->toBe(['hero', 'main', 'sidebar', 'footer-top']);
});

it('merges valid theme templates and skips broken ones', function () {
    $templates = (new CmsBuilderTemplates)->merge([
        'landing' => ['name' => 'Landing', 'view' => 'cms::builder.templates.full', 'regions' => ['hero' => 'Hero']],
        'broken' => ['name' => 'Broken', 'view' => 'theme::missing', 'regions' => ['main' => 'Main']],
        'empty' => ['name' => 'Empty', 'view' => 'cms::builder.templates.full', 'regions' => []],
    ]);

    expect($templates)->toHaveKey('landing')->not->toHaveKeys(['broken', 'empty']);
});

it('falls back to the full template for an unknown key', function () {
    expect(app(CmsBuilderTemplates::class)->find('gone')['view'])->toBe('cms::builder.templates.full');
});

it('renders a region publicly with palette classes and skips empty regions', function () {
    CmsArticle::factory()->create();
    $layout = ['regions' => [
        'main' => ['class' => ['py-8', 'evil'], 'custom_class' => 'main-band', 'widgets' => [
            ['id' => 'w1', 'type' => 'articles', 'props' => [], 'class' => ['shadow-md'], 'custom_class' => 'news-feed md:flex'],
        ]],
        'hero' => ['class' => [], 'widgets' => []],
    ]];

    $main = Blade::render('<x-cms::builder.region name="main" :layout="$layout" />', ['layout' => $layout]);
    $hero = Blade::render('<x-cms::builder.region name="hero" :layout="$layout" />', ['layout' => $layout]);

    expect($main)->toContain('cms-builder-region--main')->toContain('py-8')->toContain('shadow-md')->toContain('main-band')->toContain('news-feed')
        ->not->toContain('evil')->not->toContain('md:flex')
        ->and(trim($hero))->toBe('');
});

it('renders edit chrome with sort hooks and an error card for failing widgets', function () {
    app(CmsWidgetRegistry::class)->register('broken', ['label' => 'Broken', 'icon' => 'ph ph-x', 'component' => 'cms::does-not-exist', 'props' => []]);
    $layout = ['regions' => ['main' => ['class' => [], 'widgets' => [
        ['id' => 'w9', 'type' => 'broken', 'props' => [], 'class' => []],
    ]]]];

    $html = Blade::render('<x-cms::builder.region name="main" :layout="$layout" :editing="true" />', ['layout' => $layout]);

    expect($html)->toContain('wire:sort="moveWidget"')
        ->toContain('wire:sort:group-id="main"')
        ->toContain('wire:sort:item="w9"')
        ->toContain(__('cms::builder.widget_failed'))
        // Removing asks the shell to confirm first; the canvas never removes directly.
        ->toMatch('/data-widget="w9"\s+data-label="Broken"[^>]*@click="remove"/')
        ->not->toContain('removeWidget');
});

it('keeps an unconfigured widget grabbable on the canvas and hides it publicly', function () {
    $layout = ['regions' => ['sidebar' => ['class' => [], 'widgets' => [
        ['id' => 'g1', 'type' => 'gallery', 'props' => ['album' => ''], 'class' => []],
    ]]]];

    $editing = Blade::render('<x-cms::builder.region name="sidebar" :layout="$layout" :editing="true" />', ['layout' => $layout]);
    $public = Blade::render('<x-cms::builder.region name="sidebar" :layout="$layout" />', ['layout' => $layout]);

    expect($editing)->toContain(__('cms::builder.widget_empty'))
        ->and(trim($public))->toBe('');
});

it('prints a widget heading above the widget only when it is switched on', function () {
    CmsArticle::factory()->create();
    $layout = fn (bool $show): array => ['regions' => ['main' => ['class' => [], 'widgets' => [
        ['id' => 'w1', 'type' => 'articles', 'props' => [], 'class' => [], 'heading' => 'Latest <news>', 'show_heading' => $show],
    ]]]];

    $shown = Blade::render('<x-cms::builder.region name="main" :layout="$layout" />', ['layout' => $layout(true)]);
    $hidden = Blade::render('<x-cms::builder.region name="main" :layout="$layout" />', ['layout' => $layout(false)]);

    expect($shown)->toMatch('/<h2 class="cms-widget__heading cms-builder-widget__heading">\s*Latest &lt;news&gt;\s*<\/h2>/')
        ->and($hidden)->not->toContain('cms-builder-widget__heading');
});
