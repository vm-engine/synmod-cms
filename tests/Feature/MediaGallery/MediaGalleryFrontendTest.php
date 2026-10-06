<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsSearchIndexer;
use VmEngine\Cms\Support\CmsUrl;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

function mgFrontGallery(Model $owner, string $key): void
{
    $gallery = CmsMediaGallery::factory()->grid()->create(['owner_type' => $owner->getMorphClass(), 'owner_id' => $owner->getKey(), 'key' => $key]);
    CmsMediaGalleryImage::factory()->create(['gallery_id' => $gallery->id, 'file_id' => mgImageFile($key.'.jpg')->id, 'caption' => 'Caption '.$key]);
}

it('renders galleries on a child page of an enabled root', function () {
    $root = CmsPage::factory()->published()->create(['config' => ['media_gallery' => true]]);
    $child = CmsPage::factory()->published()->create(['parent_id' => $root->id, 'body' => '<p>Before</p><p>[gallery:shots]</p>']);
    mgFrontGallery($child, 'shots');

    $this->get(CmsUrl::to($child->fresh()))
        ->assertOk()
        ->assertSee('Caption shots')
        ->assertDontSee('[gallery:shots]', false);
});

it('renders galleries in an article of an enabled section', function () {
    $section = CmsSection::factory()->create(['config' => ['media_gallery' => true]]);
    $article = CmsArticle::factory()->create(['section_id' => $section->id, 'body' => '<p>[gallery:pics]</p>']);
    mgFrontGallery($article, 'pics');

    $this->get(CmsUrl::to($article->fresh()))->assertOk()->assertSee('Caption pics');
});

it('hides tags when the feature is off', function () {
    $page = CmsPage::factory()->published()->create(['body' => '<p>Text</p><p>[gallery:shots]</p>']);
    mgFrontGallery($page, 'shots');

    $this->get(CmsUrl::to($page))->assertOk()->assertDontSee('Caption shots')->assertDontSee('[gallery:shots]', false);
});

it('keeps gallery tags out of search text', function () {
    expect(CmsSearchIndexer::plainText('<p>Hello</p><p>[gallery:x]</p> world [gallery:y]'))->toBe('Hello world');
});
