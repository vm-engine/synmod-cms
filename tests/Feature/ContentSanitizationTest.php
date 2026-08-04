<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->dirty = '<p onclick="alert(1)">Hello <script>alert(2)</script>'
        .'<strong style="color:red">World</strong></p>'
        .'<a href="javascript:alert(3)">bad link</a>'
        .'<a href="https://example.com">good link</a>'
        .'<iframe src="https://evil.example.com"></iframe>';
});

it('sanitizes an article body on create and update', function () {
    $service = app(CmsArticleService::class);
    $section = CmsSection::factory()->create();

    $article = $service->createArticle([
        'section_id' => $section->id,
        'title' => 'XSS Test',
        'slug' => 'xss-test',
        'status' => 'draft',
        'body' => $this->dirty,
    ], [], $this->user->id);

    expect($article->body)
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('style=')
        ->not->toContain('javascript:')
        ->not->toContain('<iframe')
        ->toContain('<strong>World</strong>')
        ->toContain('<a href="https://example.com"');

    $updated = $service->updateArticle($article, ['body' => '<p onerror="x()">Clean <script>bad()</script>update</p>'], [], $this->user->id);

    expect($updated->body)
        ->not->toContain('<script')
        ->not->toContain('onerror')
        ->toContain('Clean');
});

it('sanitizes a section description on create and update', function () {
    $service = app(CmsArticleService::class);

    $section = $service->createSection([
        'title' => 'Section XSS',
        'slug' => 'section-xss',
        'status' => 'draft',
        'description' => $this->dirty,
    ], $this->user->id);

    expect($section->description)
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('style=')
        ->toContain('<strong>World</strong>');

    $updated = $service->updateSection($section, ['description' => '<script>bad()</script>Safe text']);

    expect($updated->description)->not->toContain('<script')->toContain('Safe text');
});

it('sanitizes a category description on create and update', function () {
    $service = app(CmsArticleService::class);
    $section = CmsSection::factory()->create();

    $category = $service->createCategory([
        'section_id' => $section->id,
        'title' => 'Category XSS',
        'slug' => 'category-xss',
        'status' => 'draft',
        'description' => $this->dirty,
    ], $this->user->id);

    expect($category->description)
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('style=');

    $updated = $service->updateCategory($category, ['description' => '<script>bad()</script>Safe category text']);

    expect($updated->description)->not->toContain('<script')->toContain('Safe category text');
});

it('sanitizes a page body on create and update', function () {
    $service = app(CmsPageService::class);

    $page = $service->create([
        'title' => 'Page XSS',
        'slug' => 'page-xss',
        'status' => 'draft',
        'body' => $this->dirty,
    ], $this->user->id);

    expect($page->body)
        ->not->toContain('<script')
        ->not->toContain('onclick')
        ->not->toContain('style=')
        ->toContain('<strong>World</strong>');

    $updated = $service->update($page, ['slug' => $page->slug, 'body' => '<script>bad()</script>Safe page text']);

    expect($updated->body)->not->toContain('<script')->toContain('Safe page text');
});

it('backfills existing dirty content via cms:sanitize-content --dry-run without saving', function () {
    $section = CmsSection::factory()->create(['description' => '<script>alert(1)</script>Dirty section']);

    $this->artisan('cms:sanitize-content', ['--dry-run' => true])
        ->assertExitCode(0);

    $section->refresh();
    expect($section->description)->toContain('<script');
});

it('backfills existing dirty content via cms:sanitize-content and saves changes', function () {
    $section = CmsSection::factory()->create(['description' => '<script>alert(1)</script>Dirty section']);

    $this->artisan('cms:sanitize-content')
        ->assertExitCode(0);

    $section->refresh();
    expect($section->description)
        ->not->toContain('<script')
        ->toContain('Dirty section');
});
