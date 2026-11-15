<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Support\CmsWidgetRegistry;
use VmEngine\Fm\Models\FmFile;

beforeEach(function () {
    $album = CmsAlbum::factory()->create(['title' => 'Summer Trip', 'slug' => 'trip', 'type' => 'normal', 'status' => 'active']);
    $file = FmFile::create([
        'disk' => 'public', 'folder_path' => 'fm/public', 'relative_path' => 'trip.jpg', 'filename' => 'trip.jpg',
        'original_name' => 'trip.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg', 'size' => 1024,
        'has_thumbnail' => false, 'is_trashed' => false,
    ]);
    CmsAlbumImage::factory()->create(['album_id' => $album->id, 'file_id' => $file->id]);
});

it('prints the album name above the gallery when asked', function () {
    expect(Blade::render('<x-cms::widget.gallery album="trip" :show-album-title="true" />'))
        ->toMatch('/<h2 class="cms-widget__heading">\s*<a [^>]*class="cms-widget__heading-link">Summer Trip<\/a>/');
});

it('keeps embeds without the prop unchanged', function () {
    expect(Blade::render('<x-cms::widget.gallery album="trip" />'))->not->toContain('Summer Trip');
});

it('shows the album name by default in the page builder, with the image title switch labelled as such', function () {
    $registry = app(CmsWidgetRegistry::class);

    expect($registry->sanitizeProps('gallery', [])['showAlbumTitle'])->toBeTrue()
        ->and($registry->get('gallery')['props']['showAlbumTitle']['label'])->toBe('cms::builder.prop_show_album_title')
        ->and($registry->get('gallery')['props']['showTitle']['label'])->toBe('cms::builder.prop_show_image_titles')
        ->and($registry->renderSafely('gallery', ['album' => 'trip']))->toContain('Summer Trip');
});
