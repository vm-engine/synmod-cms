<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsFieldValue;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsCustomFieldService;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);
});

/** @return list<array<string, mixed>> */
function cfSchema(): array
{
    return [
        ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'required' => true],
        ['name' => 'colour', 'label' => 'Colour', 'type' => 'select', 'options' => [['value' => 'r', 'label' => 'Red'], ['value' => 'g', 'label' => 'Green']], 'default' => 'g'],
        ['name' => 'tags', 'label' => 'Tags', 'type' => 'checkboxes', 'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']]],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text'],
    ];
}

function cfService(): CmsCustomFieldService
{
    return app(CmsCustomFieldService::class);
}

it('creates, updates and removes definitions while keeping values on rename', function () {
    $section = CmsSection::factory()->create();
    cfService()->syncSchema($section, cfSchema());
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);
    cfService()->saveValues($article, ['en' => ['subtitle' => 'Hello']]);

    $defs = $section->customFields()->get()->map->toDefinition()->all();
    $defs[3]['name'] = 'tagline';
    unset($defs[0]);
    cfService()->syncSchema($section, array_values($defs));

    expect($section->customFields()->pluck('name')->all())->toBe(['colour', 'tags', 'tagline'])
        ->and($article->fresh()->field('tagline'))->toBe('Hello');
});

it('rejects schema on a non-root page', function () {
    $child = CmsPage::factory()->create(['parent_id' => CmsPage::factory()->create()->id]);

    cfService()->syncSchema($child, cfSchema());
})->throws(InvalidArgumentException::class);

it('locks the type of a field that has values', function () {
    $section = CmsSection::factory()->create();
    cfService()->syncSchema($section, cfSchema());
    cfService()->saveValues(CmsArticle::factory()->create(['section_id' => $section->id]), ['en' => ['subtitle' => 'x']]);

    $defs = $section->customFields()->get()->map->toDefinition()->all();
    $defs[3]['type'] = 'number';
    cfService()->syncSchema($section, $defs);
})->throws(ValidationException::class);

it('requires options for option types', function () {
    cfService()->syncSchema(CmsSection::factory()->create(), [['name' => 'c', 'label' => 'C', 'type' => 'select', 'options' => []]]);
})->throws(ValidationException::class);

it('stores typed rows, one per checkbox option, and reads with locale fallback then default', function () {
    $section = CmsSection::factory()->create();
    cfService()->syncSchema($section, cfSchema());
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);

    cfService()->saveValues($article, [
        'en' => ['price' => '12.5', 'tags' => ['a', 'b', 'zzz'], 'subtitle' => 'Hello'],
        'id' => ['subtitle' => 'Halo'],
    ]);

    expect(CmsFieldValue::where('value_index', 'a')->exists())->toBeTrue()
        ->and((float) CmsFieldValue::whereNotNull('value_number')->value('value_number'))->toBe(12.5)
        ->and($article->field('price'))->toBe(12.5)
        ->and($article->field('tags'))->toBe(['a', 'b'])
        ->and($article->field('colour'))->toBe('g')
        ->and($article->field('subtitle', 'id'))->toBe('Halo')
        ->and($article->field('price', 'id'))->toBe(12.5)
        ->and($article->fields('en'))->toHaveKeys(['price', 'colour', 'tags', 'subtitle']);
});

it('drops values of fields outside the current schema when saving (item moved)', function () {
    [$a, $b] = [CmsSection::factory()->create(), CmsSection::factory()->create()];
    cfService()->syncSchema($a, cfSchema());
    $article = CmsArticle::factory()->create(['section_id' => $a->id]);
    cfService()->saveValues($article, ['en' => ['subtitle' => 'x']]);

    $article->update(['section_id' => $b->id]);
    cfService()->saveValues($article->fresh(), []);

    expect(CmsFieldValue::count())->toBe(0);
});

it('builds form values (stored, else primary default) and value rules', function () {
    $section = CmsSection::factory()->create();
    cfService()->syncSchema($section, cfSchema());
    $schema = $section->customFields()->get();

    $values = cfService()->formValues(null, $schema);
    expect($values['en']['colour'])->toBe('g')->and($values['id']['colour'])->toBeNull()->and($values['id']['tags'])->toBe([]);

    $rules = cfService()->valueRules($schema->map->toDefinition()->all());
    expect(Validator::make(['customFieldValues' => ['en' => ['price' => null], 'id' => []]], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['customFieldValues' => ['en' => ['price' => 3, 'colour' => 'x'], 'id' => []]], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['customFieldValues' => ['en' => ['price' => 3, 'tags' => ['a']], 'id' => ['price' => null]]], $rules)->fails())->toBeFalse();
});
