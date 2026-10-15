<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Support\CmsUrl;

require_once __DIR__.'/MediaGallery/helpers.php';

uses(RefreshDatabase::class);

describe('model helpers', function () {
    it('resolves children display with legacy show_children fallback', function (?array $config, string $expected) {
        expect((new CmsPage(['config' => $config]))->childrenDisplay())->toBe($expected);
    })->with([
        'empty' => [null, 'none'],
        'legacy on' => [['show_children' => true], 'cards'],
        'legacy off' => [['show_children' => false], 'none'],
        'explicit wins' => [['show_children' => true, 'children_display' => 'one_page'], 'one_page'],
        'unknown value' => [['children_display' => 'bogus'], 'none'],
    ]);

    it('allows child creation by default and blocks non-devs when locked', function () {
        $open = new CmsPage(['config' => null]);
        $locked = new CmsPage(['config' => ['allow_child_create' => false]]);

        $this->actingAs(mgAdmin());
        expect($open->acceptsChildrenFromCurrentUser())->toBeTrue()
            ->and($locked->acceptsChildrenFromCurrentUser())->toBeFalse();

        $this->actingAs(mgDev());
        expect($locked->acceptsChildrenFromCurrentUser())->toBeTrue();
    });

    it('shows pages in the sitemap unless turned off', function (?array $config, bool $expected) {
        expect((new CmsPage(['config' => $config]))->showInSitemap())->toBe($expected);
    })->with([
        'empty' => [null, true],
        'on' => [['show_in_sitemap' => true], true],
        'off' => [['show_in_sitemap' => false], false],
    ]);
});

describe('page form', function () {
    it('saves display settings', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.children_display', 'one_page')
            ->set('form.show_submenu', true)
            ->set('form.show_excerpt', true)
            ->call('save')
            ->assertHasNoErrors();

        $config = $page->fresh()->config;
        expect($config['children_display'])->toBe('one_page')
            ->and($config['show_submenu'])->toBeTrue()
            ->and($config['show_excerpt'])->toBeTrue()
            ->and($config)->not->toHaveKey('show_children');
    });

    it('loads legacy show_children as cards', function () {
        $page = CmsPage::factory()->create(['config' => ['show_children' => true]]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSet('form.children_display', 'cards');
    });

    it('rejects an unknown children display', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.children_display', 'bogus')
            ->call('save')
            ->assertHasErrors('form.children_display');
    });

    it('lets a dev lock child creation', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs(mgDev())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSee(__('cms::labels.allow_child_create'))
            ->set('form.allow_child_create', false)
            ->call('save')
            ->assertHasNoErrors();

        expect($page->fresh()->config['allow_child_create'])->toBeFalse();
    });

    it('keeps the stored lock when a non-dev saves', function () {
        $page = CmsPage::factory()->create(['config' => ['allow_child_create' => false]]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertDontSee(__('cms::labels.allow_child_create'))
            ->set('form.allow_child_create', true)
            ->call('save')
            ->assertHasNoErrors();

        expect($page->fresh()->config['allow_child_create'])->toBeFalse();
    });

    it('saves the sitemap flag', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSet('form.show_in_sitemap', true)
            ->set('form.show_in_sitemap', false)
            ->call('save')
            ->assertHasNoErrors();

        expect($page->fresh()->config['show_in_sitemap'])->toBeFalse();
    });
});

