<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use VmEngine\Synapse\Services\SearchableImporter;

/**
 * @return array<string, array<string, string>>
 */
function cmsSearchables(): array
{
    $module = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/module.json'), true);

    return $module['searchables'] ?? [];
}

it('declares the extra CMS pages as searchables', function () {
    expect(array_keys(cmsSearchables()))->toBe(['create_page', 'create_section', 'create_album']);
});

it('points every searchable at an existing route', function () {
    foreach (cmsSearchables() as $key => $entry) {
        expect(Route::has('backend.'.$entry['route']) || Route::has($entry['route']))
            ->toBeTrue("Searchable [{$key}] route [{$entry['route']}] does not exist");
    }
});

it('has en and id labels for every searchable', function (string $locale) {
    foreach (cmsSearchables() as $key => $entry) {
        foreach (['name', 'description', 'keywords'] as $field) {
            $translationKey = substr($entry[$field], 2);

            expect(Lang::hasForLocale($translationKey, $locale))
                ->toBeTrue("Missing [{$locale}] translation [{$translationKey}] for searchable [{$key}]");
        }
    }
})->with(['en', 'id']);

it('imports the searchables with their ACLs', function () {
    config(['synapps.nav.backend' => [], 'synapps.modules' => [['id' => 'cms', 'searchables' => cmsSearchables()]]]);

    $rows = collect((new SearchableImporter)->rows(['en', 'id']))->where('locale', 'id')->keyBy('key');

    expect($rows->keys()->all())->toBe(['cms.create_page', 'cms.create_section', 'cms.create_album'])
        ->and($rows['cms.create_page']['acl'])->toBe('cms.page.create')
        ->and($rows['cms.create_section']['acl'])->toBe('cms.article.section.create')
        ->and($rows['cms.create_album']['acl'])->toBe('cms.gallery.album.create')
        ->and($rows['cms.create_page']['title'])->toBe('Tambah halaman');
});
