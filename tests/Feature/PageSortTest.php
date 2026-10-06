<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

require_once __DIR__.'/MediaGallery/helpers.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    $permission = RolePermission::factory()->forModule('cms', 'page')->fullCrud();
    $role = Role::factory()->has($permission)->create([
        'name' => 'Administrator',
        'slug' => 'admin',
        'level' => 5,
        'can_access_backend' => true,
    ]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($role);

    $this->parent = CmsPage::factory()->create(['slug' => 'parent', 'full_slug' => 'parent', 'sort_order' => 0]);
    $this->a = CmsPage::factory()->create(['parent_id' => $this->parent->id, 'slug' => 'a', 'full_slug' => 'parent/a', 'sort_order' => 0]);
    $this->b = CmsPage::factory()->create(['parent_id' => $this->parent->id, 'slug' => 'b', 'full_slug' => 'parent/b', 'sort_order' => 1]);
    $this->c = CmsPage::factory()->create(['parent_id' => $this->parent->id, 'slug' => 'c', 'full_slug' => 'parent/c', 'sort_order' => 2]);
});

describe('CmsPageService::reorderChildren', function () {
    it('persists the new order of children', function () {
        app(CmsPageService::class)->reorderChildren($this->parent->id, [$this->c->id, $this->a->id, $this->b->id]);

        expect($this->parent->children()->pluck('id')->all())
            ->toBe([$this->c->id, $this->a->id, $this->b->id]);
    });

    it('ignores ids that belong to another parent and never re-parents', function () {
        $other = CmsPage::factory()->create(['slug' => 'other', 'full_slug' => 'other', 'sort_order' => 5]);

        app(CmsPageService::class)->reorderChildren($this->parent->id, [$other->id, $this->b->id, $this->a->id, $this->c->id]);

        $other->refresh();
        expect($other->parent_id)->toBeNull()
            ->and($other->sort_order)->toBe(5)
            ->and($this->parent->children()->pluck('id')->all())
            ->toBe([$this->b->id, $this->a->id, $this->c->id]);
    });

    it('reorders root pages when parent id is null', function () {
        $second = CmsPage::factory()->create(['slug' => 'second', 'full_slug' => 'second', 'sort_order' => 1]);

        app(CmsPageService::class)->reorderChildren(null, [$second->id, $this->parent->id, $this->a->id]);

        expect($second->refresh()->sort_order)->toBe(0)
            ->and($this->parent->refresh()->sort_order)->toBe(1)
            ->and($this->a->refresh()->sort_order)->toBe(0);
    });
});

describe('page-list drag sorting', function () {
    it('saves a new child order via updateOrder', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-list')
            ->call('updateOrder', $this->parent->id, [$this->b->id, $this->c->id, $this->a->id])
            ->assertDispatched('notify');

        expect($this->parent->children()->pluck('id')->all())
            ->toBe([$this->b->id, $this->c->id, $this->a->id]);
    });

    it('shows drag handles on sortable child rows', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-list')
            ->assertSeeHtml('data-sort-group="'.$this->parent->id.'"')
            ->assertSeeHtml('class="drag-handle');
    });

    it('forbids reordering without update permission', function () {
        $viewer = User::factory()->create();
        $viewer->roles()->attach(Role::factory()->has(
            RolePermission::factory()->forModule('cms', 'page')->readOnly()
        )->create(['slug' => 'viewer', 'level' => 3, 'can_access_backend' => true]));

        Livewire::actingAs($viewer)
            ->test('cms::backend.page-list')
            ->assertDontSeeHtml('class="drag-handle')
            ->call('updateOrder', $this->parent->id, [$this->c->id, $this->b->id, $this->a->id])
            ->assertForbidden();

        expect($this->a->refresh()->sort_order)->toBe(0);
    });

    it('hides root drag handles from non-devs and forbids root reordering', function () {
        $second = CmsPage::factory()->create(['slug' => 'second', 'full_slug' => 'second', 'sort_order' => 1]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-list')
            ->assertDontSeeHtml('data-root-handle')
            ->call('updateOrder', null, [$second->id, $this->parent->id])
            ->assertForbidden();

        expect($second->refresh()->sort_order)->toBe(1);
    });

    it('lets a dev drag-sort root pages', function () {
        $second = CmsPage::factory()->create(['slug' => 'second', 'full_slug' => 'second', 'sort_order' => 1]);

        Livewire::actingAs(mgDev())
            ->test('cms::backend.page-list')
            ->assertSeeHtml('data-root-handle')
            ->call('updateOrder', null, [$second->id, $this->parent->id])
            ->assertDispatched('notify');

        expect($second->refresh()->sort_order)->toBe(0);
    });

    it('hides root drag handles while filtering by status', function () {
        CmsPage::factory()->create(['slug' => 'second', 'full_slug' => 'second', 'sort_order' => 1]);

        Livewire::actingAs(mgDev())
            ->test('cms::backend.page-list')
            ->set('filterStatus', 'published')
            ->assertDontSeeHtml('data-root-handle');
    });
});
