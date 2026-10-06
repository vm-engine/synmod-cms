<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Fm\Models\FmFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);
});

it('saves article values against its section schema', function () {
    $a = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($a, [['name' => 'isbn', 'label' => 'ISBN', 'type' => 'text', 'required' => true]]);
    $article = CmsArticle::factory()->create(['section_id' => $a->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.article-form', ['sectionId' => $a->id, 'id' => $article->id])
        ->assertSet('form.customFieldSchema.0.name', 'isbn')
        ->call('save')
        ->assertHasErrors(['form.customFieldValues.en.isbn'])
        ->set('form.customFieldValues.en.isbn', '978')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->fresh()->field('isbn'))->toBe('978');
});

it('loads the section schema for a new article', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [['name' => 'isbn', 'label' => 'ISBN', 'type' => 'text']]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.article-form', ['sectionId' => $section->id])
        ->assertSet('form.customFieldSchema.0.name', 'isbn');
});

it('saves album image and download file values from their edit forms', function () {
    $album = CmsAlbum::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($album, [['name' => 'place', 'label' => 'Place', 'type' => 'text']]);
    $image = CmsAlbumImage::factory()->create(['album_id' => $album->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.album-image-form', ['albumId' => $album->id, 'id' => $image->id])
        ->set('form.customFieldValues.en.place', 'Bali')
        ->call('save')
        ->assertHasNoErrors();

    $download = CmsDownload::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($download, [['name' => 'version', 'label' => 'Version', 'type' => 'text']]);
    $file = CmsDownloadFile::factory()->create(['download_id' => $download->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.download-file-form', ['downloadId' => $download->id, 'id' => $file->id])
        ->set('form.customFieldValues.en.version', '2.0')
        ->call('save')
        ->assertHasNoErrors();

    expect($image->fresh()->field('place'))->toBe('Bali')->and($file->fresh()->field('version'))->toBe('2.0');
});

it('saves values when adding an image from the album manager', function () {
    $album = CmsAlbum::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($album, [['name' => 'place', 'label' => 'Place', 'type' => 'text', 'required' => true]]);
    $file = FmFile::create([
        'disk' => 'public', 'folder_path' => 'fm/public', 'relative_path' => 'x.jpg',
        'filename' => 'x.jpg', 'original_name' => 'x.jpg', 'extension' => 'jpg',
        'mime_type' => 'image/jpeg', 'size' => 1024, 'has_thumbnail' => false, 'is_trashed' => false,
    ]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.album-image-manager', ['albumId' => $album->id])
        ->set('newImageUrl', '/x.jpg')->set('newFileId', $file->id)
        ->call('addImage')
        ->assertHasErrors(['customFieldValues.en.place'])
        ->set('customFieldValues.en.place', 'Ubud')
        ->call('addImage')
        ->assertHasNoErrors();

    expect(CmsAlbumImage::where('album_id', $album->id)->sole()->field('place'))->toBe('Ubud');
});

it('names custom fields by their label in validation messages', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [['name' => 'isbn', 'label' => 'ISBN Code', 'type' => 'text', 'required' => true]]);
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
        ->call('save')
        ->assertHasErrors(['form.customFieldValues.en.isbn'])
        ->assertSee(__('validation.required', ['attribute' => 'ISBN Code']));
});

it('uses the synapse datepicker for date and datetime value inputs', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [
        ['name' => 'event', 'label' => 'Event', 'type' => 'date'],
        ['name' => 'starts', 'label' => 'Starts', 'type' => 'datetime'],
    ]);
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
        ->assertSeeHtml("synapseDatepicker(\$wire.entangle('form.customFieldValues.en.event')")
        ->assertSeeHtml("synapseDatepicker(\$wire.entangle('form.customFieldValues.en.starts'), {&quot;enableTime&quot;:true")
        ->set('form.customFieldValues.en.event', '2026-10-06')
        ->set('form.customFieldValues.en.starts', '2026-10-06 09:30')
        ->call('save')
        ->assertHasNoErrors();

    expect($article->fresh()->field('starts'))->toBe('2026-10-06 09:30');
});

it('rejects a tampered field schema so value rules cannot be bypassed', function () {
    $section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($section, [['name' => 'isbn', 'label' => 'ISBN', 'type' => 'text', 'required' => true]]);
    $article = CmsArticle::factory()->create(['section_id' => $section->id]);

    Livewire::actingAs(cfAdminUser())
        ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
        ->set('form.customFieldSchema', []);
})->throws(CannotUpdateLockedPropertyException::class);
