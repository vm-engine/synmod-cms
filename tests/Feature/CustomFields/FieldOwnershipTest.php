<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Enums\CmsFieldType;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsField;
use VmEngine\Cms\Models\CmsFieldValue;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

uses(RefreshDatabase::class);

it('resolves each item to its schema owner', function () {
    $root = CmsPage::factory()->create(['parent_id' => null]);
    $child = CmsPage::factory()->create(['parent_id' => $root->id]);
    $grandchild = CmsPage::factory()->create(['parent_id' => $child->id]);
    $article = CmsArticle::factory()->create();
    $file = CmsDownloadFile::factory()->create();
    $image = CmsAlbumImage::factory()->create();
    $section = CmsSection::factory()->create();

    expect($root->fieldSchemaOwner()->is($root))->toBeTrue()
        ->and($grandchild->fieldSchemaOwner()->is($root))->toBeTrue()
        ->and($section->fieldSchemaOwner()->is($section))->toBeTrue()
        ->and($article->fieldSchemaOwner()->is($article->section))->toBeTrue()
        ->and($file->fieldSchemaOwner()->is($file->download))->toBeTrue()
        ->and($image->fieldSchemaOwner()->is($image->album))->toBeTrue();
});

it('deletes fields (and so their values) with the owner, and values with the item', function () {
    $section = CmsSection::factory()->create();
    $field = $section->customFields()->create(['name' => 'x', 'label' => 'X', 'type' => CmsFieldType::Text]);
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);
    $other = CmsArticle::factory()->create(['section_id' => $section->id]);

    foreach ([$article, $other] as $item) {
        CmsFieldValue::create(['field_id' => $field->id, 'valuable_type' => $item->getMorphClass(), 'valuable_id' => $item->id, 'locale' => 'en', 'value' => 'v']);
    }

    $article->delete();
    expect(CmsFieldValue::count())->toBe(1);

    $section->delete();
    expect(CmsField::count())->toBe(0)->and(CmsFieldValue::count())->toBe(0);
});
