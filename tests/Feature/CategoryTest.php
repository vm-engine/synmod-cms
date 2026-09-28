<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $sectionPerm = RolePermission::factory()->forModule('cms', 'article', 'section')->fullCrud();
    $categoryPerm = RolePermission::factory()->forModule('cms', 'article', 'category')->fullCrud();
    $this->adminRole = Role::factory()
        ->has($sectionPerm)
        ->has($categoryPerm)
        ->create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);

    $this->section = CmsSection::factory()->create([
        'title' => 'Blog',
        'slug' => 'blog',
        'status' => 'published',
    ]);
});

describe('Model Factory', function () {
    it('can create a category using factory', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'title' => 'Tutorials',
            'slug' => 'tutorials',
        ]);

        expect($category->title)->toBe('Tutorials')
            ->and($category->slug)->toBe('tutorials')
            ->and($category->section_id)->toBe($this->section->id);

        $this->assertDatabaseHas('cms_categories', ['slug' => 'tutorials']);
    });
});

describe('Page Rendering', function () {
    it('renders the category list for a section', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.category.index', ['sectionId' => $this->section->id]))
            ->assertStatus(200);
    });

    it('renders the add category form', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.category.form', ['sectionId' => $this->section->id]))
            ->assertStatus(200);
    });

    it('renders the edit category form', function () {
        $category = CmsCategory::factory()->create(['section_id' => $this->section->id]);

        $this->actingAs($this->user)
            ->get(backend_route('cms.category.edit', ['sectionId' => $this->section->id, 'id' => $category->id]))
            ->assertStatus(200);
    });
});

describe('Create Category', function () {
    it('can create a category via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.category-form', ['sectionId' => $this->section->id])
            ->set('form.title', 'Tips & Tricks')
            ->set('form.slug', 'tips-tricks')
            ->set('form.status', 'published')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('cms_categories', [
            'title' => 'Tips & Tricks',
            'slug' => 'tips-tricks',
            'section_id' => $this->section->id,
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.category-form', ['sectionId' => $this->section->id])
            ->set('form.title', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.title' => 'required',
                'form.slug' => 'required',
            ]);
    });
});

describe('Update Category', function () {
    it('can update a category via Livewire component', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'title' => 'Old Category',
            'slug' => 'old-category',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.category-form', ['sectionId' => $this->section->id, 'id' => $category->id])
            ->assertSet('form.title', 'Old Category')
            ->set('form.title', 'New Category')
            ->set('form.slug', 'new-category')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('cms_categories', [
            'id' => $category->id,
            'title' => 'New Category',
        ]);
    });
});

describe('Delete Category', function () {
    it('can delete a category via Livewire component', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'title' => 'To Delete',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.category-list', ['sectionId' => $this->section->id])
            ->call('delete', $category->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_categories', ['id' => $category->id]);
    });
});

describe('ACL', function () {
    it('denies access to category list without permission', function () {
        $noPermRole = Role::factory()->create(['name' => 'No Perm', 'slug' => 'no-perm', 'level' => 9]);
        $noPermUser = User::factory()->create();
        $noPermUser->roles()->attach($noPermRole);

        $this->actingAs($noPermUser)
            ->get(backend_route('cms.category.index', ['sectionId' => $this->section->id]))
            ->assertStatus(302);
    });
});

describe('Fallback Route Sync', function () {
    it('registers fallback route when published category is created', function () {
        CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'slug' => 'tutorials',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', [
            'slug' => 'blog/tutorials',
            'module' => 'cms',
        ]);
    });

    it('does not register fallback route for draft categories', function () {
        CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'slug' => 'draft-cat',
            'status' => 'draft',
        ]);

        $this->assertDatabaseMissing('fallback_routes', [
            'slug' => 'blog/draft-cat',
        ]);
    });

    it('removes fallback route when category is deleted', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'slug' => 'removable',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'blog/removable']);

        $category->delete();

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'blog/removable']);
    });

    it('updates fallback route when category slug changes', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'slug' => 'original-slug',
            'status' => 'published',
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'blog/original-slug']);

        $category->update(['slug' => 'new-slug']);

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'blog/original-slug']);
        $this->assertDatabaseHas('fallback_routes', ['slug' => 'blog/new-slug']);
    });

    it('cascades article fallback routes when category slug changes', function () {
        $category = CmsCategory::factory()->create([
            'section_id' => $this->section->id,
            'slug' => 'tutorials',
            'status' => 'published',
        ]);

        CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => $category->id,
            'slug' => 'my-article',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $this->assertDatabaseHas('fallback_routes', ['slug' => 'blog/tutorials/my-article']);

        $category->update(['slug' => 'guides']);

        $this->assertDatabaseMissing('fallback_routes', ['slug' => 'blog/tutorials/my-article']);
        $this->assertDatabaseHas('fallback_routes', ['slug' => 'blog/guides/my-article']);
    });
});
