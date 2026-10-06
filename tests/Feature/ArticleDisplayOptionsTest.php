<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Support\CmsUrl;

require_once __DIR__.'/MediaGallery/helpers.php';

uses(RefreshDatabase::class);

function adoSection(array $config = []): CmsSection
{
    return CmsSection::factory()->create(['status' => 'published', 'config' => [...['items_per_page' => 12], ...$config]]);
}

describe('model helpers', function () {
    it('resolves timestamp and excerpt from article, then section, then default', function (?bool $article, ?bool $section, bool $expected) {
        $sectionModel = adoSection(array_filter(['show_date' => $section, 'show_excerpt' => $section], fn ($v) => $v !== null));
        $articleModel = CmsArticle::factory()->create([
            'section_id' => $sectionModel->id,
            'config' => ['show_date' => $article, 'show_excerpt' => $article],
        ]);

        expect($articleModel->showsDate())->toBe($expected)
            ->and($articleModel->showsExcerpt())->toBe($expected);
    })->with([
        'all default' => [null, null, true],
        'section hides' => [null, false, false],
        'article shows over section' => [true, false, true],
        'article hides over section' => [false, true, false],
    ]);
});

describe('forms', function () {
    it('saves section show_excerpt', function () {
        $section = adoSection();

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.section-form', ['id' => $section->id])
            ->assertSet('form.show_excerpt', true)
            ->set('form.show_excerpt', false)
            ->call('save')
            ->assertHasNoErrors();

        expect($section->fresh()->config['show_excerpt'])->toBeFalse();
    });

    it('saves article overrides as null/true/false', function () {
        $section = adoSection();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
            ->assertSet('form.show_date', 'inherit')
            ->set('form.show_date', 'hide')
            ->set('form.show_excerpt', 'show')
            ->call('save')
            ->assertHasNoErrors();

        $config = $article->fresh()->config;
        expect($config['show_date'])->toBeFalse()
            ->and($config['show_excerpt'])->toBeTrue();
    });

    it('stores inherit as null', function () {
        $section = adoSection();
        $article = CmsArticle::factory()->create(['section_id' => $section->id, 'config' => ['show_date' => false]]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
            ->assertSet('form.show_date', 'hide')
            ->set('form.show_date', 'inherit')
            ->call('save')
            ->assertHasNoErrors();

        expect($article->fresh()->config['show_date'])->toBeNull();
    });

    it('rejects unknown override values', function () {
        $section = adoSection();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);

        Livewire::actingAs(mgAdmin())
            ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
            ->set('form.show_date', 'bogus')
            ->call('save')
            ->assertHasErrors('form.show_date');
    });
});

describe('frontend', function () {
    it('hides timestamp and excerpt on the detail page per section, with article override', function () {
        $section = adoSection(['show_date' => false, 'show_excerpt' => false]);
        $hidden = CmsArticle::factory()->create(['section_id' => $section->id, 'excerpt' => 'Hidden excerpt text', 'published_at' => '2026-01-15 10:00']);
        $shown = CmsArticle::factory()->create(['section_id' => $section->id, 'excerpt' => 'Shown excerpt text', 'published_at' => '2026-01-15 10:00',
            'config' => ['show_date' => true, 'show_excerpt' => true]]);

        $this->get(CmsUrl::to($hidden->fresh()))->assertOk()
            ->assertDontSee('cms-article__date', false)
            ->assertDontSee('cms-article__excerpt', false);

        $this->get(CmsUrl::to($shown->fresh()))->assertOk()
            ->assertSee('cms-article__date', false)
            ->assertSee('<p>Shown excerpt text</p>', false);
    });

    it('applies an article override to its own card in section and category lists', function (string $component, string $param, Closure $id) {
        $section = adoSection();
        $category = CmsCategory::factory()->create(['section_id' => $section->id]);
        CmsArticle::factory()->create(['section_id' => $section->id, 'category_id' => $category->id, 'published_at' => '2026-01-15 10:00']);
        CmsArticle::factory()->create(['section_id' => $section->id, 'category_id' => $category->id, 'published_at' => '2026-02-20 10:00', 'config' => ['show_date' => false]]);

        Livewire::test($component, [$param => $id($section, $category)])
            ->assertSee('2026-01-15', false)
            ->assertDontSee('2026-02-20', false);
    })->with([
        'section list' => ['cms::frontend.section-index', 'section_id', fn ($s, $c) => $s->id],
        'category list' => ['cms::frontend.category-index', 'category_id', fn ($s, $c) => $c->id],
    ]);

    it('paginates lists by the section items_per_page', function () {
        $section = adoSection(['items_per_page' => 2]);
        CmsArticle::factory()->count(3)->create(['section_id' => $section->id]);

        $html = Livewire::test('cms::frontend.section-index', ['section_id' => $section->id])->html();

        expect(substr_count($html, 'class="cms-article-card__title"'))->toBe(2);
    });

    it('respects the article override in widgets', function () {
        $section = adoSection();
        CmsArticle::factory()->create(['section_id' => $section->id, 'published_at' => '2026-03-10 10:00', 'config' => ['show_date' => false]]);
        CmsArticle::factory()->create(['section_id' => $section->id, 'published_at' => '2026-03-11 10:00']);

        $this->blade('<x-cms::widget.articles :limit="10" />')
            ->assertDontSee('2026-03-10', false)
            ->assertSee('2026-03-11', false);
    });
});
