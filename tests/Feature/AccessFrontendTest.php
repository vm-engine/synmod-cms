<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsMenuService;
use VmEngine\Cms\Services\CmsSitemapService;
use VmEngine\Cms\Support\CmsUrl;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

function frontendViewGrant(object $item, User $user): void
{
    CmsAccessGrant::create([
        'grantable_type' => $item->getMorphClass(),
        'grantable_id' => $item->getKey(),
        'ability' => 'view',
        'principal_type' => 'user',
        'principal_id' => $user->id,
    ]);
}

dataset('gated content', [
    'page' => fn () => CmsPage::factory()->create(['status' => 'published', 'visibility' => 'private']),
    'section' => fn () => CmsSection::factory()->create(['status' => 'published', 'visibility' => 'private']),
    'article' => fn () => CmsArticle::factory()->create([
        'status' => 'published', 'published_at' => now()->subDay(), 'visibility' => 'private',
    ]),
    'album' => fn () => CmsAlbum::factory()->create(['visibility' => 'private']),
    'download' => fn () => CmsDownload::factory()->create(['visibility' => 'private']),
]);

describe('single items', function () {
    it('redirects guests to login', function ($item) {
        $this->get(CmsUrl::to($item))->assertRedirect(route('auth.login'));
    })->with('gated content');

    it('returns 404 for logged-in users not on the list', function ($item) {
        $this->actingAs(User::factory()->create())->get(CmsUrl::to($item))->assertNotFound();
    })->with('gated content');

    it('renders for listed users', function ($item) {
        $user = User::factory()->create();
        frontendViewGrant($item, $user);

        $this->actingAs($user)->get(CmsUrl::to($item))->assertOk();
    })->with('gated content');

    it('renders protected content for any logged-in user only', function () {
        $page = CmsPage::factory()->create(['status' => 'published', 'visibility' => 'protected']);

        $this->get(CmsUrl::to($page))->assertRedirect(route('auth.login'));
        $this->actingAs(User::factory()->create())->get(CmsUrl::to($page))->assertOk();
    });

    it('gates the download file stream route', function () {
        $download = CmsDownload::factory()->protected()->create();
        $file = $download->files()->create(['file_id' => null, 'sort_order' => 0]);

        $this->get(route('cms.download.stream', $file->id))->assertRedirect(route('auth.login'));
    });
});

