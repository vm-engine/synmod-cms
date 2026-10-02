<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSearchTerm;
use VmEngine\Cms\Services\CmsSearchService;
use VmEngine\Cms\Services\CmsSearchTermService;
use VmEngine\Fm\Models\FmFile;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
    ]);
    $this->terms = app(CmsSearchTermService::class);
});

describe('Popular terms', function () {
    it('records a normalized term once per session', function () {
        $this->terms->record('  Bali   TRIP ', 'en');
        $this->terms->record('bali trip', 'en');

        expect(CmsSearchTerm::sole()->only(['locale', 'term', 'hits']))
            ->toBe(['locale' => 'en', 'term' => 'bali trip', 'hits' => 1]);
    });

    it('counts the same term again in another session', function () {
        $this->terms->record('bali', 'en');
        session()->flush();
        $this->terms->record('bali', 'en');

        expect(CmsSearchTerm::sole()->hits)->toBe(2);
    });

    it('caps terms at 100 characters and ignores blanks', function () {
        $this->terms->record(str_repeat('a', 150), 'en');
        $this->terms->record('   ', 'en');

        expect(CmsSearchTerm::sole()->term)->toHaveLength(100);
    });

    it('lists popular terms by hits, per locale, above the threshold, never hidden', function () {
        config(['synapps.modules.cms.search.popular_min_hits' => 3, 'synapps.modules.cms.search.popular_limit' => 2]);
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'beach', 'hits' => 9]);
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'bali', 'hits' => 5]);
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'rare', 'hits' => 2]);
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'spam', 'hits' => 99, 'is_hidden' => true]);
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'third', 'hits' => 4]);
        CmsSearchTerm::create(['locale' => 'id', 'term' => 'pantai', 'hits' => 9]);

        expect($this->terms->popular('en'))->toBe(['beach', 'bali']);
    });

    it('drops a term from the cached popular list when it is hidden', function () {
        config(['synapps.modules.cms.search.popular_min_hits' => 1]);
        $term = CmsSearchTerm::create(['locale' => 'en', 'term' => 'spam', 'hits' => 9]);
        expect($this->terms->popular('en'))->toBe(['spam']);

        $this->terms->toggleHidden($term->id);

        expect($this->terms->popular('en'))->toBe([]);
    });
});

describe('CmsSearchService additions', function () {
    beforeEach(function () {
        $this->search = app(CmsSearchService::class);
        CmsPage::factory()->create(['title' => 'Zebra page']);
        $album = CmsAlbum::factory()->create(['title' => 'Zebra album', 'type' => 'normal', 'status' => 'active']);
        CmsAlbumImage::factory()->create(['album_id' => $album->id, 'title' => null, 'caption' => 'Zebra photo']);
    });

    it('filters by a list of types', function () {
        expect($this->search->search('zebra', CmsSearchService::FILTERS['album'])->total())->toBe(2);
    });

    it('counts results per filter tab', function () {
        expect($this->search->typeCounts('zebra'))->toBe([
            'all' => 3, 'page' => 1, 'article' => 0, 'category' => 0, 'album' => 2, 'download' => 0,
        ]);
    });

    it('highlights matches without trusting the text or the query', function () {
        expect(CmsSearchService::highlight('Tom & <b>Zebra</b> zebra', 'zebra')->toHtml())
            ->toBe('Tom &amp; &lt;b&gt;<mark>Zebra</mark>&lt;/b&gt; <mark>zebra</mark>')
            ->and(CmsSearchService::highlight('a & b', 'amp')->toHtml())->toBe('a &amp; b')
            ->and(CmsSearchService::highlight('x', '<script>')->toHtml())->toBe('x');
    });

    it('cuts a snippet around the first match', function () {
        $text = str_repeat('lorem ', 60).'zebra '.str_repeat('ipsum ', 60);

        expect(CmsSearchService::snippet($text, 'zebra', 80))->toStartWith('…')->toContain('zebra')
            ->and(CmsSearchService::snippet('short zebra', 'zebra'))->toBe('short zebra');
    });
});

describe('Search page', function () {
    beforeEach(function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrafish', 'excerpt' => 'Striped <fish>']);
        $page->setTranslations(['id' => ['title' => 'Ikanzebra']]);
        CmsArticle::factory()->create(['title' => 'Zebra news']);
    });

    it('lists highlighted results for the current locale', function () {
        Livewire::test('cms::frontend.search-results')
            ->set('q', 'zebra')
            ->assertSeeHtml('<mark>Zebra</mark>fish')
            ->assertSeeHtml('Striped &lt;fish&gt;')
            ->assertSeeText('Zebra news');

        app()->setLocale('id');

        Livewire::test('cms::frontend.search-results')->set('q', 'ikan')->assertSeeHtml('<mark>Ikan</mark>zebra');
    });

    it('filters by tab and shows tab counts', function () {
        Livewire::test('cms::frontend.search-results')
            ->set('q', 'zebra')
            ->set('type', 'article')
            ->assertSeeText('Zebra news')
            ->assertDontSeeText('Zebrafish')
            ->assertSeeHtml('data-count="2"');
    });

    it('ignores queries shorter than two characters', function () {
        Livewire::test('cms::frontend.search-results')->set('q', 'z')->assertDontSeeText('Zebrafish');

        expect(CmsSearchTerm::count())->toBe(0);
    });

    it('records a term only when it has results', function () {
        Livewire::test('cms::frontend.search-results')->set('q', 'zebra');
        Livewire::test('cms::frontend.search-results')->set('q', 'nothing-here');

        expect(CmsSearchTerm::pluck('term')->all())->toBe(['zebra']);
    });

    it('stops searching once the visitor exceeds the rate limit', function () {
        config(['synapps.modules.cms.search.rate_limit' => 1]);
        RateLimiter::clear('cms-search:127.0.0.1');

        Livewire::test('cms::frontend.search-results')->set('q', 'zebra')->assertSeeText('Zebrafish');
        Livewire::test('cms::frontend.search-results')->set('q', 'zebra')
            ->assertDontSeeText('Zebrafish')
            ->assertSee(__('cms::labels.search_rate_limited'));
    });

    it('is served at /{locale}/search after a reindex', function () {
        $this->artisan('cms:search-reindex')->assertSuccessful();

        $this->get('/en/search?q=zebra')->assertOk()->assertSeeText('Zebrafish');
        $this->get('/id/search?q=ikan')->assertOk()->assertSeeText('Ikanzebra');
    });
});

