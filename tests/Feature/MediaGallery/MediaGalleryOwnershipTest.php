<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

uses(RefreshDatabase::class);

it('enables pages through their root page only', function () {
    $root = CmsPage::factory()->create(['config' => ['media_gallery' => true]]);
    $child = CmsPage::factory()->create(['parent_id' => $root->id, 'config' => ['media_gallery' => false]]);
    $grandchild = CmsPage::factory()->create(['parent_id' => $child->id]);
    $otherRoot = CmsPage::factory()->create();

    expect($root->mediaGalleriesEnabled())->toBeTrue()
        ->and($child->mediaGalleriesEnabled())->toBeTrue()
        ->and($grandchild->mediaGalleriesEnabled())->toBeTrue()
        ->and($otherRoot->mediaGalleriesEnabled())->toBeFalse();
});

it('enables articles through their section and download files through their download', function () {
    $section = CmsSection::factory()->create(['config' => ['media_gallery' => true]]);
    $download = CmsDownload::factory()->create(['config' => ['media_gallery' => true]]);

    expect(CmsArticle::factory()->create(['section_id' => $section->id])->mediaGalleriesEnabled())->toBeTrue()
        ->and(CmsArticle::factory()->create()->mediaGalleriesEnabled())->toBeFalse()
        ->and(CmsDownloadFile::factory()->create(['download_id' => $download->id])->mediaGalleriesEnabled())->toBeTrue()
        ->and(CmsDownloadFile::factory()->create()->mediaGalleriesEnabled())->toBeFalse();
});

it("deletes an item's galleries with the item", function (Model $item) {
    CmsMediaGallery::factory()->count(2)->create(['owner_type' => $item->getMorphClass(), 'owner_id' => $item->id]);
    $survivor = CmsMediaGallery::factory()->create();

    $item->delete();

    expect(CmsMediaGallery::pluck('id')->all())->toBe([$survivor->id]);
})->with([
    'page' => fn () => CmsPage::factory()->create(),
    'article' => fn () => CmsArticle::factory()->create(),
    'download file' => fn () => CmsDownloadFile::factory()->create(),
]);
