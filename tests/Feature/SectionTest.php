<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permission = RolePermission::factory()
        ->forModule('cms', 'article', 'section')
        ->fullCrud();
    $this->adminRole = Role::factory()->has($permission)->create([
        'name' => 'Administrator',
        'slug' => 'admin',
        'level' => 5,
        'can_access_backend' => true,
    ]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);
});

describe('Model Factory', function () {
    it('can create a section using factory', function () {
        $section = CmsSection::factory()->create([
            'title' => 'Blog',
            'slug' => 'blog',
        ]);

        expect($section->title)->toBe('Blog')
            ->and($section->slug)->toBe('blog');

        $this->assertDatabaseHas('cms_sections', ['slug' => 'blog']);
    });
});

describe('Page Rendering', function () {
    it('renders the section list', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.section.index'))
            ->assertStatus(200);
    });

    it('renders the add section form', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.section.form'))
            ->assertStatus(200);
    });

    it('renders the edit section form', function () {
        $section = CmsSection::factory()->create();

        $this->actingAs($this->user)
            ->get(backend_route('cms.section.form', ['id' => $section->id]))
            ->assertStatus(200);
    });
});

describe('Create Section', function () {
    it('can create a section via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.section-form')
            ->set('form.title', 'News')
            ->set('form.slug', 'news')
            ->set('form.status', 'published')
            ->call('save')
            ->assertRedirect(backend_route('cms.section.index'));

        $this->assertDatabaseHas('cms_sections', [
            'title' => 'News',
            'slug' => 'news',
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.section-form')
            ->set('form.title', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'required',
                'form.slug' => 'required',
            ]);
    });
});

describe('Update Section', function () {
    it('can update a section via Livewire component', function () {
        $section = CmsSection::factory()->create([
            'title' => 'Old News',
            'slug' => 'old-news',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.section-form', ['id' => $section->id])
            ->assertSet('form.title', 'Old News')
            ->set('form.title', 'Latest News')
            ->set('form.slug', 'latest-news')
            ->call('save')
            ->assertRedirect(backend_route('cms.section.index'));

        $this->assertDatabaseHas('cms_sections', [
            'id' => $section->id,
            'title' => 'Latest News',
        ]);
    });
});

describe('Delete Section', function () {
    it('can delete a section via Livewire component', function () {
        $section = CmsSection::factory()->create(['title' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.section-list')
            ->call('delete', $section->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_sections', ['id' => $section->id]);
    });
});

describe('ACL', function () {
    it('denies access to section list without permission', function () {
        $noPermRole = Role::factory()->create(['name' => 'No Perm', 'slug' => 'no-perm', 'level' => 9]);
        $noPermUser = User::factory()->create();
        $noPermUser->roles()->attach($noPermRole);

        $this->actingAs($noPermUser)
            ->get(backend_route('cms.section.index'))
            ->assertStatus(302);
    });
});

describe('Fallback Route Sync', function () {
    it('registers fallback route when published section is created', function () {
        CmsSection::factory()->create([
            'slug' => 'tech-news',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'tech-news',
            'module' => 'cms',
        ]);
    });

    it('does not register fallback route for draft sections', function () {
        CmsSection::factory()->draft()->create(['slug' => 'draft-section']);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'draft-section',
        ]);
    });

    it('removes fallback route when section is deleted', function () {
        $section = CmsSection::factory()->create([
            'slug' => 'removable',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'removable']);

        $section->delete();

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'removable']);
    });
});