describe('child lock', function () {
    beforeEach(function () {
        $this->locked = CmsPage::factory()->create(['title' => 'Locked Root', 'config' => ['allow_child_create' => false]]);
        $this->open = CmsPage::factory()->create(['title' => 'Open Root']);
    });

    it('hides add-child on locked pages for non-devs only', function () {
        $lockedUrl = backend_route('cms.page.form', ['parentId' => $this->locked->id]);

        Livewire::actingAs(mgAdmin())->test('cms::backend.page-list')
            ->assertDontSee($lockedUrl, false)
            ->assertSee(backend_route('cms.page.form', ['parentId' => $this->open->id]), false);

        Livewire::actingAs(mgDev())->test('cms::backend.page-list')
            ->assertSee($lockedUrl, false);
    });

    it('forbids a non-dev opening the form under a locked parent', function () {
        Livewire::actingAs(mgAdmin())
            ->withQueryParams(['parentId' => $this->locked->id])
            ->test('cms::backend.page-form')
            ->assertForbidden();
    });

    it('forbids a non-dev moving a page under a locked parent', function () {
        $page = CmsPage::factory()->create(['parent_id' => $this->open->id]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.parent_id', $this->locked->id)
            ->call('save')
            ->assertForbidden();

        expect($page->fresh()->parent_id)->toBe($this->open->id);
    });

    it('keeps an existing child editable under a locked parent', function () {
        $child = CmsPage::factory()->create(['parent_id' => $this->locked->id]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.page-form', ['id' => $child->id])
            ->set('form.title', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        expect($child->fresh()->title)->toBe('Renamed');
    });

    it('omits locked pages from parent options for non-devs', function () {
        $options = Livewire::actingAs(mgAdmin())->test('cms::backend.page-form')->instance()->parentOptions;

        expect(array_column($options, 'value'))->not->toContain($this->locked->id)->toContain($this->open->id);
    });

    it('lets a dev create a child under a locked parent', function () {
        Livewire::actingAs(mgDev())
            ->withQueryParams(['parentId' => $this->locked->id])
            ->test('cms::backend.page-form')
            ->assertOk();
    });
});

describe('frontend', function () {
    it('shows the excerpt only when enabled', function (bool $on) {
        $page = CmsPage::factory()->published()->create(['excerpt' => 'Short summary here', 'config' => ['show_excerpt' => $on]]);

        $response = $this->get(CmsUrl::to($page))->assertOk();
        // Meta description falls back to the excerpt, so assert on the rendered element.
        $element = '<p class="cms-page__excerpt">Short summary here</p>';
        $on ? $response->assertSee($element, false) : $response->assertDontSee($element, false);
    })->with([true, false]);

    it('renders children as one page with anchors', function () {
        $parent = CmsPage::factory()->published()->create(['config' => ['children_display' => 'one_page']]);
        $child = CmsPage::factory()->published()->create(['parent_id' => $parent->id, 'slug' => 'alpha', 'title' => 'Alpha', 'body' => '<p>Alpha body text</p>']);
        CmsPage::factory()->create(['parent_id' => $parent->id, 'status' => 'draft', 'body' => '<p>Draft body text</p>']);

        $this->get(CmsUrl::to($parent))
            ->assertOk()
            ->assertSee('id="'.$child->childAnchor().'"', false)
            ->assertSee('Alpha body text', false)
            ->assertDontSee('Draft body text', false)
            ->assertDontSee('cms-page-card', false);

        $this->get(CmsUrl::to($child->fresh()))->assertOk()->assertSee('Alpha body text', false);
    });

    it('shows own children in the sub-menu, then siblings on a child', function () {
        $parent = CmsPage::factory()->published()->create(['title' => 'Services', 'config' => ['show_submenu' => true]]);
        $a = CmsPage::factory()->published()->create(['parent_id' => $parent->id, 'title' => 'Web Design', 'slug' => 'web']);
        $b = CmsPage::factory()->published()->create(['parent_id' => $parent->id, 'title' => 'Hosting Plans', 'slug' => 'hosting']);

        $this->get(CmsUrl::to($parent))->assertOk()
            ->assertSee('cms-submenu', false)
            ->assertSee(CmsUrl::to($a->fresh()), false)
            ->assertSee(CmsUrl::to($b->fresh()), false);

        $this->get(CmsUrl::to($a->fresh()))->assertOk()
            ->assertSee('cms-submenu', false)
            ->assertSee(CmsUrl::to($b->fresh()), false)
            ->assertSee('aria-current="page"', false);
    });

    it('has no sub-menu when the flag is off', function () {
        $parent = CmsPage::factory()->published()->create();
        CmsPage::factory()->published()->create(['parent_id' => $parent->id]);

        $this->get(CmsUrl::to($parent))->assertOk()->assertDontSee('cms-submenu', false);
    });

    it('links sub-menu items to anchors in one-page mode', function () {
        $parent = CmsPage::factory()->published()->create(['config' => ['show_submenu' => true, 'children_display' => 'one_page']]);
        $a = CmsPage::factory()->published()->create(['parent_id' => $parent->id, 'slug' => 'web']);

        $this->get(CmsUrl::to($a->fresh()))->assertOk()
            ->assertSee(CmsUrl::to($parent).'#'.$a->childAnchor(), false);
    });
});
