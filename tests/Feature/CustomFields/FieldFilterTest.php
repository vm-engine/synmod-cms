<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsCustomFieldService;
use VmEngine\Cms\Support\CmsFieldFilter;
use VmEngine\Cms\Support\CmsUrl;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($this->section, [
        ['name' => 'price', 'label' => 'Price', 'type' => 'number', 'filterable' => true, 'sortable' => true],
        ['name' => 'colour', 'label' => 'Colour', 'type' => 'select', 'filterable' => true, 'options' => [['value' => 'r', 'label' => 'R'], ['value' => 'g', 'label' => 'G']]],
        ['name' => 'secret', 'label' => 'Secret', 'type' => 'number'],
        ['name' => 'title2', 'label' => 'T', 'type' => 'text', 'filterable' => true],
    ]);

    foreach ([['a', 10, 'r', 1, 'Blue'], ['b', 50, 'g', 2, 'Red'], ['c', 90, 'g', 3, 'Blue sky']] as [$slug, $price, $colour, $secret, $t]) {
        $article = CmsArticle::factory()->create(['section_id' => $this->section->id, 'slug' => $slug]);
        app(CmsCustomFieldService::class)->saveValues($article, ['en' => ['price' => $price, 'colour' => $colour, 'secret' => $secret, 'title2' => $t]]);
    }
});

function cfFiltered(CmsFieldFilter $filter): array
{
    return $filter->apply(CmsArticle::query())->orderBy('id')->pluck('slug')->all();
}

it('applies whitelisted filters and sort', function () {
    $schema = $this->section->customFields()->get();

    expect(cfFiltered(new CmsFieldFilter($schema, ['price' => ['min' => '20']])))->toBe(['b', 'c'])
        ->and(cfFiltered(new CmsFieldFilter($schema, ['price' => ['max' => '60'], 'colour' => 'g'])))->toBe(['b'])
        ->and(cfFiltered(new CmsFieldFilter($schema, ['title2' => 'blue'])))->toBe(['a', 'c'])
        ->and(cfFiltered(new CmsFieldFilter($schema, ['secret' => ['min' => '2'], 'nope' => 'x', 'colour' => 'zzz'])))->toBe(['a', 'b', 'c'])
        ->and((new CmsFieldFilter($schema, [], 'price', 'desc'))->apply(CmsArticle::query())->pluck('slug')->all())->toBe(['c', 'b', 'a'])
        ->and((new CmsFieldFilter($schema, [], 'secret', 'desc'))->apply(CmsArticle::query())->orderBy('id')->pluck('slug')->all())->toBe(['a', 'b', 'c']);
});

it('filters the section index listing from the query string', function () {
    $this->get(CmsUrl::to($this->section).'?f[colour]=g&sort=price&dir=desc')
        ->assertOk()
        ->assertSeeInOrder([CmsArticle::where('slug', 'c')->value('title'), CmsArticle::where('slug', 'b')->value('title')])
        ->assertDontSee(CmsArticle::where('slug', 'a')->value('title'));
});

it('keeps the page-children filter bar when no child matches', function () {
    $root = CmsPage::factory()->create(['parent_id' => null, 'status' => 'published', 'config' => ['show_children' => true]]);
    app(CmsCustomFieldService::class)->syncSchema($root, [['name' => 'size', 'label' => 'Size', 'type' => 'text', 'filterable' => true]]);
    $child = CmsPage::factory()->create(['parent_id' => $root->id, 'status' => 'published', 'title' => 'Tiny child']);
    app(CmsCustomFieldService::class)->saveValues($child, ['en' => ['size' => 'small']]);

    Livewire::withQueryParams(['f' => ['size' => 'huge']])
        ->test('cms::frontend.page-viewer', ['page_id' => $root->id])
        ->assertSeeHtml('cms-field-filters')
        ->assertDontSee('Tiny child');

    Livewire::withQueryParams(['f' => ['size' => 'small']])
        ->test('cms::frontend.page-viewer', ['page_id' => $root->id])
        ->assertSee('Tiny child');
});

it('applies and resets filters through the Livewire listing', function () {
    $title = fn (string $slug): string => (string) CmsArticle::where('slug', $slug)->value('title');

    Livewire::test('cms::frontend.section-index', ['section_id' => $this->section->id])
        ->set('f.colour', 'g')
        ->set('f.price.min', '20')
        ->set('sort', 'price')
        ->set('dir', 'desc')
        ->call('applyFieldFilters')
        ->assertSeeInOrder([$title('c'), $title('b')])
        ->assertDontSee($title('a'))
        ->call('resetFieldFilters')
        ->assertSet('f', [])
        ->assertSet('sort', '')
        ->assertSee($title('a'));
});

it('renders date range filters with the synapse datepicker', function () {
    app(CmsCustomFieldService::class)->syncSchema($this->section, [
        ...$this->section->customFields()->get()->map->toDefinition()->all(),
        ['name' => 'event', 'label' => 'Event', 'type' => 'date', 'filterable' => true],
    ]);

    Livewire::test('cms::frontend.section-index', ['section_id' => $this->section->id])
        ->assertSeeHtml("synapseDatepicker(\$wire.entangle('f.event.min')")
        ->assertSeeHtml("synapseDatepicker(\$wire.entangle('f.event.max')")
        ->assertSeeHtml('wire:submit="applyFieldFilters"')
        // the datepicker entangles f.event.min/max, so both paths must exist
        ->assertSet('f.event', ['min' => null, 'max' => null]);
});

it('mounts each filterable listing on its outermost element', function (string $component, Closure $params) {
    $html = Livewire::test($component, $params())->html();

    // Livewire attaches to the first element starting a line; a root wrapped in
    // @if (block marker on the same line) would leave the filter bar outside the component.
    expect(preg_match('/^(?:\s|<!--.*?-->)*<[a-z]+\b[^>]*\bwire:snapshot=/s', $html))->toBe(1);
})->with([
    'section index' => ['cms::frontend.section-index', fn () => ['section_id' => CmsSection::factory()->create()->id]],
    'category index' => ['cms::frontend.category-index', fn () => ['category_id' => CmsCategory::factory()->create()->id]],
    'album viewer' => ['cms::frontend.album-viewer', fn () => ['album_id' => CmsAlbum::factory()->create()->id]],
    'download viewer' => ['cms::frontend.download-viewer', fn () => ['download_id' => CmsDownload::factory()->create()->id]],
    'page viewer' => ['cms::frontend.page-viewer', fn () => ['page_id' => CmsPage::factory()->create(['status' => 'published'])->id]],
]);
