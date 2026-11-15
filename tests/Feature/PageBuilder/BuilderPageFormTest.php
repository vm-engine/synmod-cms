<?php

declare(strict_types=1);

use Livewire\Livewire;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'builder')->fullCrud()
    )->create(['name' => 'Admin', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]));
});

it('creates a page and redirects to the editor', function () {
    $component = Livewire::actingAs($this->user)->test('cms::backend.builder-page-form')
        ->set('form.title', 'Promo')
        ->set('form.slug', 'promo')
        ->set('form.template', 'sidebar-right')
        ->call('save')
        ->assertHasNoErrors();

    $page = CmsBuilderPage::where('slug', 'promo')->firstOrFail();
    $component->assertRedirect(backend_route('cms.builder.editor', ['id' => $page->id]));
    expect($page->template)->toBe('sidebar-right')
        ->and($page->created_by)->toBe($this->user->id);
});

it('rejects a slug used by a cms page', function () {
    CmsPage::factory()->create(['slug' => 'about', 'full_slug' => 'about', 'status' => 'published']);

    Livewire::actingAs($this->user)->test('cms::backend.builder-page-form')
        ->set('form.title', 'About')
        ->set('form.slug', 'about')
        ->call('save')
        ->assertHasErrors(['form.slug']);
});

it('rejects an unknown template', function () {
    Livewire::actingAs($this->user)->test('cms::backend.builder-page-form')
        ->set('form.title', 'X')
        ->set('form.slug', 'x')
        ->set('form.template', 'nope')
        ->call('save')
        ->assertHasErrors(['form.template']);
});

it('updates settings of an existing page and keeps its own slug valid', function () {
    $page = CmsBuilderPage::factory()->create(['slug' => 'old']);

    Livewire::actingAs($this->user)->test('cms::backend.builder-page-form', ['id' => $page->id])
        ->assertSet('form.slug', 'old')
        ->set('form.meta_title', 'SEO title')
        ->call('save')
        ->assertHasNoErrors()
        ->assertNoRedirect();

    expect($page->fresh()->meta_title)->toBe('SEO title');
});
