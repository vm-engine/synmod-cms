<?php

declare(strict_types=1);

use Livewire\Livewire;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'builder')->fullCrud()
    )->create(['name' => 'Admin', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]));

    $this->service = app(CmsBuilderPageService::class);
    $this->page = $this->service->create(['title' => 'Home', 'slug' => 'home', 'template' => 'full']);
});

it('embeds the canvas iframe', function () {
    $this->actingAs($this->user)->get(backend_route('cms.builder.editor', ['id' => $this->page->id]))
        ->assertOk()
        ->assertSee(backend_route('cms.builder.canvas', ['id' => $this->page->id]), false);
});

it('offers a button that reloads the canvas iframe', function () {
    $html = (string) $this->actingAs($this->user)->get(backend_route('cms.builder.editor', ['id' => $this->page->id]))->getContent();

    expect($html)->toMatch('/<button\b[^>]*@click="reloadCanvas"[^>]*aria-label="Reload canvas"/s')
        ->and($html)->toContain('reloadCanvas() {');
});

it('adds a widget to the region picked in the canvas and opens its settings', function () {
    $component = Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('openPicker', 'main')
        ->assertSet('addRegion', 'main')
        ->assertDispatched('open-modal-builder-widget-picker')
        ->call('addWidget', 'articles')
        ->assertDispatched('cms-builder-refresh-canvas');

    $widget = $this->page->fresh()->layout_draft['regions']['main']['widgets'][0];
    $component->assertSet('selectedWidgetId', $widget['id'])
        ->assertSet('widgetProps.limit', 6)
        ->assertSee(__('cms::builder.prop_limit'));
});

it('ignores a picker request for a region the template lacks', function () {
    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('openPicker', 'sidebar')
        ->assertSet('addRegion', null)
        ->assertNotDispatched('open-modal-builder-widget-picker');
});

it('saves widget settings and classes through the service', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');

    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('selectWidget', $id)
        ->set('widgetProps.limit', '3')
        ->set('widgetClasses', ['shadow-md', 'nope'])
        ->call('saveWidget')
        ->assertDispatched('cms-builder-refresh-canvas');

    $widget = $this->page->fresh()->layout_draft['regions']['main']['widgets'][0];
    expect($widget['props']['limit'])->toBe(3)
        ->and($widget['class'])->toBe(['shadow-md']);
});

it('saves custom classes for a widget and a region', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');

    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('selectWidget', $id)
        ->set('widgetCustomClass', 'news-feed')
        ->call('saveWidget')
        ->call('selectRegion', 'hero')
        ->assertSet('regionCustomClass', '')
        ->set('regionCustomClass', 'hero-band')
        ->call('saveRegion')
        ->call('selectWidget', $id)
        ->assertSet('widgetCustomClass', 'news-feed')
        ->assertSeeHtml('id="builder-custom-class"')
        ->assertSee(__('cms::builder.custom_class_help'));

    expect($this->page->fresh()->layout_draft['regions']['hero']['custom_class'])->toBe('hero-band');
});

it('saves the widget heading and its show switch', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');

    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('selectWidget', $id)
        ->assertSet('widgetHeading', '')
        ->assertSet('widgetShowHeading', true)
        ->assertSeeHtml('id="builder-widget-heading"')
        ->set('widgetHeading', 'Latest news')
        ->set('widgetShowHeading', false)
        ->call('saveWidget');

    expect($this->page->fresh()->layout_draft['regions']['main']['widgets'][0])
        ->toMatchArray(['heading' => 'Latest news', 'show_heading' => false]);
});

it('removes a widget only through the confirm dialog it renders', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');

    $component = Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->assertSeeHtml('synapseConfirmDialog')
        ->assertSeeHtml('data-remove-title="'.e(__('cms::builder.remove_confirm_title')).'"')
        ->call('selectWidget', $id)
        ->call('removeWidget', $id)
        ->assertDispatched('synapse-confirmed')
        ->assertDispatched('cms-builder-refresh-canvas')
        ->assertSet('selectedWidgetId', null);

    expect($this->page->fresh()->layout_draft['regions']['main']['widgets'])->toBe([]);

    // The dialog closes on the failure path too.
    $component->call('removeWidget', 'gone')
        ->assertDispatched('synapse-confirmed')
        ->assertDispatched('notify', variant: 'danger');
});

it('saves region classes', function () {
    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('selectRegion', 'hero')
        ->set('regionClasses', ['bg-gray-50', 'py-16'])
        ->call('saveRegion');

    expect($this->page->fresh()->layout_draft['regions']['hero']['class'])->toBe(['bg-gray-50', 'py-16']);
});

it('switches template', function () {
    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->set('template', 'sidebar-right')
        ->assertDispatched('cms-builder-refresh-canvas');

    expect($this->page->fresh()->template)->toBe('sidebar-right');
});

it('requires the publish permission to publish', function () {
    Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('publish')
        ->assertForbidden();

    $publisher = User::factory()->create();
    $publisher->roles()->attach(Role::factory()
        ->has(RolePermission::factory()->forModule('cms', 'builder')->fullCrud())
        ->has(RolePermission::factory()->forModule('cms', 'builder', 'publish')->readUpdate())
        ->create(['slug' => 'publisher', 'level' => 5, 'can_access_backend' => true]));

    Livewire::actingAs($publisher)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('publish')
        ->assertDispatched('notify', variant: 'success');

    expect($this->page->fresh()->status)->toBe('published');
});

it('binds no Alpine expression on the canvas iframe (the CSP build refuses to evaluate on iframes)', function () {
    $html = $this->actingAs($this->user)->get(backend_route('cms.builder.editor', ['id' => $this->page->id]))->getContent();

    preg_match('/<iframe\b[^>]*>/s', (string) $html, $match);

    expect($match)->not->toBeEmpty()
        ->and($match[0])->not->toMatch('/\s(:|x-bind:|x-on:|@)[\w.-]+=/');
});

it('renders widget pickers as adv-select with a none option', function () {
    $id = $this->service->addWidget($this->page, 'main', 'page');

    $component = Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('selectWidget', $id);

    expect($component->html())
        ->toContain('synapseAdvSelect(')
        ->toContain("'widgetProps.slug'")
        ->not->toContain('id="prop-slug"');
    expect($component->instance()->pickerOptions('pages')[0])->toBe(['value' => '', 'label' => __('cms::builder.none')]);
});

it('puts the settings in the named synapse drawer with the actions in its footer, collapsing class groups', function () {
    $id = $this->service->addWidget($this->page, 'main', 'articles');
    $this->service->updateWidget($this->page, $id, [], ['py-8', 'shadow-md']);

    $component = Livewire::actingAs($this->user)->test('cms::backend.builder-editor', ['id' => $this->page->id])
        ->call('selectWidget', $id)
        ->assertDispatched('open-drawer-builder-settings');
    $html = $component->html();

    // Synapse pins the footer; no local layout hacks left.
    expect($html)->toContain('@open-drawer-builder-settings.window')
        ->toMatch('/class="syn-drawer-footer".*wire:click="saveWidget"/s')
        ->not->toContain('syn-drawer-panel]:flex')
        ->not->toContain('cms-builder-drawer-actions');
    expect(substr_count($html, '<details'))->toBe(6);
    // spacing (py-8) and border (shadow-md) open, the other four closed
    expect(preg_match_all('/<details[^>]*\bopen\b/', $html))->toBe(2);
});
