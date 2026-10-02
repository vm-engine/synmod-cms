<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsSearchService;
use VmEngine\Fm\Models\FmFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
    ]);
});

describe('CmsSearchable', function () {
    it('is visible only inside its publish window', function () {
        $base = ['searchable_type' => 'article', 'locale' => 'en', 'title' => 'x', 'url' => '/x'];
        CmsSearchable::create($base + ['searchable_id' => 1]);
        CmsSearchable::create($base + ['searchable_id' => 2, 'published_at' => now()->subDay()]);
        CmsSearchable::create($base + ['searchable_id' => 3, 'published_at' => now()->addDay()]);
        CmsSearchable::create($base + ['searchable_id' => 4, 'expires_at' => now()->subDay()]);

        expect(CmsSearchable::visible()->orderBy('searchable_id')->pluck('searchable_id')->all())->toBe([1, 2]);
    });

    it('uses the database engine unless Scout is configured and installed', function () {
        expect(CmsSearchable::usesScout())->toBeFalse();

        config(['synapps.modules.cms.search.engine' => 'scout']);

        expect(CmsSearchable::usesScout())->toBe(trait_exists('Laravel\Scout\Searchable'));
    });
});

function searchRows(string $type, int $id): array
{
    return CmsSearchable::where('searchable_type', $type)->where('searchable_id', $id)
        ->orderBy('locale')->get(['locale', 'title', 'excerpt', 'content', 'url'])->toArray();
}

describe('Indexing routed content', function () {
    it('indexes a published page once per locale, with fallback resolved', function () {
        $page = CmsPage::factory()->create([
            'title' => 'Zebrafish', 'slug' => 'zebra', 'full_slug' => 'zebra',
            'excerpt' => 'Short', 'body' => '<p>Swims <b>fast</b>&nbsp;today</p>',
        ]);
        $page->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        expect(searchRows('page', $page->id))->toBe([
            ['locale' => 'en', 'title' => 'Zebrafish', 'excerpt' => 'Short', 'content' => 'Swims fast today', 'url' => '/en/zebra'],
            ['locale' => 'id', 'title' => 'Ikanzebra', 'excerpt' => 'Short', 'content' => 'Swims fast today', 'url' => '/id/zebra'],
        ]);
    });

    it('indexes one unprefixed row when multi-language is off', function () {
        config(['synapps.apps.multi_language' => false]);
        $page = CmsPage::factory()->create(['slug' => 'zebra', 'full_slug' => 'zebra']);

        expect(searchRows('page', $page->id))->toHaveCount(1)
            ->and(searchRows('page', $page->id)[0]['url'])->toBe('/zebra');
    });

    it('indexes articles, categories, albums and downloads at their routed path', function () {
        $section = CmsSection::factory()->create(['slug' => 'news']);
        $category = CmsCategory::factory()->create(['section_id' => $section->id, 'slug' => 'tech']);
        $article = CmsArticle::factory()->create(['section_id' => $section->id, 'category_id' => $category->id, 'slug' => 'ai']);
        $album = CmsAlbum::factory()->create(['slug' => 'trip', 'type' => 'normal', 'status' => 'active']);
        $download = CmsDownload::factory()->create(['slug' => 'forms']);

        expect(searchRows('category', $category->id)[0]['url'])->toBe('/en/news/tech')
            ->and(searchRows('article', $article->id)[0]['url'])->toBe('/en/news/tech/ai')
            ->and(searchRows('album', $album->id)[0]['url'])->toBe('/en/gallery/trip')
            ->and(searchRows('download', $download->id)[0]['url'])->toBe('/en/downloads/forms');
    });

    it('removes rows when content is unpublished or deleted', function () {
        $page = CmsPage::factory()->create();
        $article = CmsArticle::factory()->create();

        $page->update(['status' => 'draft']);
        $article->delete();

        expect(CmsSearchable::count())->toBe(0);
    });

    it('never indexes private downloads or banner albums', function () {
        $download = CmsDownload::factory()->create();
        CmsAlbum::factory()->create(['type' => 'banner', 'status' => 'active']);

        $download->update(['access' => 'private']);

        expect(CmsSearchable::count())->toBe(0);
    });

    it('follows a category slug change to its articles', function () {
        $section = CmsSection::factory()->create(['slug' => 'news']);
        $category = CmsCategory::factory()->create(['section_id' => $section->id, 'slug' => 'tech']);
        $article = CmsArticle::factory()->create(['section_id' => $section->id, 'category_id' => $category->id, 'slug' => 'ai']);

        $category->update(['slug' => 'science']);

        expect(searchRows('article', $article->id)[0]['url'])->toBe('/en/news/science/ai');
    });

    it('reindexes when only a translation changes', function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrafish']);
        $page->setTranslations(['id' => ['title' => 'Ikanzebra']]);
        $page->setTranslations(['id' => ['title' => 'Ikan']]);

        expect(searchRows('page', $page->id)[1]['title'])->toBe('Ikan');
    });

    it('prunes rows whose content was removed by a DB cascade', function () {
        $section = CmsSection::factory()->create();
        CmsArticle::factory()->create(['section_id' => $section->id]);

        $section->delete();

        expect(CmsSearchable::count())->toBe(0);
    });
});

