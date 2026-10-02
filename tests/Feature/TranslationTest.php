<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Models\CmsTranslation;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\TranslatableContent;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'app.locale' => 'en',
        'synapps.apps.multi_language' => true,
        'synapps.apps.enabled_languages' => ['en', 'id'],
    ]);
});

describe('CmsLocale', function () {
    it('defaults the primary locale to app.locale', function () {
        expect(CmsLocale::primary())->toBe('en');
    });

    it('keeps the primary locale when the runtime locale changes', function () {
        // SetFallbackLocale calls app()->setLocale(), which rewrites config('app.locale').
        app()->setLocale('id');

        expect(CmsLocale::primary())->toBe('en')
            ->and(CmsLocale::secondary())->toBe(['id']);
    });

    it('uses the module primary_locale setting when set', function () {
        config(['synapps.modules.cms.primary_locale' => 'id']);

        expect(CmsLocale::primary())->toBe('id')
            ->and(CmsLocale::all())->toBe(['id', 'en'])
            ->and(CmsLocale::secondary())->toBe(['en']);
    });

    it('lists the primary locale first, without duplicates', function () {
        config(['synapps.apps.enabled_languages' => ['id', 'en', 'ja']]);

        expect(CmsLocale::all())->toBe(['en', 'id', 'ja'])
            ->and(CmsLocale::secondary())->toBe(['id', 'ja']);
    });

    it('declares its settings in module.json', function () {
        $module = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/module.json'), true);

        expect($module)->toHaveKey('primary_locale')
            ->and($module['search'])->toMatchArray(['engine' => 'database', 'scout_content_limit' => 5000])
            ->and(file_exists(dirname(__DIR__, 2).'/config/cms.php'))->toBeFalse();
    });

    it('only knows the primary locale when multi-language is off', function () {
        config(['synapps.apps.multi_language' => false]);

        expect(CmsLocale::isMultiLanguage())->toBeFalse()
            ->and(CmsLocale::all())->toBe(['en'])
            ->and(CmsLocale::secondary())->toBe([]);
    });
});

describe('CmsTranslation', function () {
    it('prunes rows whose owner no longer exists', function () {
        $section = CmsSection::factory()->create();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);

        CmsTranslation::create([
            'translatable_type' => CmsArticle::class,
            'translatable_id' => $article->id,
            'locale' => 'id',
            'title' => 'Halo',
        ]);
        CmsTranslation::create([
            'translatable_type' => CmsArticle::class,
            'translatable_id' => 999999,
            'locale' => 'id',
            'title' => 'Yatim',
        ]);

        expect(CmsTranslation::pruneOrphans())->toBe(1);
        $this->assertDatabaseHas('cms_translations', ['translatable_id' => $article->id]);
        $this->assertDatabaseMissing('cms_translations', ['translatable_id' => 999999]);
    });

    it('drops rows of a type whose class no longer exists', function () {
        CmsTranslation::create([
            'translatable_type' => 'VmEngine\\Cms\\Models\\Removed',
            'translatable_id' => 1,
            'locale' => 'id',
            'title' => 'x',
        ]);

        expect(CmsTranslation::pruneOrphans())->toBe(1);
    });
});

describe('HasTranslations', function () {
    beforeEach(function () {
        $this->article = CmsArticle::factory()->create([
            'section_id' => CmsSection::factory()->create()->id,
            'title' => 'Hello',
            'excerpt' => 'Primary excerpt',
            'body' => '<p>Primary body</p>',
        ]);
    });

    it('returns the translation for the requested locale', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo', 'body' => '<p>Isi</p>']]);

        expect($this->article->translate('title', 'id'))->toBe('Halo')
            ->and($this->article->translate('body', 'id'))->toBe('<p>Isi</p>');
    });

    it('uses the app locale when none is given', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);
        app()->setLocale('id');

        expect($this->article->translate('title'))->toBe('Halo');
    });

    it('falls back to the base column per field', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);

        expect($this->article->translate('excerpt', 'id'))->toBe('Primary excerpt');
    });

    it('returns the base column for the primary locale', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);

        expect($this->article->translate('title', 'en'))->toBe('Hello');
    });

    it('returns the base column when multi-language is off', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);
        config(['synapps.apps.multi_language' => false]);

        expect($this->article->fresh()->translate('title', 'id'))->toBe('Hello');
    });

    it('ignores the primary locale and disabled locales on write', function () {
        $this->article->setTranslations([
            'en' => ['title' => 'Overwrite?'],
            'fr' => ['title' => 'Bonjour'],
        ]);

        expect(CmsTranslation::count())->toBe(0)
            ->and($this->article->fresh()->title)->toBe('Hello');
    });

    it('updates an existing translation in place', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);
        $this->article->setTranslations(['id' => ['title' => 'Halo lagi']]);

        expect(CmsTranslation::count())->toBe(1)
            ->and($this->article->translate('title', 'id'))->toBe('Halo lagi');
    });

    it('deletes the row when every field of a locale is empty', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);
        $this->article->setTranslations(['id' => ['title' => '  ', 'body' => '<p><br></p>']]);

        expect(CmsTranslation::count())->toBe(0);
    });

    it('purifies HTML fields with the cms_rte profile', function () {
        $this->article->setTranslations(['id' => ['body' => '<p>Isi</p><script>alert(1)</script>']]);

        expect($this->article->translate('body', 'id'))->toBe('<p>Isi</p>');
    });

    it('keeps an HTML field that holds only local media', function () {
        $this->article->setTranslations(['id' => ['body' => '<p><img src="/storage/a.jpg" alt=""></p>']]);

        expect($this->article->translate('body', 'id'))->toContain('<img');
    });

    it('does not purify plain-text fields', function () {
        $this->article->setTranslations(['id' => ['title' => 'A & B <3']]);

        expect($this->article->translate('title', 'id'))->toBe('A & B <3');
    });

    it('removes its translations when the model is deleted', function () {
        $this->article->setTranslations(['id' => ['title' => 'Halo']]);
        $this->article->delete();

        expect(CmsTranslation::count())->toBe(0);
    });
});

