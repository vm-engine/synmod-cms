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
use VmEngine\Cms\Models\CmsTranslation;
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

    $role = Role::factory();
    foreach ([
        ['page'], ['article'], ['article', 'section'], ['article', 'category'],
        ['gallery', 'album'], ['gallery', 'image'], ['download', 'category'], ['download', 'file'], ['menu'],
    ] as $acl) {
        $role = $role->has(RolePermission::factory()->forModule('cms', ...$acl)->fullCrud());
    }
    $role = $role->create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]);

    $this->user = User::factory()->create();
    $this->user->roles()->attach($role);
});

describe('Page form translations', function () {
    it('creates a page with translations', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form')
            ->set('form.title', 'Contact Us')
            ->set('form.slug', 'contact-us')
            ->set('form.status', 'published')
            ->set('form.translations.id.title', 'Hubungi Kami')
            ->set('form.translations.id.body', '<p>Isi</p>')
            ->call('save')
            ->assertHasNoErrors();

        $page = CmsPage::where('slug', 'contact-us')->firstOrFail();
        expect($page->title)->toBe('Contact Us')
            ->and($page->translate('title', 'id'))->toBe('Hubungi Kami')
            ->and($page->translate('body', 'id'))->toBe('<p>Isi</p>');
    });

    it('fills translations when editing', function () {
        $page = CmsPage::factory()->create();
        $page->setTranslations(['id' => ['title' => 'Halo']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSet('form.translations.id.title', 'Halo');
    });

    it('updates translations', function () {
        $page = CmsPage::factory()->create();
        $page->setTranslations(['id' => ['title' => 'Halo']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.translations.id.title', 'Halo lagi')
            ->call('save')
            ->assertHasNoErrors();

        expect($page->fresh()->translate('title', 'id'))->toBe('Halo lagi');
    });

    it('removes a translation whose fields were all cleared', function () {
        $page = CmsPage::factory()->create();
        $page->setTranslations(['id' => ['title' => 'Halo']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.translations.id.title', '')
            ->call('save')
            ->assertHasNoErrors();

        expect(CmsTranslation::count())->toBe(0);
    });

    it('validates translations with the base rules, made optional', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.translations.id.title', str_repeat('a', 256))
            ->call('save')
            ->assertHasErrors(['form.translations.id.title' => 'max']);
    });

    it('names translated fields by their label in validation messages', function () {
        $page = CmsPage::factory()->create();

        $errors = Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.translations.id.title', str_repeat('a', 256))
            ->call('save')
            ->errors();

        expect($errors->first('form.translations.id.title'))->toContain(__('cms::labels.title'))
            ->not->toContain('translations.id.title');
    });
});

describe('Form translations', function () {
    it('saves translations from the form', function (Closure $make, string $component, Closure $params, string $field) {
        $model = $make();

        Livewire::actingAs($this->user)
            ->test($component, $params($model))
            ->set("form.translations.id.{$field}", 'Terjemahan')
            ->call('save')
            ->assertHasNoErrors();

        expect($model->fresh()->translate($field, 'id'))->toBe('Terjemahan');
    })->with([
        'article' => [fn () => CmsArticle::factory()->create(), 'cms::backend.article-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id], 'excerpt'],
        'section' => [fn () => CmsSection::factory()->create(), 'cms::backend.section-form', fn ($m) => ['id' => $m->id], 'title'],
        'category' => [fn () => CmsCategory::factory()->create(), 'cms::backend.category-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id], 'title'],
        'album' => [fn () => CmsAlbum::factory()->create(), 'cms::backend.album-form', fn ($m) => ['id' => $m->id], 'description'],
        'download' => [fn () => CmsDownload::factory()->create(), 'cms::backend.download-form', fn ($m) => ['id' => $m->id], 'title'],
        'album image' => [fn () => CmsAlbumImage::factory()->create(), 'cms::backend.album-image-form', fn ($m) => ['albumId' => $m->album_id, 'id' => $m->id], 'caption'],
        'download file' => [fn () => CmsDownloadFile::factory()->create(), 'cms::backend.download-file-form', fn ($m) => ['downloadId' => $m->download_id, 'id' => $m->id], 'description'],
    ]);

    it('creates an article with translations', function () {
        $section = CmsSection::factory()->create();

        Livewire::actingAs($this->user)
            ->test('cms::backend.article-form', ['sectionId' => $section->id])
            ->set('form.section_id', $section->id)
            ->set('form.title', 'First Post')
            ->set('form.slug', 'first-post')
            ->set('form.status', 'published')
            ->set('form.translations.id.title', 'Tulisan Pertama')
            ->call('save')
            ->assertHasNoErrors();

        expect(CmsArticle::where('slug', 'first-post')->firstOrFail()->translate('title', 'id'))->toBe('Tulisan Pertama');
    });
});

describe('Translation components', function () {
    it('renders a tab per enabled locale, primary first', function () {
        $html = Blade::render('<x-cms::locale-tabs :values="$values" />', ['values' => ['id' => ['title' => 'Halo']]]);

        expect($html)->toContain("select('en')")
            ->and($html)->toContain("select('id')")
            ->and(strpos($html, "select('en')"))->toBeLessThan(strpos($html, "select('id')"))
            ->and($html)->toContain('●');
    });

    it('renders no tabs when multi-language is off', function () {
        config(['synapps.apps.multi_language' => false]);

        expect(trim(Blade::render('<x-cms::locale-tabs />')))->toBe('');
    });

    it('renders a text input per secondary locale', function () {
        $html = Blade::render('<x-cms::translation-input field="title" label="Title" placeholder="Hello" />');

        expect($html)->toContain('wire:model="form.translations.id.title"')
            ->and($html)->toContain("x-show=\"is('id')\"")
            ->and($html)->toContain('placeholder="Hello"')
            ->and($html)->not->toContain('form.translations.en.');
    });

    it('renders a rich-text editor per secondary locale', function () {
        $html = Blade::render(
            '<x-cms::translation-input field="body" label="Body" type="rte" :values="$values" />',
            ['values' => ['id' => ['body' => '<p>Isi</p>']]],
        );

        expect($html)->toContain('name="form.translations.id.body"')
            ->and($html)->toContain('&lt;p&gt;Isi&lt;/p&gt;');
    });

    it('renders a badge per translated secondary locale', function () {
        $page = CmsPage::factory()->create();
        $page->setTranslations(['id' => ['title' => 'Halo']]);

        expect(Blade::render('<x-cms::locale-badges :model="$model" />', ['model' => $page->fresh()]))->toContain('ID');
    });
});

describe('Form views', function () {
    it('renders the language switcher and translation inputs', function (Closure $make, string $component, Closure $params, string $field) {
        Livewire::actingAs($this->user)
            ->test($component, $params($make()))
            ->assertSeeHtml("x-data=\"cmsLocaleTabs('en')\"")
            ->assertSeeHtml("select('id')")
            ->assertSeeHtml("form.translations.id.{$field}")
            ->assertSeeHtml('x-show="isPrimary()"');
    })->with([
        'page' => [fn () => CmsPage::factory()->create(), 'cms::backend.page-form', fn ($m) => ['id' => $m->id], 'body'],
        'article' => [fn () => CmsArticle::factory()->create(), 'cms::backend.article-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id], 'body'],
        'section' => [fn () => CmsSection::factory()->create(), 'cms::backend.section-form', fn ($m) => ['id' => $m->id], 'description'],
        'category' => [fn () => CmsCategory::factory()->create(), 'cms::backend.category-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id], 'meta_description'],
        'album' => [fn () => CmsAlbum::factory()->create(), 'cms::backend.album-form', fn ($m) => ['id' => $m->id], 'meta_title'],
        'download' => [fn () => CmsDownload::factory()->create(), 'cms::backend.download-form', fn ($m) => ['id' => $m->id], 'description'],
        'album image' => [fn () => CmsAlbumImage::factory()->create(), 'cms::backend.album-image-form', fn ($m) => ['albumId' => $m->album_id, 'id' => $m->id], 'caption'],
        'download file' => [fn () => CmsDownloadFile::factory()->create(), 'cms::backend.download-file-form', fn ($m) => ['downloadId' => $m->download_id, 'id' => $m->id], 'title'],
    ]);

    it('marks a language tab that has validation errors', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.translations.id.title', str_repeat('a', 256))
            ->call('save')
            ->assertSeeHtml('ph ph-warning-circle');
    });
});

describe('Menu item translations', function () {
    beforeEach(function () {
        $this->menu = CmsMenu::factory()->create();
    });

    it('saves label translations when adding an item', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-item-manager', ['menuId' => $this->menu->id])
            ->set('newLabel', 'Home')
            ->set('newTargetType', 'url')
            ->set('newTargetUrl', '/')
            ->set('newLabelTranslations.id', 'Beranda')
            ->call('addItem')
            ->assertHasNoErrors();

        expect(CmsMenuItem::where('label', 'Home')->firstOrFail()->translate('label', 'id'))->toBe('Beranda');
    });

    it('fills label translations when editing an item', function () {
        $item = CmsMenuItem::factory()->create(['menu_id' => $this->menu->id]);
        $item->setTranslations(['id' => ['label' => 'Beranda']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-item-manager', ['menuId' => $this->menu->id])
            ->call('startEdit', $item->id)
            ->assertSet('editLabelTranslations.id', 'Beranda');
    });

    it('updates label translations', function () {
        $item = CmsMenuItem::factory()->create(['menu_id' => $this->menu->id, 'target_type' => 'url', 'target_url' => '/']);

        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-item-manager', ['menuId' => $this->menu->id])
            ->call('startEdit', $item->id)
            ->set('editLabelTranslations.id', 'Tentang')
            ->call('saveEdit')
            ->assertHasNoErrors();

        expect($item->fresh()->translate('label', 'id'))->toBe('Tentang');
    });

    it('renders a label input per secondary locale', function () {
        Livewire::actingAs($this->user)
            ->test('cms::backend.menu-item-manager', ['menuId' => $this->menu->id])
            ->assertSeeHtml('wire:model="newLabelTranslations.id"');
    });
});

describe('List search and badges', function () {
    it('finds an article by its translated title in the list', function () {
        $article = CmsArticle::factory()->create(['title' => 'Zebrafish']);
        $article->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.article-list')
            ->set('q', 'Ikanzebra')
            ->assertSee('Zebrafish');
    });

    it('finds a page by its translated title in the list', function () {
        $page = CmsPage::factory()->create(['title' => 'Zebrapage']);
        $page->setTranslations(['id' => ['title' => 'Halamanzebra']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.page-list')
            ->set('q', 'Halamanzebra')
            ->assertSee('Zebrapage');
    });

    it('shows a badge for each translated locale in the article list', function () {
        $article = CmsArticle::factory()->create();
        $article->setTranslations(['id' => ['title' => 'Ikanzebra']]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.article-list')
            ->assertSeeHtml('data-locale-badges');
    });
});
