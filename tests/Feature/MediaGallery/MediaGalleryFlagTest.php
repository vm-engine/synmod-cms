<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

it('lets a dev enable media galleries on a root page, section and download', function (string $component, Closure $make) {
    $container = $make();

    Livewire::actingAs(mgDev())
        ->test($component, ['id' => $container->id])
        ->assertSee(__('cms::labels.media_gallery_enable'))
        ->set('form.mediaGallery', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($container->fresh()->config['media_gallery'])->toBeTrue();
})->with([
    'root page' => ['cms::backend.page-form', fn () => CmsPage::factory()->create()],
    'section' => ['cms::backend.section-form', fn () => CmsSection::factory()->create()],
    'download' => ['cms::backend.download-form', fn () => CmsDownload::factory()->create()],
]);

it('ignores a tampered flag from a non-dev and keeps the stored value', function () {
    $section = CmsSection::factory()->create(['config' => ['media_gallery' => true]]);

    Livewire::actingAs(mgAdmin())
        ->test('cms::backend.section-form', ['id' => $section->id])
        ->assertDontSee(__('cms::labels.media_gallery_enable'))
        ->set('form.mediaGallery', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($section->fresh()->config['media_gallery'])->toBeTrue();
});

it('does not offer the toggle on a child page', function () {
    $root = CmsPage::factory()->create();
    $child = CmsPage::factory()->create(['parent_id' => $root->id]);

    Livewire::actingAs(mgDev())
        ->test('cms::backend.page-form', ['id' => $child->id])
        ->assertDontSee(__('cms::labels.media_gallery_enable'));
});
