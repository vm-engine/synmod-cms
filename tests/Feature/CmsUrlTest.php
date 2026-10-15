<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsSitemapService;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\CmsUrl;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
    ]);
});

function urlTestTree(): array
{
    $section = CmsSection::factory()->create(['slug' => 'news']);
    $category = CmsCategory::factory()->create(['section_id' => $section->id, 'slug' => 'tech']);

    return [
        'section' => $section,
        'category' => $category,
        'article' => CmsArticle::factory()->create(['section_id' => $section->id, 'category_id' => $category->id, 'slug' => 'ai']),
        'root article' => CmsArticle::factory()->create(['section_id' => $section->id, 'category_id' => null, 'slug' => 'intro']),
        'album' => $album = CmsAlbum::factory()->create(['slug' => 'trip', 'type' => 'normal', 'status' => 'active']),
        'image' => CmsAlbumImage::factory()->create(['album_id' => $album->id]),
        'download' => $download = CmsDownload::factory()->create(['slug' => 'forms']),
        'file' => CmsDownloadFile::factory()->create(['download_id' => $download->id]),
        'page' => CmsPage::factory()->create(['slug' => 'about', 'full_slug' => 'about']),
    ];
}

describe('CmsUrl paths and links', function () {
    it('returns the routed path of every content type', function () {
        $tree = urlTestTree();

        expect(array_map(fn ($model) => CmsUrl::path($model), $tree))->toBe([
            'section' => 'news',
            'category' => 'news/tech',
            'article' => 'news/tech/ai',
            'root article' => 'news/intro',
            'album' => 'gallery/trip',
            'image' => 'gallery/trip',
            'download' => 'downloads/forms',
            'file' => 'downloads/forms',
            'page' => 'about',
        ]);
    });

    it('builds locale-prefixed paths with an optional fragment', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'trip', 'type' => 'normal', 'status' => 'active']);

        expect(CmsUrl::to($album))->toBe('/en/gallery/trip')
            ->and(CmsUrl::to($album, 'id', 'item-5'))->toBe('/id/gallery/trip#item-5')
            ->and(CmsUrl::to('/'))->toBe('/en')
            ->and(CmsUrl::to('downloads', 'id'))->toBe('/id/downloads');
    });

    it('builds plain paths when multi-language is off', function () {
        config(['synapps.apps.multi_language' => false]);

        expect(CmsUrl::to('gallery/trip'))->toBe('/gallery/trip')
            ->and(CmsUrl::to('/'))->toBe('/');
    });

    it('localizes site-internal hrefs only', function (string $href, string $expected) {
        // The backend case below assumes this prefix; pin it rather than inherit the host's.
        config(['synapps.auth.routes.backend_prefix' => '/apps']);
        app()->setLocale('id');

        expect(CmsUrl::localizeHref($href))->toBe($expected);
    })->with([
        'path' => ['/contact', '/id/contact'],
        'query and fragment' => ['/contact?ref=menu#form', '/id/contact?ref=menu#form'],
        'home' => ['/', '/id'],
        'already prefixed' => ['/en/about', '/en/about'],
        'backend' => ['/apps/cms/page', '/apps/cms/page'],
        'external' => ['https://example.com/x', 'https://example.com/x'],
        'protocol-relative' => ['//cdn.example.com/a.js', '//cdn.example.com/a.js'],
        'anchor' => ['#top', '#top'],
        'mailto' => ['mailto:a@b.c', 'mailto:a@b.c'],
    ]);

    it('leaves hrefs alone when multi-language is off', function () {
        config(['synapps.apps.multi_language' => false]);

        expect(CmsUrl::localizeHref('/contact'))->toBe('/contact');
    });

    it('registers fallback routes at the same paths', function () {
        $tree = urlTestTree();

        foreach (['category', 'article', 'root article', 'album', 'download', 'page'] as $key) {
            $this->assertDatabaseHas('fallback_routes', ['slug' => CmsUrl::path($tree[$key])]);
        }
    });
});

describe('Frontend links', function () {
    it('never hand-builds root-relative links in frontend templates', function () {
        $root = dirname(__DIR__, 2);
        $offenders = [];

        foreach ([...glob("{$root}/resources/views/components/frontend/*/*.blade.php"), ...glob("{$root}/resources/views/components/widget/*/*.blade.php")] as $file) {
            foreach (file($file) as $number => $line) {
                if (str_contains($line, 'href="/')) {
                    $offenders[] = str_replace("{$root}/", '', $file).':'.($number + 1);
                }
            }
        }

        expect($offenders)->toBe([]);
    });

    it('keeps the visitor language in article breadcrumbs', function () {
        $tree = urlTestTree();
        app()->setLocale('id');

        Livewire::test('cms::frontend.article-viewer', ['article_id' => $tree['article']->id])
            ->assertSeeHtml('href="/id"')
            ->assertSeeHtml('href="/id/news"')
            ->assertSeeHtml('href="/id/news/tech"');
    });

    it('keeps the visitor language in section listings', function () {
        $tree = urlTestTree();
        app()->setLocale('id');

        Livewire::test('cms::frontend.section-index', ['section_id' => $tree['section']->id])
            ->assertSeeHtml('href="/id/news/tech"')
            ->assertSeeHtml('href="/id/news/tech/ai"');
    });
});