describe('listings', function () {
    it('hides private articles from guests in a section listing', function () {
        $section = CmsSection::factory()->create(['status' => 'published']);
        CmsArticle::factory()->create(['section_id' => $section->id, 'status' => 'published', 'published_at' => now()->subDay(), 'title' => 'Open Post']);
        CmsArticle::factory()->create(['section_id' => $section->id, 'status' => 'published', 'published_at' => now()->subDay(), 'title' => 'Secret Post', 'visibility' => 'private']);

        Livewire::test('cms::frontend.section-index', ['section_id' => $section->id])
            ->assertSee('Open Post')
            ->assertDontSee('Secret Post');
    });

    it('hides protected albums from guests in the gallery index', function () {
        CmsAlbum::factory()->create(['title' => 'Open Album']);
        CmsAlbum::factory()->create(['title' => 'Members Album', 'visibility' => 'protected']);

        Livewire::test('cms::frontend.gallery-index')
            ->assertSee('Open Album')
            ->assertDontSee('Members Album');
    });

    it('shows protected downloads only to logged-in users', function () {
        CmsDownload::factory()->protected()->create(['title' => 'Members File']);

        Livewire::test('cms::frontend.download-index')->assertDontSee('Members File');
        Livewire::actingAs(User::factory()->create())->test('cms::frontend.download-index')->assertSee('Members File');
    });

    it('drops menu items that point at hidden content', function () {
        $menu = CmsMenu::factory()->create(['position' => 'header']);
        $hidden = CmsPage::factory()->create(['status' => 'published', 'visibility' => 'private']);
        $menu->items()->create(['label' => 'Hidden Link', 'target_type' => 'page', 'target_id' => $hidden->id, 'is_active' => true, 'sort_order' => 0]);
        $menu->items()->create(['label' => 'Docs', 'target_type' => 'url', 'target_url' => 'https://example.com', 'is_active' => true, 'sort_order' => 1]);

        expect(app(CmsMenuService::class)->buildTree('header')->pluck('label')->all())->toBe(['Docs']);
    });

    it('applies visitor, inheritance and nesting rules to menu items', function () {
        $menu = CmsMenu::factory()->create(['position' => 'header']);
        $members = CmsPage::factory()->create(['status' => 'published', 'visibility' => 'protected']);
        $privateSection = CmsSection::factory()->create(['status' => 'published', 'visibility' => 'private']);
        $inheritingArticle = CmsArticle::factory()->create(['section_id' => $privateSection->id, 'status' => 'published', 'published_at' => now()->subDay()]);
        $parent = $menu->items()->create(['label' => 'Parent', 'target_type' => 'url', 'target_url' => '/about', 'is_active' => true, 'sort_order' => 0]);
        $menu->items()->create(['label' => 'Members', 'target_type' => 'page', 'target_id' => $members->id, 'parent_id' => $parent->id, 'is_active' => true, 'sort_order' => 0]);
        $menu->items()->create(['label' => 'Secret Article', 'target_type' => 'article', 'target_id' => $inheritingArticle->id, 'is_active' => true, 'sort_order' => 1]);

        $labels = fn () => app(CmsMenuService::class)->buildTree('header')
            ->flatMap(fn ($item) => [$item->label, ...$item->children->pluck('label')])
            ->all();

        expect($labels())->toBe(['Parent']);

        $this->actingAs(User::factory()->create());
        app()->forgetScopedInstances();
        expect($labels())->toBe(['Parent', 'Members']);

        $listed = User::factory()->create();
        frontendViewGrant($privateSection, $listed);
        $this->actingAs($listed);
        app()->forgetScopedInstances();
        expect($labels())->toBe(['Parent', 'Members', 'Secret Article']);
    });
});

describe('search + sitemap', function () {
    it('drops a page from the search index when it stops being public', function () {
        $page = CmsPage::factory()->create(['status' => 'published']);
        expect(CmsSearchable::where('searchable_type', 'page')->where('searchable_id', $page->id)->count())->toBeGreaterThan(0);

        $page->update(['visibility' => 'protected']);

        expect(CmsSearchable::where('searchable_type', 'page')->where('searchable_id', $page->id)->count())->toBe(0);
    });

    it('re-evaluates inheriting child pages when the parent turns private', function () {
        $parent = CmsPage::factory()->create(['status' => 'published']);
        $child = CmsPage::factory()->create(['status' => 'published', 'parent_id' => $parent->id, 'visibility' => 'inherit']);

        $parent->update(['visibility' => 'private']);

        expect(CmsSearchable::where('searchable_type', 'page')->where('searchable_id', $child->id)->count())->toBe(0);
    });

    it('re-evaluates inheriting articles when the section turns protected', function () {
        $section = CmsSection::factory()->create(['status' => 'published']);
        $article = CmsArticle::factory()->create(['section_id' => $section->id, 'status' => 'published', 'published_at' => now()->subDay()]);

        $section->update(['visibility' => 'protected']);

        expect(CmsSearchable::where('searchable_type', 'article')->where('searchable_id', $article->id)->count())->toBe(0);
    });

    it('omits non-public content from the sitemap', function () {
        CmsPage::factory()->create(['status' => 'published', 'slug' => 'open-page', 'full_slug' => 'open-page']);
        CmsPage::factory()->create(['status' => 'published', 'slug' => 'members-page', 'full_slug' => 'members-page', 'visibility' => 'protected']);

        expect(app(CmsSitemapService::class)->generate())
            ->toContain('open-page')
            ->not->toContain('members-page');
    });
});
