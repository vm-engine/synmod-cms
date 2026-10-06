<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

function cfDevUser(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('slug', 'dev')->first() ?? Role::factory()->dev()->create());

    return $user;
}

function cfSectionEditor(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(
        Role::factory()->has(RolePermission::factory()->forModule('cms', 'article', 'section')->fullCrud())
            ->create(['level' => 5, 'can_access_backend' => true])
    );

    return $user;
}

it('adds and removes fields and options for devs', function () {
    $section = CmsSection::factory()->create();

    Livewire::actingAs(cfDevUser())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->call('addField')
        ->assertSet('form.fieldSchema.0.type', 'text')
        ->set('form.fieldSchema.0.type', 'select')
        ->call('addOption', 0)
        ->assertCount('form.fieldSchema.0.options', 1)
        ->call('removeOption', 0, 0)
        ->assertCount('form.fieldSchema.0.options', 0)
        ->call('removeField', 0)
        ->assertCount('form.fieldSchema', 0);
});

it('forbids schema actions for non-devs and hides the card', function () {
    $section = CmsSection::factory()->create();

    Livewire::actingAs(cfSectionEditor())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->assertDontSee(__('cms::labels.custom_fields_schema'))
        ->call('addField')
        ->assertForbidden();
});

it('shows schema validation errors inside the definitions card', function () {
    $section = CmsSection::factory()->create();

    Livewire::actingAs(cfDevUser())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->call('addField')
        ->set('form.fieldSchema.0.name', 'Bad Name')
        ->set('form.fieldSchema.0.label', 'X')
        ->call('save')
        ->assertHasErrors(['form.fieldSchema.0.name'])
        ->assertSeeHtml('<p class="form-error">');
});

it('marks fields whose type is locked', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [['name' => 'a', 'label' => 'A', 'type' => 'text']]);
    app(CmsCustomFieldService::class)->saveValues(CmsArticle::factory()->create(['section_id' => $section->id]), ['en' => ['a' => 'x']]);

    Livewire::actingAs(cfDevUser())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->assertSee(__('cms::labels.field_type_locked'));
});

it('adds value keys for fields defined live so datepickers can entangle them', function () {
    $section = CmsSection::factory()->create();

    $component = Livewire::actingAs(cfDevUser())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->call('addField')
        ->set('form.fieldSchema.0.name', 'event_date')
        ->set('form.fieldSchema.0.type', 'date')
        ->call('addField')
        ->set('form.fieldSchema.1.name', 'tags')
        ->set('form.fieldSchema.1.type', 'checkboxes');

    foreach (CmsLocale::all() as $locale) {
        $values = $component->get("form.customFieldValues.{$locale}");
        expect($values)->toHaveKey('event_date')->toHaveKey('tags')
            ->and($values['tags'])->toBe([]);
    }
});
