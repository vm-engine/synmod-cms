<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\User;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);
});

function cfDev(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('slug', 'dev')->first() ?? Role::factory()->dev()->create());

    return $user;
}

it('lets a dev define fields and fill values on a new root page in one save', function () {
    Livewire::actingAs(cfDev())
        ->test('cms::backend.page-form')
        ->set('form.title', 'Products')->set('form.slug', 'products')->set('form.status', 'published')
        ->set('form.fieldSchema', [['id' => null, 'name' => 'price', 'label' => 'Price', 'type' => 'number', 'options' => [], 'default' => null, 'required' => true, 'help' => '', 'searchable' => false, 'filterable' => true, 'sortable' => true]])
        ->set('form.customFieldValues.en.price', '10')
        ->call('save')
        ->assertHasNoErrors();

    $page = CmsPage::where('slug', 'products')->firstOrFail();
    expect($page->customFields()->pluck('name')->all())->toBe(['price'])
        ->and($page->field('price'))->toBe(10);
});

it('validates required values on the primary locale', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true]]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->set('form.customFieldValues.en.code', '')
        ->call('save')
        ->assertHasErrors(['form.customFieldValues.en.code']);
});

it('ignores a schema tampered by a non-dev and keeps their values on the stored schema', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [['name' => 'code', 'label' => 'Code', 'type' => 'text', 'required' => true]]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->set('form.fieldSchema', [])
        ->set('form.customFieldValues.en.code', 'ABC')
        ->call('save')
        ->assertHasNoErrors();

    expect(CmsField::count())->toBe(1)->and($section->fresh()->field('code'))->toBe('ABC');
});

it('shows the schema manager to devs only, and not on child pages', function () {
    $root = CmsPage::factory()->create(['parent_id' => null]);
    $child = CmsPage::factory()->create(['parent_id' => $root->id]);

    Livewire::actingAs(cfDev())->test('cms::backend.page-form', ['id' => $root->id])->assertSee(__('cms::labels.custom_fields_schema'));
    Livewire::actingAs(cfDev())->test('cms::backend.page-form', ['id' => $child->id])->assertDontSee(__('cms::labels.custom_fields_schema'));
    Livewire::actingAs(cfAdminUser())->test('cms::backend.page-form', ['id' => $root->id])->assertDontSee(__('cms::labels.custom_fields_schema'));
});

it('gives child pages the root page fields', function () {
    $root = CmsPage::factory()->create(['parent_id' => null]);
    app(CmsCustomFieldService::class)->syncSchema($root, [['name' => 'sku', 'label' => 'SKU', 'type' => 'text']]);
    $child = CmsPage::factory()->create(['parent_id' => $root->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.page-form', ['id' => $child->id])
        ->set('form.customFieldValues.en.sku', 'S-1')
        ->set('form.customFieldValues.id.sku', 'S-1-ID')
        ->call('save')
        ->assertHasNoErrors();

    expect($child->fresh()->field('sku', 'id'))->toBe('S-1-ID');
});

it('saves album and download category values', function () {
    foreach ([['cms::backend.album-form', CmsAlbum::factory()->create()], ['cms::backend.download-form', CmsDownload::factory()->create()]] as [$component, $owner]) {
        app(CmsCustomFieldService::class)->syncSchema($owner, [['name' => 'note', 'label' => 'Note', 'type' => 'text']]);

        Livewire::actingAs(cfAdminUser())
            ->test($component, ['id' => $owner->id])
            ->set('form.customFieldValues.en.note', 'hello')
            ->call('save')
            ->assertHasNoErrors();

        expect($owner->fresh()->field('note'))->toBe('hello');
    }
});
