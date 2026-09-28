<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsMenuService;

uses(RefreshDatabase::class);

describe('CmsMenuService::getMenuByPosition', function () {
    it('returns the active menu for the given position', function () {
        $menu = CmsMenu::factory()->create(['position' => 'header', 'is_active' => true]);

        $found = app(CmsMenuService::class)->getMenuByPosition('header');

        expect($found?->id)->toBe($menu->id);
    });

    it('returns null when no menu exists for the position', function () {
        $found = app(CmsMenuService::class)->getMenuByPosition('nonexistent');

        expect($found)->toBeNull();
    });

    it('returns null when the menu for the position is inactive', function () {
        CmsMenu::factory()->inactive()->create(['position' => 'footer']);

        $found = app(CmsMenuService::class)->getMenuByPosition('footer');

        expect($found)->toBeNull();
    });
});

describe('CmsMenuService::buildTree', function () {
    it('returns empty collection when no menu exists for position', function () {
        $tree = app(CmsMenuService::class)->buildTree('nonexistent');

        expect($tree)->toBeEmpty();
    });

    it('returns root items only (no parent_id)', function () {
        $menu = CmsMenu::factory()->create(['position' => 'header', 'is_active' => true]);
        CmsMenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => null, 'label' => 'Home']);
        CmsMenuItem::factory()->create(['menu_id' => $menu->id, 'parent_id' => null, 'label' => 'About']);

        $tree = app(CmsMenuService::class)->buildTree('header');

        expect($tree)->toHaveCount(2);
    });

    it('excludes inactive menu items', function () {
        $menu = CmsMenu::factory()->create(['position' => 'header', 'is_active' => true]);
        CmsMenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Visible', 'is_active' => true]);
        CmsMenuItem::factory()->create(['menu_id' => $menu->id, 'label' => 'Hidden', 'is_active' => false]);

        $tree = app(CmsMenuService::class)->buildTree('header');

        expect($tree)->toHaveCount(1);
        expect($tree->first()->label)->toBe('Visible');
    });

    it('nests children under parent items', function () {
        $menu = CmsMenu::factory()->create(['position' => 'header', 'is_active' => true]);
        $parent = CmsMenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => null,
            'label' => 'Products',
            'sort_order' => 1,
        ]);
        CmsMenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'label' => 'Widget A',
            'sort_order' => 1,
        ]);
        CmsMenuItem::factory()->create([
            'menu_id' => $menu->id,
            'parent_id' => $parent->id,
            'label' => 'Widget B',
            'sort_order' => 2,
        ]);

        $tree = app(CmsMenuService::class)->buildTree('header');

        expect($tree)->toHaveCount(1);
        expect($tree->first()->children)->toHaveCount(2);
    });
});

describe('CmsMenuItem::resolveUrl', function () {
    it('resolves url target type', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'url',
            'target_url' => 'https://example.com',
            'target_id' => null,
        ]);

        expect($item->resolveUrl())->toBe('https://example.com');
    });

    it('returns # for url target with null url', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'url',
            'target_url' => null,
            'target_id' => null,
        ]);

        expect($item->resolveUrl())->toBe('#');
    });

    it('resolves album_list target type with custom slug', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'album_list',
            'target_url' => 'gallery',
            'target_id' => null,
        ]);

        expect($item->resolveUrl())->toBe('/gallery');
    });

    it('resolves album_list target type with default slug when empty', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'album_list',
            'target_url' => null,
            'target_id' => null,
        ]);

        expect($item->resolveUrl())->toBe('/gallery');
    });

    it('returns # when target_id is null for non-url types', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'page',
            'target_id' => null,
        ]);

        expect($item->resolveUrl())->toBe('#');
    });

    it('resolves page target type', function () {
        $page = CmsPage::factory()->create(['slug' => 'about', 'full_slug' => 'about']);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'page',
            'target_id' => $page->id,
        ]);

        expect($item->resolveUrl())->toBe('/about');
    });

    it('returns # for page target when page does not exist', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'page',
            'target_id' => 99999,
        ]);

        expect($item->resolveUrl())->toBe('#');
    });

    it('resolves section target type', function () {
        $section = CmsSection::factory()->create(['slug' => 'news']);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'section',
            'target_id' => $section->id,
        ]);

        expect($item->resolveUrl())->toBe('/news');
    });

    it('resolves category target type', function () {
        $section = CmsSection::factory()->create(['slug' => 'blog']);
        $category = CmsCategory::factory()->create(['section_id' => $section->id, 'slug' => 'tutorials']);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'category',
            'target_id' => $category->id,
        ]);

        expect($item->resolveUrl())->toBe('/blog/tutorials');
    });

    it('resolves article target type without category', function () {
        $section = CmsSection::factory()->create(['slug' => 'news']);
        $article = CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => null,
            'slug' => 'breaking-story',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);

        expect($item->resolveUrl())->toBe('/news/breaking-story');
    });

    it('resolves article target type with category', function () {
        $section = CmsSection::factory()->create(['slug' => 'news']);
        $category = CmsCategory::factory()->create(['section_id' => $section->id, 'slug' => 'sports']);
        $article = CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => $category->id,
            'slug' => 'match-day',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'article',
            'target_id' => $article->id,
        ]);

        expect($item->resolveUrl())->toBe('/news/sports/match-day');
    });

    it('resolves album target type', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'summer-2024']);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'album',
            'target_id' => $album->id,
        ]);

        expect($item->resolveUrl())->toBe('/gallery/summer-2024');
    });

    it('resolves album_image target type', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'events']);
        $image = CmsAlbumImage::factory()->create(['album_id' => $album->id]);

        $item = CmsMenuItem::factory()->make([
            'target_type' => 'album_image',
            'target_id' => $image->id,
        ]);

        expect($item->resolveUrl())->toBe('/gallery/events#image-'.$image->id);
    });

    it('returns # for unknown target type', function () {
        $item = CmsMenuItem::factory()->make([
            'target_type' => 'unknown',
            'target_id' => 1,
        ]);

        expect($item->resolveUrl())->toBe('#');
    });
});
