<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
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
use VmEngine\Fm\Models\FmFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
    ]);
});

describe('Eager loading', function () {
    it('loads translations with every query and nested relation', function () {
        CmsArticle::factory()->create();

        $article = CmsArticle::with('section')->firstOrFail();

        expect($article->relationLoaded('translations'))->toBeTrue()
            ->and($article->section->relationLoaded('translations'))->toBeTrue();
    });

    it('does not load translations when multi-language is off', function () {
        CmsArticle::factory()->create();
        config(['synapps.apps.multi_language' => false]);

        expect(CmsArticle::firstOrFail()->relationLoaded('translations'))->toBeFalse();
    });

    it('keeps translations out of toArray()', function () {
        $article = CmsArticle::factory()->create();
        $article->setTranslations(['id' => ['title' => 'Halo']]);

        expect(CmsArticle::firstOrFail()->toArray())->not->toHaveKey('translations');
    });
});

describe('Meta title', function () {
    beforeEach(function () {
        $this->article = CmsArticle::factory()->create(['title' => 'Hello', 'meta_title' => 'Hello | Site']);
    });

    it('returns null without fallback when the locale has no value', function () {
        expect($this->article->translate('meta_title', 'id', fallback: false))->toBeNull()
            ->and($this->article->translate('meta_title', 'en', fallback: false))->toBe('Hello | Site');
    });

    it("uses the locale's own meta title", function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo', 'meta_title' => 'Halo | Situs']]);
        app()->setLocale('id');

        expect($this->article->translatedMetaTitle())->toBe('Halo | Situs');
    });

    it("falls back to the locale's title, not the default meta title", function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);
        app()->setLocale('id');

        expect($this->article->translatedMetaTitle())->toBe('Halo');
    });

    it('keeps the default-language behaviour', function () {
        expect($this->article->translatedMetaTitle())->toBe('Hello | Site')
            ->and(CmsArticle::factory()->create(['title' => 'Plain', 'meta_title' => null])->translatedMetaTitle())->toBe('Plain');
    });
});

function translationTestFile(): FmFile
{
    return FmFile::create([
        'disk' => 'public',
        'folder_path' => 'fm/public',
        'relative_path' => 'test-image.jpg',
        'filename' => 'test-image.jpg',
        'original_name' => 'test-image.jpg',
        'extension' => 'jpg',
        'mime_type' => 'image/jpeg',
        'size' => 1024,
        'has_thumbnail' => false,
        'is_trashed' => false,
    ]);
}

describe('Viewers', function () {
    it('renders the visitor language', function (Closure $make, string $component, string $param) {
        $model = $make();
        $model->setTranslations(['id' => ['title' => 'Ikanzebra']]);
        app()->setLocale('id');

        Livewire::test($component, [$param => $model->id])
            ->assertSee('Ikanzebra')
            ->assertDontSee('Zebrafish');
    })->with([
        'page' => [fn () => CmsPage::factory()->create(['title' => 'Zebrafish']), 'cms::frontend.page-viewer', 'page_id'],
        'article' => [fn () => CmsArticle::factory()->create(['title' => 'Zebrafish']), 'cms::frontend.article-viewer', 'article_id'],
        'section' => [fn () => CmsSection::factory()->create(['title' => 'Zebrafish', 'status' => 'published']), 'cms::frontend.section-index', 'section_id'],
        'category' => [fn () => CmsCategory::factory()->create(['title' => 'Zebrafish']), 'cms::frontend.category-index', 'category_id'],
        'album' => [fn () => CmsAlbum::factory()->create(['title' => 'Zebrafish', 'type' => 'normal', 'status' => 'active']), 'cms::frontend.album-viewer', 'album_id'],
        'download' => [fn () => CmsDownload::factory()->create(['title' => 'Zebrafish']), 'cms::frontend.download-viewer', 'download_id'],
    ]);

    it('falls back to the default language per field', function () {
        $article = CmsArticle::factory()->create(['title' => 'Zebrafish', 'body' => '<p>Primary body</p>']);
        $article->setTranslations(['id' => ['title' => 'Ikanzebra']]);
        app()->setLocale('id');

        Livewire::test('cms::frontend.article-viewer', ['article_id' => $article->id])
            ->assertSee('Ikanzebra')
            ->assertSeeHtml('<p>Primary body</p>');
    });

    it('renders the default language for the primary locale', function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrafish']);
        $page->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        Livewire::test('cms::frontend.page-viewer', ['page_id' => $page->id])
            ->assertSee('Zebrafish')
            ->assertDontSee('Ikanzebra');
    });

    it('translates album item captions', function () {
        $album = CmsAlbum::factory()->create(['type' => 'normal', 'status' => 'active']);
        $image = CmsAlbumImage::factory()->create(['album_id' => $album->id, 'file_id' => translationTestFile()->id, 'caption' => 'Beach']);
        $image->setTranslations(['id' => ['caption' => 'Pantai']]);
        app()->setLocale('id');

        Livewire::test('cms::frontend.album-viewer', ['album_id' => $album->id])->assertSee('Pantai');
    });

    it('translates download file titles', function () {
        $download = CmsDownload::factory()->create();
        $file = CmsDownloadFile::factory()->create(['download_id' => $download->id, 'file_id' => translationTestFile()->id, 'title' => 'Brochure']);
        $file->setTranslations(['id' => ['title' => 'Brosur']]);
        app()->setLocale('id');

        Livewire::test('cms::frontend.download-viewer', ['download_id' => $download->id])->assertSee('Brosur');
    });
});

