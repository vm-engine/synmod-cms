<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $categoryPermission = RolePermission::factory()->forModule('cms', 'download', 'category')->fullCrud();
    $filePermission = RolePermission::factory()->forModule('cms', 'download', 'file')->fullCrud();
    $this->adminRole = Role::factory()
        ->has($categoryPermission)
        ->has($filePermission)
        ->create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);
});

describe('Model Factory', function () {
    it('can create a download using factory', function () {
        $download = CmsDownload::factory()->create([
            'title' => 'Annual Report 2024',
            'slug' => 'annual-report-2024',
        ]);

        expect($download->title)->toBe('Annual Report 2024');
        $this->assertDatabaseHas('cms_downloads', ['slug' => 'annual-report-2024']);
    });
});

describe('Page Rendering', function () {
    it('renders the download list', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.download.index'))
            ->assertStatus(200);
    });

    it('renders the add download form', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.download.form'))
            ->assertStatus(200);
    });

    it('renders the edit download form', function () {
        $download = CmsDownload::factory()->create();

        $this->actingAs($this->user)
            ->get(backend_route('cms.download.form', ['id' => $download->id]))
            ->assertStatus(200);
    });
});

describe('Create Download', function () {
    it('can create a download via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.download-form')
            ->set('form.title', 'User Guide')
            ->set('form.slug', 'user-guide')
            ->set('form.status', 'active')
            ->set('form.access', 'public')
            ->call('save')
            ->assertRedirect(backend_route('cms.download.index'));

        $this->assertDatabaseHas('cms_downloads', [
            'title' => 'User Guide',
            'slug' => 'user-guide',
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.download-form')
            ->set('form.title', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'required',
                'form.slug' => 'required',
            ]);
    });
});

describe('Update Download', function () {
    it('can update a download via Livewire component', function () {
        $download = CmsDownload::factory()->create([
            'title' => 'Old Manual',
            'slug' => 'old-manual',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.download-form', ['id' => $download->id])
            ->assertSet('form.title', 'Old Manual')
            ->set('form.title', 'New Manual')
            ->set('form.slug', 'new-manual')
            ->call('save')
            ->assertRedirect(backend_route('cms.download.index'));

        $this->assertDatabaseHas('cms_downloads', [
            'id' => $download->id,
            'title' => 'New Manual',
        ]);
    });
});

describe('Delete Download', function () {
    it('can delete a download via Livewire component', function () {
        $download = CmsDownload::factory()->create(['title' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.download-list')
            ->call('delete', $download->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_downloads', ['id' => $download->id]);
    });
});

describe('ACL', function () {
    it('denies access to download list without permission', function () {
        $noPermRole = Role::factory()->create(['name' => 'No Perm', 'slug' => 'no-perm', 'level' => 9]);
        $noPermUser = User::factory()->create();
        $noPermUser->roles()->attach($noPermRole);

        $this->actingAs($noPermUser)
            ->get(backend_route('cms.download.index'))
            ->assertStatus(302);
    });
});

describe('Fallback Route Sync', function () {
    it('registers fallback route for an active download', function () {
        CmsDownload::factory()->create([
            'slug' => 'active-download',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'downloads/active-download',
            'module' => 'cms',
        ]);
    });

    it('does not register fallback route for an inactive download', function () {
        CmsDownload::factory()->inactive()->create([
            'slug' => 'inactive-download',
        ]);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'downloads/inactive-download',
        ]);
    });

    it('removes fallback route when download is deleted', function () {
        $download = CmsDownload::factory()->create([
            'slug' => 'deletable-download',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'downloads/deletable-download']);

        $download->delete();

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'downloads/deletable-download']);
    });
});