describe('Translatable models', function () {
    it('declares only real, translatable columns', function (string $class, array $fields, array $html) {
        $model = new $class;

        expect($model)->toBeInstanceOf(TranslatableContent::class)
            ->and($model->translatableFields())->toBe($fields)
            ->and($model->translatableHtmlFields())->toBe($html)
            ->and(array_diff($fields, CmsTranslation::FIELDS))->toBe([])
            ->and(array_diff($html, $fields))->toBe([]);

        foreach ($fields as $field) {
            expect(Schema::hasColumn($model->getTable(), $field))->toBeTrue("{$class} has no {$field} column");
        }
    })->with([
        'page' => [CmsPage::class, ['title', 'excerpt', 'body', 'meta_title', 'meta_description'], ['body']],
        'article' => [CmsArticle::class, ['title', 'excerpt', 'body', 'meta_title', 'meta_description'], ['body']],
        'section' => [CmsSection::class, ['title', 'description', 'meta_title', 'meta_description'], ['description']],
        'category' => [CmsCategory::class, ['title', 'description', 'meta_title', 'meta_description'], ['description']],
        'album' => [CmsAlbum::class, ['title', 'description', 'meta_title', 'meta_description'], []],
        'album image' => [CmsAlbumImage::class, ['title', 'caption'], []],
        'download' => [CmsDownload::class, ['title', 'description', 'meta_title', 'meta_description'], []],
        'download file' => [CmsDownloadFile::class, ['title', 'description'], []],
        'menu item' => [CmsMenuItem::class, ['label'], []],
    ]);

    it('prunes child translations removed by a DB-level cascade', function () {
        $album = CmsAlbum::factory()->create();
        $image = CmsAlbumImage::factory()->create(['album_id' => $album->id]);
        $image->setTranslations(['id' => ['caption' => 'Pantai']]);

        $album->delete();

        expect(CmsTranslation::count())->toBe(0);
    });
});

describe('translationValues', function () {
    it('returns secondary-locale values keyed by locale and field', function () {
        config(['synapps.apps.enabled_languages' => ['en', 'id', 'ja']]);
        $page = CmsPage::factory()->create();
        $page->setTranslations(['id' => ['title' => 'Halo']]);

        expect($page->fresh()->translationValues())->toBe([
            'id' => ['title' => 'Halo', 'excerpt' => '', 'body' => '', 'meta_title' => '', 'meta_description' => ''],
        ]);
    });
});

describe('Search with translations', function () {
    it('finds a model by its translated title', function (Closure $make) {
        $model = $make();
        $model->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        expect($model::search('Ikanzebra')->pluck('id')->all())->toBe([$model->id])
            ->and($model::search('Zebrafish')->pluck('id')->all())->toBe([$model->id]);
    })->with([
        'page' => fn () => CmsPage::factory()->create(['title' => 'Zebrafish']),
        'article' => fn () => CmsArticle::factory()->create(['title' => 'Zebrafish']),
        'section' => fn () => CmsSection::factory()->create(['title' => 'Zebrafish']),
        'category' => fn () => CmsCategory::factory()->create(['title' => 'Zebrafish']),
        'album' => fn () => CmsAlbum::factory()->create(['title' => 'Zebrafish']),
        'download' => fn () => CmsDownload::factory()->create(['title' => 'Zebrafish']),
    ]);

    it('keeps the translation match inside the search group', function () {
        $article = CmsArticle::factory()->create(['title' => 'Zebrafish', 'status' => 'published']);
        $article->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        expect(CmsArticle::query()->where('status', 'draft')->search('Ikanzebra')->count())->toBe(0);
    });

    it('ignores translations when multi-language is off', function () {
        $article = CmsArticle::factory()->create(['title' => 'Zebrafish']);
        $article->setTranslations(['id' => ['title' => 'Ikanzebra']]);
        config(['synapps.apps.multi_language' => false]);

        expect(CmsArticle::search('Ikanzebra')->count())->toBe(0);
    });
});
