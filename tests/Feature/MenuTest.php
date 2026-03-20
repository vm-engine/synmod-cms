<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permission = RolePermission::factory()
        ->forModule('cms', 'menu')
        ->fullCrud();
    $this->adminRole = Role::factory()->has($permission)->create([
        'name' => 'Administrator',
        'slug' => 'admin',
        'level' => 5,
    ]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);
});

describe('Model Factory', function () {
    it('can create a menu using factory', function () {
        $menu = CmsMenu::factory()->create([
            'name' => 'Main Navigation',
            'position' => 'header',
        ]);

        expect($menu->name)->toBe('Main Navigation')
            ->and($menu->position)->toBe('header');

        $this->assertDatabaseHas('cms_menus', ['position' => 'header']);
    });

    it('can create a menu item using factory', function () {
        $menu = CmsMenu::factory()->create();
        $item = CmsMenuItem::factory()->create([
            'menu_id' => $menu->id,
            'label' => 'Home',
            'target_type' => 'url',
            'target_url' => '/',
        ]);

        expect($item->label)->toBe('Home');
        $this->assertDatabaseHas('cms_menu_items', ['menu_id' => $menu->id, 'label' => 'Home']);
    });
});

describe('Page Rendering', function () {
    it('renders the menu list', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.menu.index'))
            ->assertStatus(200);
    });

    it('renders the menu item manager', function () {
        $menu = CmsMenu::factory()->create();

        $this->actingAs($this->user)
            ->get(backend_route('cms.menu.items', ['menuId' => $menu->id]))
            ->assertStatus(200);
    });
});

describe('Create Menu', function () {
    it('can create a menu via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-list')
            ->set('form.name', 'Footer Menu')
            ->set('form.position', 'footer')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('cms_menus', [
            'name' => 'Footer Menu',
            'position' => 'footer',
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-list')
            ->set('form.name', '')
            ->set('form.position', '')
            ->call('save')
            ->assertHasErrors([
                'form.name' => 'required',
                'form.position' => 'required',
            ]);
    });
});

describe('Delete Menu', function () {
    it('can delete a menu via Livewire component', function () {
        $menu = CmsMenu::factory()->create(['name' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-list')
            ->call('delete', $menu->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_menus', ['id' => $menu->id]);
    });
});
