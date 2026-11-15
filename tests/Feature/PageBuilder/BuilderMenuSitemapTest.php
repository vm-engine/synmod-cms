<?php

declare(strict_types=1);

use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Services\CmsBuilderPageService;

it('links a menu item to a published builder page', function () {
    $page = CmsBuilderPage::factory()->published()->create(['slug' => 'promo']);
    $item = CmsMenuItem::factory()->create(['target_type' => 'builder_page', 'target_id' => $page->id]);

    expect($item->resolveUrl())->toEndWith('/promo');
});

it('resolves a draft builder page target to #', function () {
    $page = CmsBuilderPage::factory()->create();
    $item = CmsMenuItem::factory()->create(['target_type' => 'builder_page', 'target_id' => $page->id]);

    expect($item->resolveUrl())->toBe('#');
});

it('offers only published builder pages as menu targets', function () {
    CmsBuilderPage::factory()->published()->create(['title' => 'Promo Live']);
    CmsBuilderPage::factory()->create(['title' => 'Promo Draft']);

    $labels = array_column(app(CmsBuilderPageService::class)->searchOptions('Promo'), 'label');

    expect($labels)->toHaveCount(1)
        ->and($labels[0])->toStartWith('Promo Live');
});

it('lists published builder pages in the sitemap', function () {
    CmsBuilderPage::factory()->published()->create(['slug' => 'promo']);
    CmsBuilderPage::factory()->create(['slug' => 'secret-draft']);

    $this->get('/sitemap.xml')
        ->assertOk()
        ->assertSee('/promo', false)
        ->assertDontSee('secret-draft', false);
});