function searchTestImageFile(): FmFile
{
    return FmFile::create([
        'disk' => 'public', 'folder_path' => 'fm/public', 'relative_path' => 'beach.jpg',
        'filename' => 'beach.jpg', 'original_name' => 'beach.jpg', 'extension' => 'jpg',
        'mime_type' => 'image/jpeg', 'size' => 1024, 'has_thumbnail' => false, 'is_trashed' => false,
    ]);
}

describe('Indexing items', function () {
    beforeEach(function () {
        $this->album = CmsAlbum::factory()->create(['title' => 'Bali Trip', 'slug' => 'bali', 'type' => 'normal', 'status' => 'active']);
    });

    it('indexes an album image under its album, with anchor, label and thumbnail', function () {
        $image = CmsAlbumImage::factory()->create(['album_id' => $this->album->id, 'file_id' => searchTestImageFile()->id, 'title' => null, 'caption' => 'Sunset']);

        $row = CmsSearchable::where('searchable_type', 'album_item')->where('locale', 'en')->firstOrFail();

        expect($row->searchable_id)->toBe($image->id)
            ->and($row->title)->toBe('Sunset')
            ->and($row->url)->toBe("/en/gallery/bali#item-{$image->id}")
            ->and($row->parent_label)->toBe('Bali Trip')
            ->and($row->content)->toContain('Bali Trip')
            ->and($row->image)->toContain('beach.jpg');
    });

    it('skips items with no text and items of unindexed albums', function () {
        CmsAlbumImage::factory()->create(['album_id' => $this->album->id, 'title' => null, 'caption' => null]);
        $banner = CmsAlbum::factory()->create(['type' => 'banner', 'status' => 'active']);
        CmsAlbumImage::factory()->create(['album_id' => $banner->id, 'caption' => 'Hero']);

        expect(CmsSearchable::where('searchable_type', 'album_item')->count())->toBe(0);
    });

    it('updates and removes item rows with the item and its album', function () {
        $image = CmsAlbumImage::factory()->create(['album_id' => $this->album->id, 'title' => null, 'caption' => 'Sunset']);
        $image->update(['caption' => 'Sunrise']);

        expect(CmsSearchable::where('searchable_type', 'album_item')->where('locale', 'en')->value('title'))->toBe('Sunrise');

        $this->album->delete();

        expect(CmsSearchable::where('searchable_type', 'album_item')->count())->toBe(0);
    });

    it('removes the row when an album image is deleted', function () {
        $image = CmsAlbumImage::factory()->create(['album_id' => $this->album->id, 'caption' => 'Sunset']);
        $image->delete();

        expect(CmsSearchable::where('searchable_type', 'album_item')->count())->toBe(0);
    });

    it('indexes download files, but not under a private download', function () {
        $download = CmsDownload::factory()->create(['slug' => 'forms']);
        $file = CmsDownloadFile::factory()->create(['download_id' => $download->id, 'title' => 'Brochure']);

        expect(CmsSearchable::where('searchable_type', 'download_file')->where('locale', 'en')->value('url'))
            ->toBe("/en/downloads/forms#file-{$file->id}");

        $download->update(['access' => 'private']);

        expect(CmsSearchable::where('searchable_type', 'download_file')->count())->toBe(0);
    });
});

