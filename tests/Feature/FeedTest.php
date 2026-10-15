<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsSection;

uses(RefreshDatabase::class);

/**
 * Routes are registered at boot from the host's own config, so on a single-language host the
 * /{locale}/feed copies never exist. Re-register the override routes under the test's
 * multi-language config, keeping synapps-fallback's catch-all `{path}` route last as at boot
 * (it is an ordinary route, so anything added after it would never match).
 */
function registerFeedRoutesAsMultiLanguageHost(): void
{
    $router = app('router');
    $catchAll = $router->getRoutes()->getByName('synfallback.dispatch');
    $routes = new RouteCollection;

    foreach ($router->getRoutes()->getRoutes() as $route) {
        if ($route !== $catchAll) {
            $routes->add($route);
        }
    }

    $router->setRoutes($routes);
    Route::middleware('web')->name('cms.')->group(dirname(__DIR__, 2).'/routes/web.override.php');

    if ($catchAll !== null) {
        $routes->add($catchAll);
    }

    $routes->refreshNameLookups();
}

beforeEach(function () {
    config([
        'app.name' => 'Synapse',
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
    ]);

    registerFeedRoutesAsMultiLanguageHost();

    $this->section = CmsSection::factory()->create(['slug' => 'news', 'title' => 'News']);
});

/**
 * @return list<string>
 */
function cmsFeedTitles(string $xml): array
{
    return array_map('strval', simplexml_load_string($xml)->xpath('//item/title'));
}

describe('Feed routes', function () {
    it('serves every feed as RSS 2.0', function (string $url) {
        $response = $this->get($url)->assertOk();

        $xml = simplexml_load_string($response->getContent());

        expect($response->headers->get('Content-Type'))->toContain('application/rss+xml')
            ->and($xml->getName())->toBe('rss')
            ->and((string) $xml['version'])->toBe('2.0')
            ->and((string) $xml->channel->title)->toStartWith('Synapse — ');
    })->with([
        '/feed/articles.xml',
        '/feed/articles/news.xml',
        '/feed/downloads.xml',
        '/feed/gallery.xml',
        '/id/feed/articles.xml',
        '/id/feed/articles/news.xml',
        '/id/feed/downloads.xml',
        '/id/feed/gallery.xml',
    ]);

    it('registers locale-prefixed feed routes only when multi-language is on', function (bool $multiLanguage) {
        config(['synapps.apps.multi_language' => $multiLanguage]);

        $router = new Router(app('events'), app());
        app()->instance('router', $router);
        Route::clearResolvedInstance('router');
        $router->group(['as' => 'cms.'], dirname(__DIR__, 2).'/routes/web.override.php');
        $router->getRoutes()->refreshNameLookups();

        expect($router->getRoutes()->hasNamedRoute('cms.feed.articles'))->toBeTrue()
            ->and($router->getRoutes()->hasNamedRoute('cms.feed.articles.locale'))->toBe($multiLanguage);
    })->with([true, false]);
});

describe('Article feed', function () {
    it('lists only published, public articles of published sections, newest first', function () {
        $article = fn (array $attributes) => CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => null,
            ...$attributes,
        ]);

        $article(['title' => 'Older', 'published_at' => now()->subDays(2)]);
        $article(['title' => 'Newer', 'published_at' => now()->subDay()]);
        CmsArticle::factory()->draft()->create(['section_id' => $this->section->id, 'category_id' => null, 'title' => 'Draft']);
        $article(['title' => 'Future', 'published_at' => now()->addDay()]);
        $article(['title' => 'Expired', 'published_at' => now()->subDays(3), 'expires_at' => now()->subDay()]);
        $article(['title' => 'Members', 'visibility' => 'protected']);

        $private = CmsSection::factory()->create(['slug' => 'staff', 'visibility' => 'private']);
        CmsArticle::factory()->create(['section_id' => $private->id, 'category_id' => null, 'title' => 'Inherited private']);

        $draftSection = CmsSection::factory()->draft()->create(['slug' => 'wip']);
        CmsArticle::factory()->create(['section_id' => $draftSection->id, 'category_id' => null, 'title' => 'In draft section']);

        expect(cmsFeedTitles($this->get('/feed/articles.xml')->getContent()))->toBe(['Newer', 'Older']);
    });

    it('caps the feed at 20 items', function () {
        CmsArticle::factory()->count(21)->create(['section_id' => $this->section->id, 'category_id' => null]);

        expect(cmsFeedTitles($this->get('/feed/articles.xml')->getContent()))->toHaveCount(20);
    });

    it('maps article fields onto items in the primary locale when unprefixed', function () {
        $published = Carbon::parse('2026-10-01 08:00:00');

        CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => null,
            'title' => 'Hello',
            'slug' => 'hello',
            'excerpt' => '<p>Short intro</p>',
            'meta_description' => null,
            'cover_image' => '/storage/cover.jpg',
            'published_at' => $published,
        ]);

        $channel = simplexml_load_string($this->get('/feed/articles.xml')->getContent())->channel;
        $item = $channel->item[0];

        expect((string) $channel->language)->toBe('en')
            ->and((string) $channel->lastBuildDate)->toBe($published->toRssString())
            ->and((string) $item->title)->toBe('Hello')
            ->and((string) $item->link)->toBe(url('en/news/hello'))
            ->and((string) $item->guid)->toBe(url('en/news/hello'))
            ->and((string) $item->guid['isPermaLink'])->toBe('true')
            ->and((string) $item->description)->toBe('Short intro')
            ->and((string) $item->pubDate)->toBe($published->toRssString())
            ->and((string) $item->category)->toBe('News')
            ->and((string) $item->enclosure['url'])->toBe(url('/storage/cover.jpg'))
            ->and((string) $item->enclosure['type'])->toBe('image/jpeg');
    });

    it('translates the feed for a locale prefix', function () {
        $article = CmsArticle::factory()->create([
            'section_id' => $this->section->id,
            'category_id' => null,
            'title' => 'Hello',
            'slug' => 'hello',
        ]);
        $article->setTranslations(['id' => ['title' => 'Halo']]);

        $channel = simplexml_load_string($this->get('/id/feed/articles.xml')->getContent())->channel;

        expect((string) $channel->language)->toBe('id')
            ->and((string) $channel->title)->toBe('Synapse — Artikel')
            ->and((string) $channel->children('atom', true)->link->attributes()['href'])->toBe(url('id/feed/articles.xml'))
            ->and((string) $channel->item[0]->title)->toBe('Halo')
            ->and((string) $channel->item[0]->link)->toBe(url('id/news/hello'));
    });
});