describe('Menus and widgets', function () {
    beforeEach(function () {
        $menu = CmsMenu::factory()->create(['position' => 'header']);
        $item = CmsMenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Home']);
        $item->setTranslations(['id' => ['label' => 'Beranda']]);
        app()->setLocale('id');
    });

    it('translates menu labels in the menu component', function () {
        expect(Blade::render('<x-cms::menu position="header" />'))->toContain('Beranda')->not->toContain('>Home<');
    });

    it('translates menu labels in the Livewire menu', function () {
        Livewire::test('cms::frontend.menu', ['position' => 'header'])->assertSee('Beranda');
    });

    it('translates the articles widget', function () {
        $article = CmsArticle::factory()->create(['title' => 'Zebrafish']);
        $article->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        expect(Blade::render('<x-cms::widget.articles />'))->toContain('Ikanzebra')->not->toContain('Zebrafish');
    });

    it('translates the page widget', function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrafish', 'slug' => 'zebra', 'full_slug' => 'zebra']);
        $page->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        expect(Blade::render('<x-cms::widget.page slug="zebra" />'))->toContain('Ikanzebra');
    });

    it('translates banner slider captions', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'hero', 'type' => 'banner', 'status' => 'active']);
        $image = CmsAlbumImage::factory()->create(['album_id' => $album->id, 'file_id' => translationTestFile()->id, 'caption' => 'Beach']);
        $image->setTranslations(['id' => ['caption' => 'Pantai']]);

        expect(Blade::render('<x-cms::widget.banner-slider album="hero" />'))->toContain('Pantai');
    });

    it('translates gallery widget captions', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'trip', 'type' => 'normal', 'status' => 'active']);
        $image = CmsAlbumImage::factory()->create(['album_id' => $album->id, 'file_id' => translationTestFile()->id, 'caption' => 'Beach']);
        $image->setTranslations(['id' => ['caption' => 'Pantai']]);

        expect(Blade::render('<x-cms::widget.gallery album="trip" />'))->toContain('Pantai');
    });
});

describe('Template guard', function () {
    it('reads translatable fields through translate() in every frontend template', function () {
        $root = dirname(__DIR__, 2);
        $files = [
            ...glob("{$root}/resources/views/components/frontend/*/*"),
            ...glob("{$root}/resources/views/components/widget/*/*"),
            ...glob("{$root}/src/View/Components/Widget/*.php"),
        ];
        $offenders = [];

        // The search templates render CmsSearchable rows, whose text is already
        // localized at index time — they hold no translatable models.
        $files = array_filter($files, fn (string $file): bool => ! str_contains($file, '/frontend/search-'));

        foreach ($files as $file) {
            foreach (file($file) as $number => $line) {
                if (preg_match('/->(title|excerpt|body|description|caption|label|meta_title|meta_description)\b(?!\s*\()/', $line)) {
                    $offenders[] = str_replace("{$root}/", '', $file).':'.($number + 1);
                }
            }
        }

        expect($offenders)->toBe([]);
    });
});

