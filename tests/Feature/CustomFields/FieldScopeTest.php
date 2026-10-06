<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsCustomFieldService;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en', 'synapps.apps.multi_language' => true, 'synapps.apps.enabled_languages' => ['en', 'id']]);

    $this->section = CmsSection::factory()->create();
    app(CmsCustomFieldService::class)->syncSchema($this->section, [
        ['name' => 'price', 'label' => 'Price', 'type' => 'number'],
        ['name' => 'event', 'label' => 'Event', 'type' => 'date'],
        ['name' => 'colour', 'label' => 'Colour', 'type' => 'select', 'options' => [['value' => 'r', 'label' => 'R'], ['value' => 'g', 'label' => 'G']]],
        ['name' => 'tags', 'label' => 'Tags', 'type' => 'checkboxes', 'options' => [['value' => 'a', 'label' => 'A'], ['value' => 'b', 'label' => 'B']]],
        ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text'],
    ]);

    $make = function (string $slug, array $en, array $id = []): CmsArticle {
        $article = CmsArticle::factory()->create(['section_id' => $this->section->id, 'slug' => $slug]);
        app(CmsCustomFieldService::class)->saveValues($article, ['en' => $en, 'id' => $id]);

        return $article;
    };

    $make('cheap', ['price' => 9, 'event' => '2026-01-10', 'colour' => 'r', 'tags' => ['a'], 'subtitle' => 'Blue sky']);
    $make('mid', ['price' => 50, 'event' => '2026-03-01', 'colour' => 'g', 'tags' => ['a', 'b'], 'subtitle' => 'Grey'], ['subtitle' => 'Abu sky']);
    $make('dear', ['price' => 100, 'event' => '2026-02-01', 'colour' => 'g', 'tags' => ['b']]);
});

function cfSlugs($query): array
{
    return $query->pluck('slug')->all();
}

it('filters by number, date, option and checkbox values', function () {
    expect(cfSlugs(CmsArticle::whereField('price', '<', 60)->orderBy('id')))->toBe(['cheap', 'mid'])
        ->and(cfSlugs(CmsArticle::whereField('event', '>=', Carbon::parse('2026-02-01'))->orderBy('id')))->toBe(['mid', 'dear'])
        ->and(cfSlugs(CmsArticle::whereField('colour', 'g')->orderBy('id')))->toBe(['mid', 'dear'])
        ->and(cfSlugs(CmsArticle::whereFieldIn('tags', ['a'])->orderBy('id')))->toBe(['cheap', 'mid']);
});

it('matches the current locale, falling back to the primary locale', function () {
    app()->setLocale('id');

    expect(cfSlugs(CmsArticle::whereField('subtitle', 'like', '%sky%')->orderBy('id')))->toBe(['cheap', 'mid']);
});

it('orders by a field value', function () {
    expect(cfSlugs(CmsArticle::query()->orderByField('price', 'desc')))->toBe(['dear', 'mid', 'cheap'])
        ->and(cfSlugs(CmsArticle::query()->orderByField('event')))->toBe(['cheap', 'dear', 'mid']);
});