describe('Menu URLs', function () {
    it('localizes every target type and deep-links album images', function () {
        $tree = urlTestTree();
        $menu = CmsMenu::factory()->create();
        app()->setLocale('id');

        $url = fn (string $type, ?int $id = null, ?string $target = null) => CmsMenuItem::factory()
            ->create(['menu_id' => $menu->id, 'target_type' => $type, 'target_id' => $id, 'target_url' => $target])
            ->resolveUrl();

        expect($url('page', $tree['page']->id))->toBe('/id/about')
            ->and($url('section', $tree['section']->id))->toBe('/id/news')
            ->and($url('category', $tree['category']->id))->toBe('/id/news/tech')
            ->and($url('article', $tree['article']->id))->toBe('/id/news/tech/ai')
            ->and($url('album', $tree['album']->id))->toBe('/id/gallery/trip')
            ->and($url('album_image', $tree['image']->id))->toBe('/id/gallery/trip#item-'.$tree['image']->id)
            ->and($url('download', $tree['download']->id))->toBe('/id/downloads/forms')
            ->and($url('album_list'))->toBe('/id/gallery')
            ->and($url('page', 999999))->toBe('#');
    });

    it('localizes internal url targets but not external ones', function () {
        $menu = CmsMenu::factory()->create();
        app()->setLocale('id');

        $url = fn (?string $target) => CmsMenuItem::factory()
            ->create(['menu_id' => $menu->id, 'target_type' => 'url', 'target_url' => $target])
            ->resolveUrl();

        expect($url('/contact'))->toBe('/id/contact')
            ->and($url('https://example.com'))->toBe('https://example.com')
            ->and($url(null))->toBe('#');
    });
});

describe('Sitemap', function () {
    it('lists every language version with hreflang alternates', function () {
        config(['synapps.modules.cms.sitemap.static_paths' => []]);
        CmsPage::factory()->create(['slug' => 'about', 'full_slug' => 'about']);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)
            ->toContain('xmlns:xhtml="http://www.w3.org/1999/xhtml"')
            ->toContain('<loc>'.url('en/about').'</loc>')
            ->toContain('<loc>'.url('id/about').'</loc>')
            ->toContain('<xhtml:link rel="alternate" hreflang="id" href="'.url('id/about').'"/>')
            ->toContain('<xhtml:link rel="alternate" hreflang="x-default" href="'.url('en/about').'"/>')
            ->and(substr_count($xml, '<url>'))->toBe(2);
    });
});

describe('Language names and icons', function () {
    it('names languages natively, with module overrides and a code fallback', function () {
        config(['synapps.modules.cms.language_names' => ['id' => 'Bahasa Indonesia']]);

        expect(CmsLocale::displayName('en'))->toBe('English')
            ->and(CmsLocale::displayName('id'))->toBe('Bahasa Indonesia')
            ->and(CmsLocale::displayName('ja'))->toBe('日本語')
            ->and(CmsLocale::displayName('zz'))->toBe('ZZ');
    })->skip(! class_exists(Locale::class), 'intl is not installed');

    it('resolves language icons', function (array $icons, string $code, ?array $expected) {
        config(['synapps.modules.cms.language_icons' => $icons]);

        expect(CmsLocale::icon($code))->toBe($expected);
    })->with([
        'image path' => [['id' => '/storage/fm/public/flags/id.svg'], 'id', ['type' => 'image', 'value' => '/storage/fm/public/flags/id.svg']],
        'image url' => [['id' => 'https://cdn.example.com/id.png?v=2'], 'id', ['type' => 'image', 'value' => 'https://cdn.example.com/id.png?v=2']],
        'data uri' => [['id' => 'data:image/png;base64,AAAA'], 'id', ['type' => 'image', 'value' => 'data:image/png;base64,AAAA']],
        'css class' => [['id' => 'fi fi-id'], 'id', ['type' => 'class', 'value' => 'fi fi-id']],
        'emoji override' => [['en' => '🇺🇸'], 'en', ['type' => 'emoji', 'value' => '🇺🇸']],
        'region from code' => [[], 'pt_BR', ['type' => 'emoji', 'value' => '🇧🇷']],
        'built-in map' => [[], 'id', ['type' => 'emoji', 'value' => '🇮🇩']],
        'unknown' => [[], 'xx', null],
    ]);
});

function urlTestRequest(string $uri, array $server = []): void
{
    app()->instance('request', Request::create($uri, 'GET', [], [], [], $server));
}

