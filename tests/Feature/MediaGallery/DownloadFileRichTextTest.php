<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Cms\Support\CmsUrl;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

it('purifies the description on update and on add', function () {
    $file = CmsDownloadFile::factory()->create();
    $service = app(CmsDownloadService::class);

    $service->updateFile($file, ['description' => '<p>Ok</p><script>alert(1)</script>']);
    expect($file->fresh()->description)->toBe('<p>Ok</p>');

    $added = $service->addFileToDownload($file->download_id, ['file_id' => mgImageFile()->id, 'description' => '<b onclick="x()">Hi</b>']);
    expect($added->description)->toContain('Hi')->not->toContain('onclick');
});

it('purifies translated descriptions', function () {
    expect((new CmsDownloadFile)->translatableHtmlFields())->toBe(['description']);
});

it('renders the description as html with galleries on the download page', function () {
    $download = CmsDownload::factory()->create(['config' => ['media_gallery' => true]]);
    $fm = mgImageFile('d.jpg');
    $file = CmsDownloadFile::factory()->create(['download_id' => $download->id, 'file_id' => $fm->id, 'description' => '<p><strong>Bold</strong></p><p>[gallery:g]</p>']);
    $gallery = CmsMediaGallery::factory()->grid()->create(['owner_type' => $file->getMorphClass(), 'owner_id' => $file->id, 'key' => 'g']);
    CmsMediaGalleryImage::factory()->create(['gallery_id' => $gallery->id, 'file_id' => $fm->id, 'caption' => 'File gallery']);

    $this->get(CmsUrl::to($download))
        ->assertOk()
        ->assertSee('<strong>Bold</strong>', false)
        ->assertSee('File gallery');
});
