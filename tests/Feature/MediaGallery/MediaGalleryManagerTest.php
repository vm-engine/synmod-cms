<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsMediaGalleryImage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynAuth\Models\User;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);
    $this->page = CmsPage::factory()->create(['config' => ['media_gallery' => true]]);
});

function mgOwnedGallery(CmsPage $page, array $attributes = []): CmsMediaGallery
{
    return CmsMediaGallery::factory()->create(['owner_type' => $page->getMorphClass(), 'owner_id' => $page->id, ...$attributes]);
}

it('lets a non-dev editor create, edit and delete a gallery', function () {
    $component = Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => $this->page])
        ->call('create')
        ->set('title', 'Intro Shots')
        ->assertSet('key', 'intro-shots')
        ->set('layout', 'grid')
        ->call('saveGallery')
        ->assertHasNoErrors()
        ->assertSee('[gallery:intro-shots]');

    $gallery = $this->page->mediaGalleries()->sole();
    expect($gallery->layout)->toBe('grid');

    $component->call('edit', $gallery->id)->set('title', 'Renamed')->call('saveGallery')->assertHasNoErrors();
    expect($gallery->fresh()->title)->toBe('Renamed')->and($gallery->fresh()->key)->toBe('intro-shots');

    $component->call('deleteGallery', $gallery->delete_token);
    expect(CmsMediaGallery::count())->toBe(0);
});

it('requires a key unique per owner only', function () {
    mgOwnedGallery($this->page, ['key' => 'taken']);
    $child = CmsPage::factory()->create(['parent_id' => $this->page->id]);

    Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => $this->page])
        ->call('create')->set('title', 'X')->set('key', 'taken')->call('saveGallery')
        ->assertHasErrors(['key' => 'unique']);

    Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => $child])
        ->call('create')->set('title', 'X')->set('key', 'taken')->call('saveGallery')
        ->assertHasNoErrors();
});

it('adds, captions per locale, reorders and removes images', function () {
    $gallery = mgOwnedGallery($this->page);

    $component = Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => $this->page])
        ->call('openImages', $gallery->id);

    $component->set('newImageUrl', 'x')->set('newFileId', mgImageFile('a.jpg')->id)->call('addImage')->assertHasNoErrors();
    $component->set('newImageUrl', 'x')->set('newFileId', mgImageFile('b.jpg')->id)->call('addImage')->assertHasNoErrors();

    [$a, $b] = $gallery->images()->get()->all();

    $component->set("captions.{$a->id}.en", 'Hello')->set("captions.{$a->id}.id", 'Halo')->call('saveCaptions');
    $a = CmsMediaGalleryImage::findOrFail($a->id);
    expect($a->caption)->toBe('Hello')->and($a->translate('caption', 'id'))->toBe('Halo');

    $component->call('updateOrder', [$b->id, $a->id]);
    expect($gallery->images()->pluck('id')->all())->toBe([$b->id, $a->id]);

    $component->call('removeImage', $a->delete_token);
    expect(CmsMediaGalleryImage::pluck('id')->all())->toBe([$b->id]);
});

it('rejects non-image files', function () {
    $gallery = mgOwnedGallery($this->page);

    Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => $this->page])
        ->call('openImages', $gallery->id)
        ->set('newImageUrl', 'x')->set('newFileId', mgImageFile('doc.pdf', 'application/pdf')->id)
        ->call('addImage')
        ->assertHasErrors('newFileId');

    expect(CmsMediaGalleryImage::count())->toBe(0);
});

it("cannot touch another owner's gallery", function () {
    $foreign = mgOwnedGallery(CmsPage::factory()->create(['config' => ['media_gallery' => true]]));

    Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => $this->page])
        ->call('deleteGallery', $foreign->delete_token)
        ->assertNotFound();

    expect($foreign->fresh())->not->toBeNull();
});

it('forbids users without update access and owners with the feature off', function () {
    Livewire::actingAs(User::factory()->create())
        ->test('cms::backend.media-gallery-manager', ['owner' => $this->page])
        ->assertForbidden();

    Livewire::actingAs(mgAdmin())
        ->test('cms::backend.media-gallery-manager', ['owner' => CmsPage::factory()->create()])
        ->assertForbidden();
});

it('embeds the manager in enabled item forms and hints save-first on new items', function () {
    $section = CmsSection::factory()->create(['config' => ['media_gallery' => true]]);

    Livewire::actingAs(mgAdmin())->test('cms::backend.page-form', ['id' => $this->page->id])
        ->assertSeeLivewire('cms::backend.media-gallery-manager');

    Livewire::actingAs(mgAdmin())->test('cms::backend.article-form', ['sectionId' => $section->id])
        ->assertSee(__('cms::labels.media_galleries_save_first'));

    Livewire::actingAs(mgAdmin())->test('cms::backend.page-form', ['id' => CmsPage::factory()->create()->id])
        ->assertDontSeeLivewire('cms::backend.media-gallery-manager');
});
