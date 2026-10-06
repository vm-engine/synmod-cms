<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Services\CmsCustomFieldService;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);
});

function cfContent(string $type, int $id, string $locale = 'en'): string
{
    return (string) CmsSearchable::where('searchable_type', $type)->where('searchable_id', $id)->where('locale', $locale)->value('content');
}

it('indexes searchable values (option labels) and skips non-searchable ones', function () {
    $page = CmsPage::factory()->create(['parent_id' => null, 'status' => 'published', 'full_slug' => 'p', 'slug' => 'p']);
    app(CmsCustomFieldService::class)->syncSchema($page, [
        ['name' => 'sku', 'label' => 'SKU', 'type' => 'text', 'searchable' => true],
        ['name' => 'colour', 'label' => 'Colour', 'type' => 'select', 'searchable' => true, 'options' => [['value' => 'r', 'label' => 'Crimson']]],
        ['name' => 'secret', 'label' => 'Secret', 'type' => 'text'],
    ]);

    app(CmsCustomFieldService::class)->saveValues($page, ['en' => ['sku' => 'ZX-81', 'colour' => 'r', 'secret' => 'hidden']]);
    $page->customFieldsSaved();

    expect(cfContent('page', $page->id))->toContain('ZX-81')->toContain('Crimson')->not->toContain('hidden')
        ->and(cfContent('page', $page->id, 'id'))->toContain('ZX-81');
});

it('indexes album image values and reindexes when a field becomes searchable', function () {
    $album = CmsAlbum::factory()->create();
    $service = app(CmsCustomFieldService::class);
    $service->syncSchema($album, [['name' => 'place', 'label' => 'Place', 'type' => 'text']]);
    $image = CmsAlbumImage::factory()->create(['album_id' => $album->id, 'title' => 'Shot']);
    $service->saveValues($image, ['en' => ['place' => 'Borobudur']]);
    $image->customFieldsSaved();

    expect(cfContent('album_item', $image->id))->not->toContain('Borobudur');

    $definitions = $album->customFields()->get()->map->toDefinition()->all();
    $definitions[0]['searchable'] = true;
    if ($service->syncSchema($album, $definitions)) {
        $service->reindexOwner($album);
    }

    expect(cfContent('album_item', $image->id))->toContain('Borobudur');
});
