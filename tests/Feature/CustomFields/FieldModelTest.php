<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Models\CmsSection;

uses(RefreshDatabase::class);

function cfMakeField(array $attributes = []): CmsField
{
    $section = CmsSection::factory()->create();

    return CmsField::create([
        'owner_type' => $section->getMorphClass(),
        'owner_id' => $section->id,
        'name' => 'price',
        'label' => 'Price',
        'type' => CmsFieldType::Number,
        ...$attributes,
    ]);
}

it('resolves __ prefixed label, help and option labels through the translator', function () {
    app('translator')->addLines(['labels.colour' => 'Colour', 'labels.red' => 'Red'], 'en', 'cmstest');

    $field = cfMakeField([
        'name' => 'colour', 'type' => CmsFieldType::Select,
        'label' => '__cmstest::labels.colour', 'help' => 'Pick one',
        'options' => [['value' => 'r', 'label' => '__cmstest::labels.red'], ['value' => 'g', 'label' => 'Green']],
    ]);

    expect($field->displayLabel())->toBe('Colour')
        ->and($field->displayHelp())->toBe('Pick one')
        ->and($field->optionLabel('r'))->toBe('Red')
        ->and($field->optionLabel('g'))->toBe('Green')
        ->and($field->optionLabel('zzz'))->toBe('zzz')
        ->and($field->optionValues())->toBe(['r', 'g']);
});

it('casts defaults and stored values per type', function () {
    expect(cfMakeField(['default' => '5'])->defaultValue())->toBe(5)
        ->and(cfMakeField(['name' => 'ok', 'type' => CmsFieldType::Checkbox, 'default' => '1'])->defaultValue())->toBeTrue()
        ->and(cfMakeField(['name' => 'tags', 'type' => CmsFieldType::Checkboxes, 'default' => '["a","b"]', 'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']]])->defaultValue())->toBe(['a', 'b'])
        ->and(cfMakeField(['name' => 'note', 'type' => CmsFieldType::Text])->defaultValue())->toBeNull()
        ->and(cfMakeField(['name' => 'n2'])->castValues(['2.50']))->toBe(2.5)
        ->and(cfMakeField(['name' => 'tags2', 'type' => CmsFieldType::Checkboxes])->castValues(['a', 'b']))->toBe(['a', 'b']);
});

it('knows per-type behaviour', function () {
    expect(CmsFieldType::Select->hasOptions())->toBeTrue()
        ->and(CmsFieldType::Text->hasOptions())->toBeFalse()
        ->and(CmsFieldType::Checkboxes->isMulti())->toBeTrue()
        ->and(CmsFieldType::Number->typedColumn())->toBe('value_number')
        ->and(CmsFieldType::Datetime->typedColumn())->toBe('value_date')
        ->and(CmsFieldType::Checkboxes->isSortable())->toBeFalse();
});
