<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Models\CmsTranslation;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);

    $this->keptPage = CmsPage::factory()->create();
    $this->kept = CmsMediaGallery::factory()->create(['owner_type' => CmsPage::class, 'owner_id' => $this->keptPage->id]);

    // Section/download deletes remove their articles/files by FK cascade, firing no model events.
    $section = CmsSection::factory()->create();
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);
    $this->articleGallery = CmsMediaGallery::factory()->create(['owner_type' => CmsArticle::class, 'owner_id' => $article->id]);
    $image = CmsMediaGalleryImage::factory()->create(['gallery_id' => $this->articleGallery->id]);
    $image->setTranslations(['id' => ['caption' => 'Keterangan']]);
    $section->delete();

    $download = CmsDownload::factory()->create();
    $file = CmsDownloadFile::factory()->create(['download_id' => $download->id]);
    $this->fileGallery = CmsMediaGallery::factory()->create(['owner_type' => CmsDownloadFile::class, 'owner_id' => $file->id]);
    $download->delete();

    $this->unknownType = CmsMediaGallery::factory()->create(['owner_type' => 'App\\Models\\Gone', 'owner_id' => 1]);
});

it('only reports orphaned galleries on a dry run', function () {
    $this->artisan('cms:prune-media-galleries', ['--dry-run' => true])
        ->expectsOutputToContain('3 orphaned media galleries would be deleted')
        ->assertSuccessful();

    expect(CmsMediaGallery::count())->toBe(4);
});

it('deletes galleries whose owner no longer exists, with their images and caption translations', function () {
    $this->artisan('cms:prune-media-galleries')
        ->expectsOutputToContain('3 orphaned media galleries deleted')
        ->assertSuccessful();

    expect(CmsMediaGallery::pluck('id')->all())->toBe([$this->kept->id])
        ->and(CmsMediaGalleryImage::count())->toBe(0)
        ->and(CmsTranslation::where('translatable_type', CmsMediaGalleryImage::class)->count())->toBe(0);
});

it('is a no-op when nothing is orphaned', function () {
    $this->artisan('cms:prune-media-galleries');

    $this->artisan('cms:prune-media-galleries')
        ->expectsOutputToContain('0 orphaned media galleries deleted')
        ->assertSuccessful();

    expect(CmsMediaGallery::pluck('id')->all())->toBe([$this->kept->id]);
});
