<?php

declare(strict_types=1);

use Livewire\Livewire;
use VmEngine\Cms\Models\CmsArticle;
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
    $this->page = $this->service->create(['title' => 'Home', 'slug' => 'home', 'template' => 'sidebar-right']);
    $this->widget = $this->service->addWidget($this->page, 'main', 'articles');
});

it('renders the draft with edit chrome inside the frontend layout', function () {
    $this->actingAs($this->user)->get(backend_route('cms.builder.canvas', ['id' => $this->page->id]))
        ->assertOk()
        ->assertSee('fe-body', false)
        ->assertSee('wire:sort:group-id="sidebar"', false)
        ->assertSee('wire:sort:item="'.$this->widget.'"', false)
        ->assertSee('cms-builder-canvas [html:has(&)]:overflow-x-clip [body:has(&)]:overflow-x-clip', false);
});

it('renders the draft without chrome in preview mode', function () {
    CmsArticle::factory()->create();

    $this->actingAs($this->user)->get(backend_route('cms.builder.canvas', ['id' => $this->page->id, 'preview' => 1]))
        ->assertOk()
        ->assertDontSee('wire:sort', false)
        ->assertSee('cms-builder-region--main', false);
});

it('moves and duplicates widgets and tells the shell', function () {
    $component = Livewire::actingAs($this->user)->test('cms::backend.builder-canvas', ['id' => $this->page->id]);

    $component->call('moveWidget', $this->widget, 0, 'sidebar')->assertDispatched('cms-builder-changed');
    expect(array_column($this->page->fresh()->layout_draft['regions']['sidebar']['widgets'], 'id'))->toBe([$this->widget]);

    $component->call('duplicateWidget', $this->widget)->assertDispatched('cms-builder-changed');
    expect($this->page->fresh()->layout_draft['regions']['sidebar']['widgets'])->toHaveCount(2);
});

it('notifies instead of crashing for a stale widget id', function () {
    Livewire::actingAs($this->user)->test('cms::backend.builder-canvas', ['id' => $this->page->id])
        ->call('duplicateWidget', 'gone')
        ->assertDispatched('notify', variant: 'danger')
        ->assertNotDispatched('cms-builder-changed');
});

it('forbids users without update permission', function () {
    $reader = User::factory()->create();
    $reader->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'builder')->readOnly()
    )->create(['slug' => 'reader', 'level' => 3, 'can_access_backend' => true]));

    Livewire::actingAs($reader)->test('cms::backend.builder-canvas', ['id' => $this->page->id])->assertForbidden();
});
