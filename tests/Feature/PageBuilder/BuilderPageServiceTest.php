<?php

declare(strict_types=1);

use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsBuilderPageService;

beforeEach(function () {
    $this->service = app(CmsBuilderPageService::class);
    $this->page = $this->service->create(['title' => 'Home', 'slug' => 'home', 'template' => 'sidebar-right']);
});

it('creates a page with an empty region for every template region', function () {
    expect(array_keys($this->page->layout_draft['regions']))->toBe(['hero', 'main', 'sidebar', 'footer-top'])
        ->and($this->page->status)->toBe('draft');
});

it('adds, moves, duplicates and removes widgets in the draft only', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');
    $this->service->addWidget($this->page, 'main', 'gallery');

    $this->service->moveWidget($this->page, $id, 0, 'sidebar');
    $copy = $this->service->duplicateWidget($this->page, $id);
    $this->service->removeWidget($this->page, $copy);

    $regions = $this->page->fresh()->layout_draft['regions'];

    expect(array_column($regions['sidebar']['widgets'], 'id'))->toBe([$id])
        ->and(array_column($regions['main']['widgets'], 'type'))->toBe(['gallery'])
        ->and($this->page->fresh()->layout)->toBeNull();
});

it('reorders within the same region', function () {
    $first = $this->service->addWidget($this->page, 'main', 'articles');
    $second = $this->service->addWidget($this->page, 'main', 'gallery');

    $this->service->moveWidget($this->page, $second, 0, 'main');

    expect(array_column($this->page->fresh()->layout_draft['regions']['main']['widgets'], 'id'))->toBe([$second, $first]);
});

it('sanitizes props and classes on update', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');

    $this->service->updateWidget($this->page, $id, ['limit' => '4', 'bogus' => 'x'], ['py-8', 'bad"class']);

    $widget = $this->page->fresh()->layout_draft['regions']['main']['widgets'][0];
    expect($widget['props']['limit'])->toBe(4)
        ->and($widget['props'])->not->toHaveKey('bogus')
        ->and($widget['class'])->toBe(['py-8']);
});

it('rejects unknown regions', function () {
    $this->service->addWidget($this->page, 'nowhere', 'articles');
})->throws(InvalidArgumentException::class);

it('rejects unknown widget types', function () {
    $this->service->addWidget($this->page, 'main', 'nope');
})->throws(InvalidArgumentException::class);

it('moves widgets from regions the new template lacks into its first region', function () {
    $id = $this->service->addWidget($this->page, 'sidebar', 'articles');

    $this->service->switchTemplate($this->page, 'full');

    $regions = $this->page->fresh()->layout_draft['regions'];
    expect(array_keys($regions))->toBe(['hero', 'main'])
        ->and(array_column($regions['hero']['widgets'], 'id'))->toBe([$id]);
});

it('publishes the draft and unpublishes', function () {
    $this->service->addWidget($this->page, 'main', 'articles');

    $this->service->publish($this->page);
    expect($this->page->fresh()->status)->toBe('published')
        ->and($this->page->fresh()->hasUnpublishedChanges())->toBeFalse();

    $this->service->unpublish($this->page);
    expect($this->page->fresh()->status)->toBe('draft');
});

it('detects slug conflicts with cms pages and other builder pages', function () {
    CmsPage::factory()->create(['slug' => 'about', 'full_slug' => 'about', 'status' => 'published']);

    expect($this->service->slugIsTaken('about'))->toBeTrue()
        ->and($this->service->slugIsTaken('home'))->toBeTrue()
        ->and($this->service->slugIsTaken('home', ignoreId: $this->page->id))->toBeFalse()
        ->and($this->service->slugIsTaken('fresh-slug'))->toBeFalse();
});

it('deletes by token', function () {
    $this->service->deleteByToken($this->page->delete_token);

    expect(CmsBuilderPage::find($this->page->id))->toBeNull();
});

it('refuses a forged delete token', function () {
    $this->service->deleteByToken('forged');
})->throws(InvalidArgumentException::class);

it('lists page picker options in tree order with their depth', function () {
    $about = CmsPage::factory()->create(['title' => 'About', 'slug' => 'about', 'full_slug' => 'about', 'sort_order' => 1]);
    $team = CmsPage::factory()->create(['title' => 'Team', 'slug' => 'team', 'full_slug' => 'about/team', 'parent_id' => $about->id, 'sort_order' => 1]);
    CmsPage::factory()->create(['title' => 'Leadership', 'slug' => 'leadership', 'full_slug' => 'about/team/leadership', 'parent_id' => $team->id, 'sort_order' => 1]);
    CmsPage::factory()->create(['title' => 'History', 'slug' => 'history', 'full_slug' => 'about/history', 'parent_id' => $about->id, 'sort_order' => 2]);
    CmsPage::factory()->create(['title' => 'Contact', 'slug' => 'contact', 'full_slug' => 'contact', 'sort_order' => 2]);

    expect($this->service->sourceOptions('pages'))->toBe([
        ['value' => 'about', 'label' => 'About', 'depth' => 0],
        ['value' => 'about/team', 'label' => 'Team', 'depth' => 1],
        ['value' => 'about/team/leadership', 'label' => 'Leadership', 'depth' => 2],
        ['value' => 'about/history', 'label' => 'History', 'depth' => 1],
        ['value' => 'contact', 'label' => 'Contact', 'depth' => 0],
    ]);
});

it('stores sanitized custom classes for widgets and regions', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');

    $this->service->updateWidget($this->page, $id, [], ['py-8'], 'news-feed md:flex');
    $this->service->updateRegionClasses($this->page, 'hero', [], 'hero-band');

    $regions = $this->page->fresh()->layout_draft['regions'];
    expect($regions['main']['widgets'][0]['custom_class'])->toBe('news-feed')
        ->and($regions['hero']['custom_class'])->toBe('hero-band')
        ->and($regions['sidebar']['custom_class'])->toBe('');
});

it('stores a trimmed plain-text widget heading and its show switch', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');
    expect($this->page->fresh()->layout_draft['regions']['main']['widgets'][0])
        ->toMatchArray(['heading' => '', 'show_heading' => true]);

    $this->service->updateWidget($this->page, $id, [], [], heading: '  <b>Latest</b> news '.str_repeat('x', 200), showHeading: false);

    $widget = $this->page->fresh()->layout_draft['regions']['main']['widgets'][0];
    expect($widget['heading'])->toStartWith('Latest news x')->toHaveLength(120)
        ->and($widget['show_heading'])->toBeFalse();
});
