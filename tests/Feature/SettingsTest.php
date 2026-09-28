<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Enums\RteDriver;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

function makeCmsSettingsUser(bool $canUpdate): User
{
    $role = Role::factory()->create(['can_access_backend' => true]);
    RolePermission::factory()->forRole($role)->forModule('cms', 'settings')->create([
        'can_create' => false,
        'can_read' => true,
        'can_update' => $canUpdate,
        'can_delete' => false,
    ]);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

it('renders the settings page for users with read access', function () {
    $this->actingAs(makeCmsSettingsUser(canUpdate: false))
        ->get(backend_route('cms.settings'))
        ->assertOk()
        ->assertSee(__('cms::labels.settings_editor'))
        // breadcrumbs partial binds x-text="pageName" from the root x-data
        ->assertSee('x-data="{ pageName: \''.__('cms::labels.settings').'\' }"', false);
});

it('redirects users without settings access', function () {
    $this->actingAs(User::factory()->create())
        ->get(backend_route('cms.settings'))
        ->assertRedirect();
});

it('loads the current driver on mount', function () {
    dbconf(['cms.editor.driver' => 'tinymce']);

    Livewire::actingAs(makeCmsSettingsUser(canUpdate: false))
        ->test('cms::backend.settings')
        ->assertSet('editorDriver', 'tinymce');
});

it('saves the editor driver', function () {
    Livewire::actingAs(makeCmsSettingsUser(canUpdate: true))
        ->test('cms::backend.settings')
        ->set('editorDriver', 'tinymce')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    expect(RteDriver::current())->toBe(RteDriver::Tinymce);
});

it('rejects an unknown driver', function () {
    Livewire::actingAs(makeCmsSettingsUser(canUpdate: true))
        ->test('cms::backend.settings')
        ->set('editorDriver', 'ckeditor')
        ->call('save')
        ->assertHasErrors(['editorDriver']);

    expect(RteDriver::current())->toBe(RteDriver::Jodit);
});

it('does not save without update permission', function () {
    Livewire::actingAs(makeCmsSettingsUser(canUpdate: false))
        ->test('cms::backend.settings')
        ->set('editorDriver', 'tinymce')
        ->call('save');

    expect(RteDriver::current())->toBe(RteDriver::Jodit);
});
