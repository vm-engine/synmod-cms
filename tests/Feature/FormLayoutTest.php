<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $role = Role::factory();
    foreach ([['page'], ['article'], ['article', 'section'], ['article', 'category'], ['download', 'category'], ['gallery', 'album']] as $feature) {
        $role = $role->has(RolePermission::factory()->forModule('cms', ...$feature)->fullCrud());
    }
    $this->adminRole = $role->create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);
});

it('renders every long CMS form inside the shared form layout', function (Closure $make, string $component, Closure $params) {
    Livewire::actingAs($this->user)
        ->test($component, $params($make()))
        ->assertSeeHtml('x-data="cmsForm(')
        ->assertSeeHtml('@submit.prevent="submit()"')
        ->assertSeeHtml('data-cms-form-sidebar')
        ->assertSeeHtml('syn-form-action-bar');
})->with([
    'page' => [fn () => CmsPage::factory()->create(), 'cms::backend.page-form', fn ($m) => ['id' => $m->id]],
    'article' => [fn () => CmsArticle::factory()->create(), 'cms::backend.article-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id]],
    'section' => [fn () => CmsSection::factory()->create(), 'cms::backend.section-form', fn ($m) => ['id' => $m->id]],
    'category' => [fn () => CmsCategory::factory()->create(), 'cms::backend.category-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id]],
    'download' => [fn () => CmsDownload::factory()->create(), 'cms::backend.download-form', fn ($m) => ['id' => $m->id]],
    'album' => [fn () => CmsAlbum::factory()->create(), 'cms::backend.album-form', fn ($m) => ['id' => $m->id]],
]);

it('hides the save button when the user lacks the permission', function () {
    $this->actingAs(User::factory()->create());

    $html = Blade::render('<x-cms::form-layout back="/back" can="cms.page.update">Body<x-slot:sidebar>Side</x-slot:sidebar></x-cms::form-layout>');

    expect($html)->toContain('Body')->toContain('Side')->toContain('href="/back"')
        ->not->toContain('type="submit"');
});

it('keeps a scheduled page publish date across re-renders', function () {
    $page = CmsPage::factory()->create(['status' => 'scheduled', 'published_at' => '2030-01-02 03:04:00']);

    Livewire::actingAs($this->user)
        ->test('cms::backend.page-form', ['id' => $page->id])
        ->set('form.sort_order', 3)
        ->assertSet('form.published_at', '2030-01-02 03:04');
});

it('defaults the publish date when a page is switched to scheduled', function () {
    $page = CmsPage::factory()->create(['status' => 'draft', 'published_at' => null]);

    Livewire::actingAs($this->user)
        ->test('cms::backend.page-form', ['id' => $page->id])
        ->set('form.status', 'scheduled')
        ->assertSet('form.published_at', now()->format('Y-m-d H:i'));
});

it('summarises validation errors at the top of the form, including fields without inline errors', function () {
    $page = CmsPage::factory()->create();

    Livewire::actingAs($this->user)
        ->test('cms::backend.page-form', ['id' => $page->id])
        ->assertDontSeeHtml('data-cms-error-summary')
        ->set('form.meta_title', str_repeat('a', 300))
        ->call('save')
        ->assertHasErrors('form.meta_title')
        ->assertSeeHtml('data-cms-error-summary')
        ->assertSee(__('cms::labels.fix_errors'));
});

it('shows inline errors for fields inside the collapsed SEO and custom rendering panels', function (Closure $make, string $component, Closure $params) {
    Livewire::actingAs($this->user)
        ->test($component, $params($make()))
        ->set('form.meta_title', str_repeat('a', 300))
        ->set('form.custom_view', str_repeat('a', 300))
        ->call('save')
        ->assertHasErrors(['form.meta_title', 'form.custom_view'])
        ->assertSeeHtml('<p class="form-error">'.__('validation.max.string', ['attribute' => 'meta title', 'max' => 255]).'</p>')
        ->assertSeeHtml('<p class="form-error">'.__('validation.max.string', ['attribute' => 'custom view', 'max' => 255]).'</p>');
})->with([
    'page' => [fn () => CmsPage::factory()->create(), 'cms::backend.page-form', fn ($m) => ['id' => $m->id]],
    'article' => [fn () => CmsArticle::factory()->create(), 'cms::backend.article-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id]],
    'section' => [fn () => CmsSection::factory()->create(), 'cms::backend.section-form', fn ($m) => ['id' => $m->id]],
    'category' => [fn () => CmsCategory::factory()->create(), 'cms::backend.category-form', fn ($m) => ['sectionId' => $m->section_id, 'id' => $m->id]],
    'download' => [fn () => CmsDownload::factory()->create(), 'cms::backend.download-form', fn ($m) => ['id' => $m->id]],
    'album' => [fn () => CmsAlbum::factory()->create(), 'cms::backend.album-form', fn ($m) => ['id' => $m->id]],
]);
