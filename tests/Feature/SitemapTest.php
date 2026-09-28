<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Enums\ArticleStatus;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsSitemapService;

uses(RefreshDatabase::class);

describe('CmsSitemapService', function () {
    it('generates valid XML with urlset root', function () {
        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)
            ->toContain('<?xml version="1.0" encoding="UTF-8"?>')
            ->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">')
            ->toContain('</urlset>');
    });

    it('includes published pages', function () {
        CmsPage::factory()->create([
            'slug' => 'about',
            'full_slug' => 'about',
            'status' => 'published',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->toContain('/about');
    });

    it('excludes draft pages', function () {
        CmsPage::factory()->draft()->create([
            'slug' => 'hidden-page',
            'full_slug' => 'hidden-page',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->not->toContain('/hidden-page');
    });

    it('gives nested pages lower priority than root pages', function () {
        $parent = CmsPage::factory()->create(['slug' => 'parent', 'full_slug' => 'parent', 'status' => 'published']);
        CmsPage::factory()->create([
            'parent_id' => $parent->id,
            'slug' => 'child',
            'full_slug' => 'parent/child',
            'status' => 'published',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        // Root page gets 0.8, child gets 0.6
        expect($xml)
            ->toContain('<priority>0.8</priority>')
            ->toContain('<priority>0.6</priority>');
    });

    it('includes published sections', function () {
        CmsSection::factory()->create(['slug' => 'tech-news', 'status' => 'published']);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->toContain('/tech-news');
    });

    it('excludes draft sections', function () {
        CmsSection::factory()->draft()->create(['slug' => 'draft-section']);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->not->toContain('/draft-section');
    });

    it('includes published categories whose section is also published', function () {
        $section = CmsSection::factory()->create(['slug' => 'blog', 'status' => 'published']);
        CmsCategory::factory()->create([
            'section_id' => $section->id,
            'slug' => 'tutorials',
            'status' => 'published',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->toContain('/blog/tutorials');
    });

    it('excludes categories whose section is draft', function () {
        $section = CmsSection::factory()->draft()->create(['slug' => 'hidden']);
        CmsCategory::factory()->create([
            'section_id' => $section->id,
            'slug' => 'cat',
            'status' => 'published',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->not->toContain('/hidden/cat');
    });

    it('includes published articles with section path', function () {
        $section = CmsSection::factory()->create(['slug' => 'news', 'status' => 'published']);
        CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => null,
            'slug' => 'breaking',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->toContain('/news/breaking');
    });

    it('includes published articles with section/category path', function () {
        $section = CmsSection::factory()->create(['slug' => 'news', 'status' => 'published']);
        $category = CmsCategory::factory()->create([
            'section_id' => $section->id,
            'slug' => 'sports',
            'status' => 'published',
        ]);
        CmsArticle::factory()->create([
            'section_id' => $section->id,
            'category_id' => $category->id,
            'slug' => 'match-report',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->toContain('/news/sports/match-report');
    });

    it('gives featured articles higher priority than normal articles', function () {
        $section = CmsSection::factory()->create(['slug' => 'news', 'status' => 'published']);
        CmsArticle::factory()->create([
            'section_id' => $section->id,
            'slug' => 'featured-post',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
            'is_featured' => true,
        ]);
        CmsArticle::factory()->create([
            'section_id' => $section->id,
            'slug' => 'normal-post',
            'status' => ArticleStatus::Published,
            'published_at' => now()->subDay(),
            'is_featured' => false,
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)
            ->toContain('<priority>0.9</priority>')
            ->toContain('<priority>0.7</priority>');
    });

    it('includes active normal albums', function () {
        CmsAlbum::factory()->create([
            'slug' => 'summer-pics',
            'type' => AlbumType::Normal,
            'status' => 'active',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->toContain('/gallery/summer-pics');
    });

    it('excludes banner albums from sitemap', function () {
        CmsAlbum::factory()->create([
            'slug' => 'homepage-banner',
            'type' => AlbumType::Banner,
            'status' => 'active',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->not->toContain('/gallery/homepage-banner');
    });

    it('excludes inactive albums from sitemap', function () {
        CmsAlbum::factory()->create([
            'slug' => 'old-album',
            'type' => AlbumType::Normal,
            'status' => 'inactive',
        ]);

        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)->not->toContain('/gallery/old-album');
    });

    it('generates empty urlset when no content exists', function () {
        $xml = app(CmsSitemapService::class)->generate();

        expect($xml)
            ->toContain('<urlset')
            ->not->toContain('<url>');
    });
});

describe('Sitemap Route', function () {
    it('returns 200 and xml content-type for /sitemap.xml', function () {
        $this->get('/sitemap.xml')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');
    });
});
