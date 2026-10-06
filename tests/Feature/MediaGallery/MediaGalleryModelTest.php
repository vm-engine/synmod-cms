<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Models\CmsPage;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

it('builds slides from images that still have a file, in sort order', function () {
    $gallery = CmsMediaGallery::factory()->for(CmsPage::factory(), 'owner')->create(['title' => 'Trip']);
    CmsMediaGalleryImage::factory()->for($gallery, 'gallery')->create(['file_id' => mgImageFile('b.jpg')->id, 'caption' => 'Second', 'sort_order' => 2]);
    CmsMediaGalleryImage::factory()->for($gallery, 'gallery')->create(['file_id' => mgImageFile('a.jpg')->id, 'caption' => null, 'sort_order' => 1]);
    CmsMediaGalleryImage::factory()->for($gallery, 'gallery')->create(['file_id' => null, 'sort_order' => 0]);

    $slides = $gallery->fresh()->load('images.file')->slides();

    expect($slides)->toHaveCount(2)
        ->and($slides[0]['caption'])->toBe('')
        ->and($slides[0]['alt'])->toBe('Trip')
        ->and($slides[1]['caption'])->toBe('Second')
        ->and($slides[1]['alt'])->toBe('Second')
        ->and($slides[1]['url'])->toContain('b.jpg');
});

it('exposes its insert tag', function () {
    $gallery = CmsMediaGallery::factory()->for(CmsPage::factory(), 'owner')->create(['key' => 'intro-shots']);

    expect($gallery->tag())->toBe('[gallery:intro-shots]');
});

it('cascades images when the gallery is deleted and nulls file_id when the FM file is deleted', function () {
    $gallery = CmsMediaGallery::factory()->for(CmsPage::factory(), 'owner')->create();
    $file = mgImageFile();
    $image = CmsMediaGalleryImage::factory()->for($gallery, 'gallery')->create(['file_id' => $file->id]);

    $file->delete();
    expect($image->fresh()->file_id)->toBeNull();

    $gallery->delete();
    expect(CmsMediaGalleryImage::count())->toBe(0);
});
