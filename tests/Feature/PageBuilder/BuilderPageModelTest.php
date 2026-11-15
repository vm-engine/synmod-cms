<?php

declare(strict_types=1);

use VmEngine\Cms\Models\CmsBuilderPage;

it('casts both layouts to arrays and reports unpublished changes', function () {
    $page = CmsBuilderPage::factory()->create([
        'layout_draft' => ['regions' => ['main' => ['class' => [], 'widgets' => []]]],
        'layout' => null,
    ]);

    expect($page->fresh()->layout_draft)->toBe(['regions' => ['main' => ['class' => [], 'widgets' => []]]])
        ->and($page->hasUnpublishedChanges())->toBeTrue();

    $page->update(['layout' => $page->layout_draft]);

    expect($page->fresh()->hasUnpublishedChanges())->toBeFalse();
});

it('scopes published pages', function () {
    CmsBuilderPage::factory()->create(['slug' => 'draft-one']);
    CmsBuilderPage::factory()->published()->create(['slug' => 'live-one']);

    expect(CmsBuilderPage::published()->pluck('slug')->all())->toBe(['live-one']);
});

it('translates title and meta fields', function () {
    $page = CmsBuilderPage::factory()->create(['title' => 'Home']);

    expect($page->translatableFields())->toBe(['title', 'meta_title', 'meta_description'])
        ->and($page->translate('title'))->toBe('Home')
        ->and($page->delete_token)->toBeString();
});