describe('UI strings', function () {
    it('localizes article chrome and dates', function () {
        $article = CmsArticle::factory()->create([
            'title' => 'Zebrafish',
            'is_featured' => true,
            'read_time' => 5,
            'status' => 'published',
            'published_at' => '2026-10-02 10:00:00',
        ]);
        app()->setLocale('id');

        Livewire::test('cms::frontend.article-viewer', ['article_id' => $article->id])
            ->assertSee('Beranda')
            ->assertSee('Unggulan')
            ->assertSee('5 menit baca')
            ->assertSee('02 Oktober 2026')
            ->assertDontSee('min read');
    });

    it('localizes widget card meta', function () {
        CmsArticle::factory()->create(['read_time' => 3, 'status' => 'published', 'published_at' => '2026-10-02 10:00:00']);
        app()->setLocale('id');

        expect(Blade::render('<x-cms::widget.articles />'))->toContain('3 menit baca')->toContain('Oktober');
    });

    it('localizes banner slider controls', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'hero', 'type' => 'banner', 'status' => 'active']);
        $file = translationTestFile();
        CmsAlbumImage::factory()->count(2)->create(['album_id' => $album->id, 'file_id' => $file->id]);
        app()->setLocale('id');

        expect(Blade::render('<x-cms::widget.banner-slider album="hero" />'))
            ->toContain('aria-label="Sebelumnya"')
            ->toContain('aria-label="Berikutnya"')
            ->toContain('Ke slide :number');
    });
});

describe('UI string guard', function () {
    it('keeps hardcoded UI strings out of frontend and widget templates', function () {
        $root = dirname(__DIR__, 2);
        $patterns = [
            '/>\s*(Home|Featured)\s*</',
            '/min read/',
            '/aria-label="(breadcrumb|Close|Previous|Next)"/',
            "/'Go to slide/",
            "/format\\('d F Y'\\)/",
            "/\\?\\? 'File'/",
        ];
        $offenders = [];

        foreach ([...glob("{$root}/resources/views/components/frontend/*/*.blade.php"), ...glob("{$root}/resources/views/components/widget/*/*.blade.php")] as $file) {
            $source = (string) file_get_contents($file);
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $source)) {
                    $offenders[] = basename($file).' '.$pattern;
                }
            }
        }

        expect($offenders)->toBe([]);
    });
});

describe('Meta description', function () {
    it("uses the locale's own meta description", function () {
        $article = CmsArticle::factory()->create(['meta_description' => 'English meta', 'excerpt' => 'English excerpt']);
        $article->setTranslations(['id' => ['title' => 'Halo', 'meta_description' => 'Meta Indonesia']]);
        app()->setLocale('id');

        expect($article->translatedMetaDescription())->toBe('Meta Indonesia');
    });

    it("falls back to the locale's excerpt, not the default meta description", function () {
        $article = CmsArticle::factory()->create(['meta_description' => 'English meta', 'excerpt' => 'English excerpt']);
        $article->setTranslations(['id' => ['title' => 'Halo', 'excerpt' => '<p>Ringkasan <b>Indonesia</b></p>']]);
        app()->setLocale('id');

        expect($article->translatedMetaDescription())->toBe('Ringkasan Indonesia');
    });

    it('uses description for models without an excerpt and trims to 160 characters', function () {
        $section = CmsSection::factory()->create(['meta_description' => null, 'description' => str_repeat('word ', 60)]);

        expect($section->translatedMetaDescription())->toEndWith('...')
            ->and(mb_strlen((string) $section->translatedMetaDescription()))->toBeLessThanOrEqual(163);
    });

    it('returns null when there is nothing to describe', function () {
        expect(CmsSection::factory()->create(['meta_description' => null, 'description' => null])->translatedMetaDescription())->toBeNull();
    });

    it('exposes the description on the frontend viewers', function () {
        $article = CmsArticle::factory()->create(['meta_description' => 'Article meta']);
        $page = CmsPage::factory()->create(['meta_description' => 'Page meta']);

        expect(Livewire::test('cms::frontend.article-viewer', ['article_id' => $article->id])->instance()->description())->toBe('Article meta')
            ->and(Livewire::test('cms::frontend.page-viewer', ['page_id' => $page->id])->instance()->description())->toBe('Page meta');
    });
});
