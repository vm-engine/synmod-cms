<?php

declare(strict_types=1);

use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Cms\Support\CmsUrl;

beforeEach(function () {
    $this->service = app(CmsBuilderPageService::class);
    $this->page = $this->service->create(['title' => 'Landing', 'slug' => 'landing', 'template' => 'full']);
});

it('registers the fallback route only once published', function () {
    $this->assertDatabaseMissing('fallback_routes', ['slug' => 'landing']);

    $this->service->publish($this->page);

    $this->assertDatabaseHas('fallback_routes', ['slug' => 'landing', 'module' => 'cms', 'handler' => 'cms::frontend.builder-page-viewer']);
});

it('moves the route when the slug changes and drops it on unpublish and delete', function () {
    $this->service->publish($this->page);
    $this->service->update($this->page, ['slug' => 'landing-2']);

    $this->assertDatabaseMissing('fallback_routes', ['slug' => 'landing']);
    $this->assertDatabaseHas('fallback_routes', ['slug' => 'landing-2']);

    $this->service->unpublish($this->page);
    $this->assertDatabaseMissing('fallback_routes', ['slug' => 'landing-2']);

    $this->service->publish($this->page);
    $this->service->deleteByToken($this->page->delete_token);
    $this->assertDatabaseMissing('fallback_routes', ['slug' => 'landing-2']);
});

it('renders the published layout, not the draft, with region and widget classes', function () {
    $section = CmsSection::factory()->create(['title' => 'News', 'slug' => 'news', 'status' => 'published']);
    CmsArticle::factory()->create(['section_id' => $section->id]);
    $id = $this->service->addWidget($this->page, 'main', 'section-articles');
    $this->service->updateWidget($this->page, $id, ['section' => 'news'], ['shadow-md']);
    $this->service->updateRegionClasses($this->page, 'main', ['py-12']);
    $this->service->publish($this->page);
    $this->service->addWidget($this->page, 'hero', 'gallery');

    $this->get(CmsUrl::to($this->page->fresh()))
        ->assertOk()
        ->assertSee('cms-builder-region--main', false)
        ->assertSee('py-12', false)
        ->assertSee('shadow-md', false)
        ->assertDontSee('cms-builder-region--hero', false)
        ->assertDontSee('wire:sort', false)
        // A full width region overflows by the scrollbar's width; the page clips that.
        ->assertSee('cms-builder-page [html:has(&)]:overflow-x-clip [body:has(&)]:overflow-x-clip', false);
});

it('404s for a draft page', function () {
    $this->get(CmsUrl::to($this->page->fresh()))->assertNotFound();
});
