<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $permission = RolePermission::factory()
        ->forModule('cms', 'tag')
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
    it('can create a tag using factory', function () {
        $tag = CmsTag::factory()->create([
            'name' => 'Laravel',
            'slug' => 'laravel',
        ]);

        expect($tag->name)->toBe('Laravel')
            ->and($tag->slug)->toBe('laravel');

        $this->assertDatabaseHas('cms_tags', ['slug' => 'laravel']);
    });
});

describe('Page Rendering', function () {
    it('renders the tag list', function () {
        $this->actingAs($this->user)
            ->get(backend_route('cms.tag.index'))
            ->assertStatus(200);
    });
});

describe('Create Tag', function () {
    it('can create a tag via Livewire component', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.tag-list')
            ->set('form.name', 'PHP')
            ->set('form.slug', 'php')
            ->call('save')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('cms_tags', [
            'name' => 'PHP',
            'slug' => 'php',
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.tag-list')
            ->set('form.name', '')
            ->set('form.slug', '')
            ->call('save')
            ->assertHasErrors([
                'form.name' => 'required',
                'form.slug' => 'required',
            ]);
    });

    it('validates slug uniqueness', function () {
        CmsTag::factory()->create(['name' => 'Existing', 'slug' => 'existing-tag']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.tag-list')
            ->set('form.name', 'Another')
            ->set('form.slug', 'existing-tag')
            ->call('save')
            ->assertHasErrors(['form.slug' => 'unique']);
    });
});

describe('Delete Tag', function () {
    it('can delete a tag via Livewire component', function () {
        $tag = CmsTag::factory()->create(['name' => 'To Delete']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.tag-list')
            ->call('delete', $tag->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_tags', ['id' => $tag->id]);
    });
});