describe('Section feed', function () {
    it('lists only that section', function () {
        $blog = CmsSection::factory()->create(['slug' => 'blog']);
        CmsArticle::factory()->create(['section_id' => $this->section->id, 'category_id' => null, 'title' => 'In news']);
        CmsArticle::factory()->create(['section_id' => $blog->id, 'category_id' => null, 'title' => 'In blog']);

        $content = $this->get('/feed/articles/news.xml')->getContent();
        $channel = simplexml_load_string($content)->channel;

        expect(cmsFeedTitles($content))->toBe(['In news'])
            ->and((string) $channel->title)->toBe('Synapse — News')
            ->and((string) $channel->link)->toBe(url('en/news'));
    });

    it('404s for sections that are not publicly listed', function (?array $state) {
        if ($state !== null) {
            CmsSection::factory()->create(['slug' => 'hidden', ...$state]);
        }

        $this->get('/feed/articles/hidden.xml')->assertNotFound();
    })->with([
        'unknown' => [null],
        'draft' => [['status' => 'draft']],
        'protected' => [['visibility' => 'protected']],
    ]);
});

describe('Download & gallery feeds', function () {
    it('lists active public download categories, newest first', function () {
        CmsDownload::factory()->create(['title' => 'Brochures', 'created_at' => now()->subDay()]);
        CmsDownload::factory()->create(['title' => 'Reports']);
        CmsDownload::factory()->inactive()->create(['title' => 'Old']);
        CmsDownload::factory()->protected()->create(['title' => 'Internal']);

        expect(cmsFeedTitles($this->get('/feed/downloads.xml')->getContent()))->toBe(['Reports', 'Brochures']);
    });

    it('lists active public normal albums only', function () {
        CmsAlbum::factory()->create(['title' => 'Trip', 'slug' => 'trip']);
        CmsAlbum::factory()->banner()->create(['title' => 'Hero']);
        CmsAlbum::factory()->inactive()->create(['title' => 'Hidden']);
        CmsAlbum::factory()->create(['title' => 'Members', 'visibility' => 'protected']);

        $content = $this->get('/feed/gallery.xml')->getContent();

        expect(cmsFeedTitles($content))->toBe(['Trip'])
            ->and((string) simplexml_load_string($content)->channel->item[0]->link)->toBe(url('en/gallery/trip'));
    });
});

describe('Feed links component', function () {
    it('advertises the site feeds in the head', function () {
        $html = Blade::render('<x-cms::feed-links />');

        expect($html)
            ->toMatch('#<link\s+rel="alternate"\s+type="application/rss\+xml"\s+title="Synapse — Articles"\s+href="'.preg_quote(url('en/feed/articles.xml'), '#').'"#')
            ->toContain('href="'.url('en/feed/downloads.xml').'"')
            ->toContain('href="'.url('en/feed/gallery.xml').'"')
            ->not->toContain('feed/articles/news.xml');
    });

    it('adds the section feed on section, category and article pages', function (string $param) {
        $ids = [
            'section_id' => $this->section->id,
            'article_id' => CmsArticle::factory()->create(['section_id' => $this->section->id, 'category_id' => null])->id,
            'category_id' => CmsCategory::factory()->create(['section_id' => $this->section->id])->id,
        ];

        $route = (new RoutingRoute('GET', 'cms-page', []))->bind(request());
        $route->setParameter($param, $ids[$param]);
        request()->setRouteResolver(fn () => $route);

        expect(Blade::render('<x-cms::feed-links />'))
            ->toMatch('#title="Synapse — News"\s+href="'.preg_quote(url('en/feed/articles/news.xml'), '#').'"#');
    })->with(['section_id', 'article_id', 'category_id']);

    it('skips the section feed for a non-public section', function () {
        $this->section->update(['visibility' => 'protected']);

        $route = (new RoutingRoute('GET', 'cms-page', []))->bind(request());
        $route->setParameter('section_id', $this->section->id);
        request()->setRouteResolver(fn () => $route);

        expect(Blade::render('<x-cms::feed-links />'))->not->toContain('feed/articles/news.xml');
    });
});
