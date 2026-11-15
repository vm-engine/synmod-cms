<?php

declare(strict_types=1);

use Livewire\Livewire;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'builder')->fullCrud()
    )->create(['name' => 'Admin', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]));
});

it('lists builder pages and filters by search', function () {
    CmsBuilderPage::factory()->create(['title' => 'Landing Promo']);
    CmsBuilderPage::factory()->create(['title' => 'Careers']);

    Livewire::actingAs($this->user)->test('cms::backend.builder-page-list')
        ->assertSee('Landing Promo')->assertSee('Careers')
        ->set('q', 'Land')
        ->assertSee('Landing Promo')->assertDontSee('Careers');
});

it('deletes with a valid token and refuses a forged one', function () {
    $page = CmsBuilderPage::factory()->create();

    Livewire::actingAs($this->user)->test('cms::backend.builder-page-list')
        ->call('delete', 'forged')
        ->assertDispatched('notify', variant: 'danger')
        ->call('delete', $page->delete_token)
        ->assertDispatched('notify', variant: 'success');

    expect(CmsBuilderPage::find($page->id))->toBeNull();
});

it('renders delete buttons with tokens, never raw ids', function () {
    $page = CmsBuilderPage::factory()->create();

    Livewire::actingAs($this->user)->test('cms::backend.builder-page-list')
        ->assertSeeHtml('data-token="'.$page->delete_token.'"');
});

it('forbids deleting without the delete permission', function () {
    $page = CmsBuilderPage::factory()->create();
    $reader = User::factory()->create();
    $reader->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'builder')->readOnly()
    )->create(['slug' => 'reader', 'level' => 3, 'can_access_backend' => true]));

    Livewire::actingAs($reader)->test('cms::backend.builder-page-list')
        ->call('delete', $page->delete_token)
        ->assertForbidden();

    expect(CmsBuilderPage::find($page->id))->not->toBeNull();
});