describe('CmsUrl::alternates', function () {
    it('lists the current page in every language, keeping the query string', function () {
        urlTestRequest('/id/about-us?ref=x');
        app()->setLocale('id');

        $alternates = CmsUrl::alternates();

        expect(array_column($alternates, 'url'))->toBe(['/en/about-us?ref=x', '/id/about-us?ref=x'])
            ->and(array_column($alternates, 'absolute'))->toBe([url('en/about-us').'?ref=x', url('id/about-us').'?ref=x'])
            ->and(array_column($alternates, 'active'))->toBe([false, true])
            ->and(array_column($alternates, 'code'))->toBe(['en', 'id'])
            ->and($alternates[1]['icon'])->toBe(['type' => 'emoji', 'value' => '🇮🇩']);
    });

    it('is empty when there is nothing to switch', function () {
        urlTestRequest('/about-us');
        expect(CmsUrl::alternates())->toBe([]);

        urlTestRequest('/id/about-us');
        config(['synapps.apps.enabled_languages' => ['en']]);
        expect(CmsUrl::alternates())->toBe([]);

        config(['synapps.apps.enabled_languages' => ['en', 'id'], 'synapps.apps.multi_language' => false]);
        expect(CmsUrl::alternates())->toBe([]);
    });

    it('uses the referring page during a Livewire update', function () {
        urlTestRequest('/livewire/update', ['HTTP_X_LIVEWIRE' => '1', 'HTTP_REFERER' => url('id/news').'?page=2']);

        expect(array_column(CmsUrl::alternates(), 'url'))->toBe(['/en/news?page=2', '/id/news?page=2']);
    });
});

describe('Language switcher', function () {
    beforeEach(function () {
        urlTestRequest('/id/about');
        app()->setLocale('id');
    });

    it('renders an accessible native dropdown by default', function () {
        $html = Blade::render('<x-cms::language-switcher />');

        expect($html)->toContain('<details')
            ->toContain('cms-language-switcher--dropdown')
            ->toContain('<span class="cms-language-switcher__name">Indonesia</span>')
            ->toContain('href="/en/about"')
            ->toContain('hreflang="en"')
            ->toContain('lang="en"')
            ->toContain('aria-current="true"');
    });

    it('renders an inline list and can hide the current language', function () {
        $html = Blade::render('<x-cms::language-switcher variant="list" hide-current />');

        expect($html)->toContain('<nav')
            ->toContain('cms-language-switcher--list')
            ->toContain('href="/en/about"')
            ->not->toContain('href="/id/about"');
    });

    it('supports every display mode', function (string $display, array $contains) {
        $html = Blade::render('<x-cms::language-switcher variant="list" display="'.$display.'" />');

        foreach ($contains as $fragment) {
            expect($html)->toContain($fragment);
        }
    })->with([
        'name' => ['name', ['__name">English<']],
        'code' => ['code', ['__code">EN<']],
        'both' => ['both', ['__code">EN<', '__name">English<']],
        'icon' => ['icon', ['__icon" aria-hidden="true">🇬🇧<', 'aria-label="English"']],
        'icon-name' => ['icon-name', ['🇬🇧', '__name">English<']],
        'icon-code' => ['icon-code', ['🇬🇧', '__code">EN<']],
    ]);

    it('renders image icons as decorative images with an escaped src', function () {
        config(['synapps.modules.cms.language_icons' => ['en' => '/flags/en.svg?v="1"']]);

        expect(Blade::render('<x-cms::language-switcher variant="list" display="icon-name" />'))
            ->toContain('<img class="cms-language-switcher__icon" src="/flags/en.svg?v=&quot;1&quot;" alt=""');
    });

    it('can use a globe trigger with a custom icon class', function () {
        expect(Blade::render('<x-cms::language-switcher trigger="globe" globe-icon="fa-solid fa-globe" />'))
            ->toContain('<span class="fa-solid fa-globe" aria-hidden="true"></span>');
    });

    it('merges wrapper classes and attributes', function () {
        $html = Blade::render('<x-cms::language-switcher wrapper-class="my-switch" id="lang" data-test="1" />');

        expect($html)->toContain('my-switch')->toContain('id="lang"')->toContain('data-test="1"');
    });

    it('hands the data to a custom template', function () {
        $directory = sys_get_temp_dir().'/cms-switcher-'.uniqid();
        mkdir($directory);
        file_put_contents("{$directory}/switcher.blade.php", '{{ count($languages) }}|{{ $current[\'code\'] }}|{{ $extra }}');
        View::addNamespace('switchertest', $directory);

        expect(trim(Blade::render('<x-cms::language-switcher template="switchertest::switcher" :with="[\'extra\' => \'ok\']" />')))
            ->toBe('2|id|ok');
    });

    it('renders nothing when there is nothing to switch', function () {
        config(['synapps.apps.multi_language' => false]);

        expect(trim(Blade::render('<x-cms::language-switcher />')))->toBe('');
    });

    it('prints hreflang alternates for the layout head', function () {
        $html = Blade::render('<x-cms::hreflang />');

        expect($html)->toContain('<link rel="alternate" hreflang="en" href="'.url('en/about').'"')
            ->toContain('<link rel="alternate" hreflang="id" href="'.url('id/about').'"')
            ->toContain('<link rel="alternate" hreflang="x-default" href="'.url('en/about').'"');
    });
});
