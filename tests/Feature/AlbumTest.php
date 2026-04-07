<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $albumPermission = RolePermission::factory()->forModule('cms', 'gallery', 'album')->fullCrud();
    $bannerPermission = RolePermission::factory()->forModule('cms', 'gallery', 'banner')->fullCrud();
    $imagePermission = RolePermission::factory()->forModule('cms', 'gallery', 'image')->fullCrud();
    $this->adminRole = Role::factory()
        ->has($albumPermission)
        ->has($bannerPermission)
        ->has($imagePermission)
        ->create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);
});

describe('Model Factory', function () {
    it('can create an album using factory', function () {
        $album = CmsAlbum::factory()->create([
            'title' => 'Summer 2024',
            'slug' => 'summer-2024',
        ]);

        expect($album->title)->toBe('Summer 2024');
        $this->assertDatabaseHas('cms_albums', ['slug' => 'summer-2024']);
    });
});

describe('Page Rendering', function () {
    it('renders the album list', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.album.index'))
            ->assertStatus(200);
    });

    it('renders the add album form', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.album.form'))
            ->assertStatus(200);
    });

    it('renders the edit album form', function () {
        $album = CmsAlbum::factory()->create();

        $this->actingAs($this->user)
            ->get(backend_route('cms.album.form', ['id' => $album->id]))
            ->assertStatus(200);
    });
});

describe('Create Album', function () {
    it('can create an album via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.album-form')
            ->set('form.title', 'My Gallery')
            ->set('form.slug', 'my-gallery')
            ->set('form.type', 'normal')
            ->set('form.status', 'active')
            ->call('save')
            ->assertRedirect(backend_route('cms.album.index'));

        $this->assertDatabaseHas('cms_albums', [
            'title' => 'My Gallery',
            'slug' => 'my-gallery',
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.album-form')
            ->set('form.title', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'required',
                'form.slug' => 'required',
            ]);
    });
});

describe('Update Album', function () {
    it('can update an album via Livewire component', function () {
        $album = CmsAlbum::factory()->create([
            'title' => 'Old Gallery',
            'slug' => 'old-gallery',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.album-form', ['id' => $album->id])
            ->assertSet('form.title', 'Old Gallery')
            ->set('form.title', 'New Gallery')
            ->set('form.slug', 'new-gallery')
            ->call('save')
            ->assertRedirect(backend_route('cms.album.index'));

        $this->assertDatabaseHas('cms_albums', [
            'id' => $album->id,
            'title' => 'New Gallery',
        ]);
    });
});

describe('Delete Album', function () {
    it('can delete an album via Livewire component', function () {
        $album = CmsAlbum::factory()->create(['title' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.album-list')
            ->call('delete', $album->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_albums', ['id' => $album->id]);
    });
});

describe('ACL', function () {
    it('denies access to album list without permission', function () {
        $noPermRole = Role::factory()->create(['name' => 'No Perm', 'slug' => 'no-perm', 'level' => 9]);
        $noPermUser = User::factory()->create();
        $noPermUser->roles()->attach($noPermRole);

        $this->actingAs($noPermUser)
            ->get(backend_route('cms.album.index'))
            ->assertStatus(302);
    });
});

describe('Fallback Route Sync', function () {
    it('registers fallback route for active normal album', function () {
        CmsAlbum::factory()->create([
            'slug' => 'my-photos',
            'type' => AlbumType::Normal,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'gallery/my-photos',
            'module' => 'cms',
        ]);
    });

    it('does not register fallback route for banner albums', function () {
        CmsAlbum::factory()->create([
            'slug' => 'homepage-banner',
            'type' => AlbumType::Banner,
            'status' => 'active',
        ]);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'gallery/homepage-banner',
        ]);
    });

    it('does not register fallback route for inactive albums', function () {
        CmsAlbum::factory()->create([
            'slug' => 'inactive-album',
            'type' => AlbumType::Normal,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'gallery/inactive-album',
        ]);
    });

    it('removes fallback route when album is deleted', function () {
        $album = CmsAlbum::factory()->create([
            'slug' => 'deletable-album',
            'type' => AlbumType::Normal,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'gallery/deletable-album']);

        $album->delete();

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'gallery/deletable-album']);
    });
});
