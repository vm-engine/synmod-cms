<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsMediaGalleryRenderer;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

function mgGallery(CmsPage $page, string $key, string $layout = 'slider', bool $withImage = true): CmsMediaGallery
{
    $gallery = CmsMediaGallery::factory()->create(['owner_type' => $page->getMorphClass(), 'owner_id' => $page->id, 'key' => $key, 'layout' => $layout]);
    if ($withImage) {
        CmsMediaGalleryImage::factory()->create(['gallery_id' => $gallery->id, 'file_id' => mgImageFile($key.'.jpg')->id, 'caption' => 'Cap '.$key]);
    }

    return $gallery;
}

beforeEach(function () {
    $this->page = CmsPage::factory()->create(['config' => ['media_gallery' => true]]);
    $this->renderer = app(CmsMediaGalleryRenderer::class);
});

it('renders slider and grid galleries in place of their tags', function () {
    mgGallery($this->page, 'one');
    mgGallery($this->page, 'two', 'grid');

    $html = $this->renderer->render($this->page, '<p>Intro</p>[gallery:one]<p>Mid</p>[gallery:two]');

    expect($html)->toContain('<p>Intro</p>')
        ->and($html)->toContain('cms-media-gallery--slider')
        ->and($html)->toContain('cms-media-gallery--grid')
        ->and($html)->toContain('Cap two')
        ->and($html)->not->toContain('[gallery:');
});

it('replaces a paragraph-wrapped tag whole, leaving inline text alone', function () {
    mgGallery($this->page, 'one');

    $html = $this->renderer->render($this->page, '<p>&nbsp;[gallery:one] </p><p>See [gallery:missing] here</p>');

    expect($html)->not->toContain('<p>&nbsp;')
        ->and($html)->toStartWith('<div')
        ->and($html)->toContain('<p>See  here</p>');
});

it('drops unknown keys, empty galleries and everything when the feature is off', function () {
    mgGallery($this->page, 'empty', withImage: false);

    expect($this->renderer->render($this->page, 'a[gallery:empty]b[gallery:nope]c'))->toBe('abc');

    $this->page->update(['config' => ['media_gallery' => false]]);
    mgGallery($this->page, 'one');

    expect($this->renderer->render($this->page->fresh(), 'a[gallery:one]b'))->toBe('ab');
});

it('loads all galleries of a body with one gallery query', function () {
    mgGallery($this->page, 'one');
    mgGallery($this->page, 'two');
    mgGallery($this->page, 'three');

    DB::enableQueryLog();
    $this->renderer->render($this->page, '[gallery:one][gallery:two][gallery:three]');

    $galleryQueries = collect(DB::getQueryLog())->filter(fn (array $q) => str_contains($q['query'], 'cms_media_galleries'));
    expect($galleryQueries)->toHaveCount(1);
});

it('leaves html without tags untouched and runs no query', function () {
    DB::enableQueryLog();

    expect($this->renderer->render($this->page, '<p>Plain</p>'))->toBe('<p>Plain</p>')
        ->and(DB::getQueryLog())->toBe([]);
});

it('strips tags for plain-text use', function () {
    expect(CmsMediaGalleryRenderer::strip('<p>[gallery:x]</p><p>Text [gallery:y]</p>'))->toBe('<p>Text </p>');
});
