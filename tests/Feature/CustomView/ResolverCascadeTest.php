<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

uses(RefreshDatabase::class);

describe('CmsPage custom view cascade', function () {
    it('returns own custom_view when set', function () {
        $page = CmsPage::factory()->create(['custom_view' => 'home::own-page']);

        expect($page->resolveCustomView())->toBe('home::own-page');
    });

    it('inherits custom_view from parent page when empty', function () {
        $parent = CmsPage::factory()->create(['custom_view' => 'home::parent-page']);
        $child = CmsPage::factory()->create([
            'parent_id' => $parent->id,
            'custom_view' => null,
        ]);

        expect($child->resolveCustomView())->toBe('home::parent-page');
    });

    it('returns null when neither own nor parent has custom_view', function () {
        $parent = CmsPage::factory()->create(['custom_view' => null]);
        $child = CmsPage::factory()->create([
            'parent_id' => $parent->id,
            'custom_view' => null,
        ]);

        expect($child->resolveCustomView())->toBeNull();
    });

    it('cascades custom_layout through parent page', function () {
        $parent = CmsPage::factory()->create(['custom_layout' => 'home::parent-layout']);
        $child = CmsPage::factory()->create([
            'parent_id' => $parent->id,
            'custom_layout' => null,
        ]);

        expect($child->resolveCustomLayout())->toBe('home::parent-layout');
    });
});

describe('CmsSection custom view resolvers', function () {
    it('returns own custom_view without inheritance', function () {
        $section = CmsSection::factory()->create(['custom_view' => 'home::section-view']);

        expect($section->resolveCustomView())->toBe('home::section-view');
    });

    it('exposes custom_category_view via resolveCategoryView', function () {
        $section = CmsSection::factory()->create([
            'custom_category_view' => 'home::section-category-view',
        ]);

        expect($section->resolveCategoryView())->toBe('home::section-category-view');
    });

    it('exposes custom_article_view via resolveArticleView', function () {
        $section = CmsSection::factory()->create([
            'custom_article_view' => 'home::section-article-view',
        ]);

        expect($section->resolveArticleView())->toBe('home::section-article-view');
    });
});

describe('CmsCategory custom view cascade', function () {
    it('returns own custom_view when set', function () {
        $section = CmsSection::factory()->create(['custom_category_view' => 'home::should-not-use']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_view' => 'home::own-category',
        ]);

        expect($category->resolveCustomView())->toBe('home::own-category');
    });

    it('inherits section.custom_category_view when empty', function () {
        $section = CmsSection::factory()->create(['custom_category_view' => 'home::section-cat-view']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_view' => null,
        ]);

        expect($category->resolveCustomView())->toBe('home::section-cat-view');
    });

    it('inherits custom_layout from section', function () {
        $section = CmsSection::factory()->create(['custom_layout' => 'home::section-layout']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_layout' => null,
        ]);

        expect($category->resolveCustomLayout())->toBe('home::section-layout');
    });

    it('inherits article_view from section when own custom_article_view empty', function () {
        $section = CmsSection::factory()->create(['custom_article_view' => 'home::section-art-view']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_article_view' => null,
        ]);

        expect($category->resolveArticleView())->toBe('home::section-art-view');
    });
});

describe('CmsArticle custom view cascade', function () {
    it('returns own custom_view when set', function () {
        $section = CmsSection::factory()->create(['custom_article_view' => 'home::should-not-use']);
        $article = CmsArticle::factory()->create([
            'section_id' => $section->id,
            'custom_view' => 'home::own-article',
        ]);

        expect($article->resolveCustomView())->toBe('home::own-article');
    });

    it('inherits category.custom_article_view when own empty', function () {
        $section = CmsSection::factory()->create(['custom_article_view' => 'home::section-art-view']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_article_view' => 'home::category-art-view',
        ]);
        $article = CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => $category->id,
            'custom_view' => null,
        ]);

        expect($article->resolveCustomView())->toBe('home::category-art-view');
    });

    it('falls through to section.custom_article_view when category empty', function () {
        $section = CmsSection::factory()->create(['custom_article_view' => 'home::section-art-view']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_article_view' => null,
        ]);
        $article = CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => $category->id,
            'custom_view' => null,
        ]);

        expect($article->resolveCustomView())->toBe('home::section-art-view');
    });

    it('cascades custom_layout via category then section', function () {
        $section = CmsSection::factory()->create(['custom_layout' => 'home::section-layout']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'custom_layout' => null,
        ]);
        $article = CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => $category->id,
            'custom_layout' => null,
        ]);

        expect($article->resolveCustomLayout())->toBe('home::section-layout');
    });
});

describe('Standalone model resolvers', function () {
    it('returns CmsAlbum own custom_view', function () {
        $album = CmsAlbum::factory()->create(['custom_view' => 'home::album-view']);

        expect($album->resolveCustomView())->toBe('home::album-view');
    });

    it('returns CmsDownload own custom_view', function () {
        $download = CmsDownload::create([
            'title' => 'Sample Download',
            'slug' => 'sample-download',
            'status' => 'active',
            'access' => 'public',
            'custom_view' => 'home::download-view',
        ]);

        expect($download->resolveCustomView())->toBe('home::download-view');
    });

    it('returns CmsMenuItem own custom_view', function () {
        $item = CmsMenuItem::factory()->create(['custom_view' => 'home::menu-view']);

        expect($item->resolveCustomView())->toBe('home::menu-view');
    });
});
