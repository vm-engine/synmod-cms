<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Support\CmsPagePaletteIndex;
use VmEngine\Synapse\Models\Searchable;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate', ['--path' => base_path('vendor/vm-engine/synapse/database/migrations/searchable'), '--realpath' => true])->assertExitCode(0);
});

/**
 * @return array<string, string>
 */
function indexedPages(): array
{
    return Searchable::query()->where('source', CmsPagePaletteIndex::SOURCE)->orderBy('key')->pluck('title', 'key')->all();
}

it('keeps pages in the global search index through their lifecycle', function () {
    $page = CmsPage::factory()->create(['title' => 'About Us', 'slug' => 'about-us', 'full_slug' => 'about-us']);
    expect(indexedPages())->toBe([(string) $page->id => 'About Us']);

    $page->update(['title' => 'About Our Company']);
    expect(indexedPages())->toBe([(string) $page->id => 'About Our Company']);

    $row = Searchable::query()->where('source', CmsPagePaletteIndex::SOURCE)->sole();
    expect($row->acl)->toBe('cms.page.update')
        ->and($row->route)->toBe('cms.page.form')
        ->and($row->route_params)->toBe(['id' => $page->id])
        ->and($row->description)->toContain('/about-us')
        ->and($row->keywords)->toContain('about-us')
        ->and($row->resolveUrl())->toEndWith("/cms/page/form/{$page->id}");

    $page->delete();
    expect(indexedPages())->toBe([]);
});

it('rebuilds the page entries from scratch', function () {
    CmsPage::factory()->count(3)->create();
    Searchable::query()->delete();

    $this->artisan('cms:palette-reindex')->expectsOutputToContain('3 pages indexed')->assertSuccessful();

    expect(indexedPages())->toHaveCount(3);
});

it('warns instead of failing when global search is not set up', function () {
    Schema::drop('searchables');

    $this->artisan('cms:palette-reindex')->expectsOutputToContain('synapps:searchable-setup')->assertSuccessful();
});