describe('Search box', function () {
    it('shows popular terms while empty', function () {
        config(['synapps.modules.cms.search.popular_min_hits' => 1]);
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'bali', 'hits' => 3]);

        Livewire::test('cms::frontend.search-box')
            ->assertSee('bali')
            ->assertSeeHtml('href="/en/search?q=bali"');
    });

    it('suggests the top five matches and links to all results', function () {
        CmsPage::factory()->count(7)->create(['title' => 'Zebra']);

        $html = Livewire::test('cms::frontend.search-box')
            ->set('q', 'zeb')
            ->assertSeeHtml('role="option"')
            ->assertSeeHtml('href="/en/search?q=zeb"')
            ->html();

        expect(substr_count($html, 'class="cms-search-box__option"'))->toBe(5);
    });

    it('submits a plain GET form to the localized search page', function () {
        app()->setLocale('id');

        Livewire::test('cms::frontend.search-box')
            ->assertSeeHtml('action="/id/search"')
            ->assertSeeHtml('name="q"')
            ->assertSeeHtml('role="combobox"');
    });
});

describe('Deep links', function () {
    it('gives album items an anchor and the lightbox their ids', function () {
        $album = CmsAlbum::factory()->create(['type' => 'normal', 'status' => 'active']);
        $file = FmFile::create([
            'disk' => 'public', 'folder_path' => 'fm/public', 'relative_path' => 'a.jpg', 'filename' => 'a.jpg',
            'original_name' => 'a.jpg', 'extension' => 'jpg', 'mime_type' => 'image/jpeg', 'size' => 1, 'has_thumbnail' => false, 'is_trashed' => false,
        ]);
        $image = CmsAlbumImage::factory()->create(['album_id' => $album->id, 'file_id' => $file->id]);

        Livewire::test('cms::frontend.album-viewer', ['album_id' => $album->id])
            ->assertSeeHtml("id=\"item-{$image->id}\"")
            ->assertSeeHtml("&quot;id&quot;:{$image->id}");
    });

    it('gives download files an anchor', function () {
        $download = CmsDownload::factory()->create();
        $file = CmsDownloadFile::factory()->create(['download_id' => $download->id]);

        Livewire::test('cms::frontend.download-viewer', ['download_id' => $download->id])
            ->assertSeeHtml("id=\"file-{$file->id}\"");
    });
});

describe('Search Terms backend', function () {
    beforeEach(function () {
        $this->makeUser = function (bool $canUpdate): User {
            $permission = RolePermission::factory()->forModule('cms', 'search_term');
            $permission = $canUpdate ? $permission->fullCrud() : $permission->readOnly();
            $role = Role::factory()->has($permission)->create(['level' => 5, 'can_access_backend' => true]);
            $user = User::factory()->create();
            $user->roles()->attach($role);

            return $user;
        };
        CmsSearchTerm::create(['locale' => 'en', 'term' => 'bali', 'hits' => 4]);
        CmsSearchTerm::create(['locale' => 'id', 'term' => 'pantai', 'hits' => 2]);
    });

    it('is reachable from the backend', function () {
        $this->actingAs(($this->makeUser)(true))
            ->get(backend_route('cms.search-term.index'))
            ->assertOk()
            ->assertSee('bali');
    });

    it('filters by locale', function () {
        Livewire::actingAs(($this->makeUser)(true))
            ->test('cms::backend.search-term-list')
            ->set('locale', 'id')
            ->assertSee('pantai')
            ->assertDontSee('bali');
    });

    it('hides, unhides and deletes terms', function () {
        $term = CmsSearchTerm::where('term', 'bali')->sole();
        $component = Livewire::actingAs(($this->makeUser)(true))->test('cms::backend.search-term-list');

        $component->call('toggleHidden', $term->id);
        expect($term->fresh()->is_hidden)->toBeTrue();

        $component->call('delete', $term->id);
        expect(CmsSearchTerm::whereKey($term->id)->exists())->toBeFalse();
    });

    it('refuses changes without update or delete permission', function () {
        $term = CmsSearchTerm::where('term', 'bali')->sole();

        Livewire::actingAs(($this->makeUser)(false))
            ->test('cms::backend.search-term-list')
            ->call('toggleHidden', $term->id)
            ->assertForbidden();
    });
});
