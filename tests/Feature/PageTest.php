<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permission = RolePermission::factory()
        ->forModule('cms', 'page')
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
    it('can create a page using factory', function () {
        $page = CmsPage::factory()->create([
            'title' => 'About Us',
            'slug' => 'about-us',
            'full_slug' => 'about-us',
            'status' => 'published',
        ]);

        expect($page->title)->toBe('About Us')
            ->and($page->slug)->toBe('about-us')
            ->and($page->full_slug)->toBe('about-us');

        $this->assertDatabaseHas('cms_pages', ['slug' => 'about-us']);
    });

    it('computes full_slug for nested pages', function () {
        $parent = CmsPage::factory()->create(['slug' => 'parent', 'full_slug' => 'parent']);
        $child = CmsPage::factory()->create([
            'parent_id' => $parent->id,
            'slug' => 'child',
            'full_slug' => 'parent/child',
        ]);

        expect($child->computeFullSlug())->toBe('parent/child');
    });
});

describe('Page Rendering', function () {
    it('renders the page list', function () {
        $response = $this->actingAs($this->user)
            ->get(backend_route('cms.page.index'));

        $response->assertStatus(200);
    });

    it('renders the add page form', function () {
        $response = $this->actingAs($this->user)
            ->get(backend_route('cms.page.form'));

        $response->assertStatus(200);
    });

    it('renders the edit page form with existing data', function () {
        $page = CmsPage::factory()->create();

        $response = $this->actingAs($this->user)
            ->get(backend_route('cms.page.form', ['id' => $page->id]));

        $response->assertStatus(200);
    });
});

describe('Create Page', function () {
    it('can create a page via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form')
            ->set('form.title', 'Contact Us')
            ->set('form.slug', 'contact-us')
            ->set('form.status', 'published')
            ->call('save')
            ->assertRedirect(backend_route('cms.page.index'));

        $this->assertDatabaseHas('cms_pages', [
            'title' => 'Contact Us',
            'slug' => 'contact-us',
            'full_slug' => 'contact-us',
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form')
            ->set('form.title', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'required',
                'form.slug' => 'required',
            ]);
    });

    it('validates slug format', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form')
            ->set('form.title', 'Test Page')
            ->set('form.slug', 'Invalid Slug!')
            ->set('form.status', 'draft')
            ->call('save')
            ->assertHasErrors(['form.slug']);
    });

    it('validates status values', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form')
            ->set('form.title', 'Test Page')
            ->set('form.slug', 'test-page')
            ->set('form.status', 'invalid-status')
            ->call('save')
            ->assertHasErrors(['form.status']);
    });
});

describe('Update Page', function () {
    it('can update a page via Livewire component', function () {
        $page = CmsPage::factory()->create([
            'title' => 'Old Title',
            'slug' => 'old-slug',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSet('form.title', 'Old Title')
            ->set('form.title', 'New Title')
            ->set('form.slug', 'new-slug')
            ->call('save')
            ->assertRedirect(backend_route('cms.page.index'));

        $this->assertDatabaseHas('cms_pages', [
            'id' => $page->id,
            'title' => 'New Title',
            'slug' => 'new-slug',
        ]);
    });
});

describe('Delete Page', function () {
    it('can delete a page via Livewire component', function () {
        $page = CmsPage::factory()->create(['title' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-list')
            ->call('delete', $page->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_pages', ['id' => $page->id]);
    });

    it('handles invalid delete token gracefully', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-list')
            ->call('delete', 'invalid-token')
            ->assertDispatched('notify');
    });
});

describe('Fallback Route Sync', function () {
    it('registers fallback route when published page is created', function () {
        CmsPage::factory()->create([
            'title' => 'About',
            'slug' => 'about',
            'full_slug' => 'about',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'about',
            'module' => 'cms',
        ]);
    });

    it('does not register fallback route for draft pages', function () {
        CmsPage::factory()->draft()->create([
            'slug' => 'draft-page',
            'full_slug' => 'draft-page',
        ]);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'draft-page',
        ]);
    });

    it('removes fallback route when page is deleted', function () {
        $page = CmsPage::factory()->create([
            'slug' => 'deletable',
            'full_slug' => 'deletable',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'deletable']);

        $page->delete();

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'deletable']);
    });

    it('removes fallback route when page is set to draft', function () {
        $page = CmsPage::factory()->create([
            'slug' => 'was-published',
            'full_slug' => 'was-published',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'was-published']);

        $page->update(['status' => 'draft']);

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'was-published']);
    });
});

describe('ACL', function () {
    it('denies access without permission', function () {
        $noPermRole = Role::factory()->create(['name' => 'No Perm', 'slug' => 'no-perm', 'level' => 9]);
        $noPermUser = User::factory()->create();
        $noPermUser->roles()->attach($noPermRole);

        $response = $this->actingAs($noPermUser)
            ->get(backend_route('cms.page.index'));

        $response->assertStatus(302);
    });
});