describe('CmsSearchService', function () {
    beforeEach(function () {
        $this->search = app(CmsSearchService::class);
    });

    it('matches title, excerpt and body text in the current locale', function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrafish', 'excerpt' => 'Stripes', 'body' => '<p>Freshwater</p>']);
        $page->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        expect($this->search->search('Stripes')->total())->toBe(1)
            ->and($this->search->search('Freshwater')->total())->toBe(1)
            ->and($this->search->search('Ikanzebra')->total())->toBe(0);

        app()->setLocale('id');

        expect($this->search->search('Ikanzebra')->items()[0]->url)->toBe("/id/{$page->full_slug}");
    });

    it('hides scheduled and expired content', function () {
        CmsArticle::factory()->create(['title' => 'Zebra future', 'published_at' => now()->addDay()]);
        CmsArticle::factory()->create(['title' => 'Zebra expired', 'expires_at' => now()->subDay()]);
        CmsArticle::factory()->create(['title' => 'Zebra live', 'published_at' => now()->subDay()]);

        expect(collect($this->search->search('Zebra')->items())->pluck('title')->all())->toBe(['Zebra live']);
    });

    it('ranks title matches first and filters by type', function () {
        CmsPage::factory()->create(['title' => 'Other', 'body' => '<p>about zebra</p>']);
        CmsArticle::factory()->create(['title' => 'Zebra', 'body' => '']);

        expect(collect($this->search->search('zebra')->items())->pluck('title')->all())->toBe(['Zebra', 'Other'])
            ->and($this->search->search('zebra', type: 'page')->total())->toBe(1);
    });

    it('suggests a limited number of results', function () {
        CmsPage::factory()->count(7)->create(['title' => 'Zebra']);

        expect($this->search->suggest('zebra'))->toHaveCount(5);
    });

    it('queries through Scout when configured', function () {
        config(['synapps.modules.cms.search.engine' => 'scout', 'scout.driver' => 'collection']);
        CmsPage::factory()->create(['title' => 'Zebrafish']);

        expect($this->search->search('Zebrafish')->total())->toBe(1);
    })->skip(! trait_exists('Laravel\Scout\Searchable'), 'laravel/scout is not installed');
});

describe('cms:search-reindex', function () {
    it('rebuilds the index from routed content', function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrafish']);
        CmsSearchable::query()->delete();
        CmsSearchable::create(['searchable_type' => 'page', 'searchable_id' => 999, 'locale' => 'en', 'title' => 'Stale', 'url' => '/stale']);

        $this->artisan('cms:search-reindex')->assertSuccessful();

        expect(CmsSearchable::where('title', 'Stale')->exists())->toBeFalse()
            ->and(searchRows('page', $page->id))->toHaveCount(2);
    });

    it('drops rows of locales that are no longer enabled', function () {
        CmsPage::factory()->create();
        config(['synapps.apps.enabled_languages' => ['en']]);

        $this->artisan('cms:search-reindex')->assertSuccessful();

        expect(CmsSearchable::where('locale', 'id')->exists())->toBeFalse();
    });

    it('limits the rebuild to the given types', function () {
        CmsPage::factory()->create();
        $article = CmsArticle::factory()->create();
        CmsSearchable::where('searchable_type', 'article')->delete();

        $this->artisan('cms:search-reindex', ['--type' => ['page']])->assertSuccessful();

        expect(searchRows('article', $article->id))->toBe([]);
    });

    it('rejects unknown types', function () {
        $this->artisan('cms:search-reindex', ['--type' => ['tag']])->assertFailed();
    });
});
