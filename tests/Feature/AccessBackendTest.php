<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

/**
 * @param  array<int, array<int, string>>  $permissions  e.g. [['cms', 'page'], ['cms', 'gallery', 'album']]
 */
function cmsBackendUser(array $permissions): User
{
    $factory = Role::factory();
    foreach ($permissions as $permission) {
        $factory = $factory->has(RolePermission::factory()->forModule(...$permission)->fullCrud());
    }

    $user = User::factory()->create();
    $user->roles()->attach($factory->create(['level' => 5, 'can_access_backend' => true]));

    return $user;
}

function cmsAddGrant(object $item, string $ability, User $user, bool $base = false): void
{
    CmsAccessGrant::create([
        'grantable_type' => $item->getMorphClass(),
        'grantable_id' => $item->getKey(),
        'ability' => $ability,
        'principal_type' => 'user',
        'principal_id' => $user->id,
        'is_base' => $base,
    ]);
}

describe('pages', function () {
    it('forbids editing a page whose managers list excludes the user', function () {
        $page = CmsPage::factory()->create();
        cmsAddGrant($page, 'manage', cmsBackendUser([['cms', 'page']]));

        $this->actingAs(cmsBackendUser([['cms', 'page']]))
            ->get(backend_route('cms.page.form', ['id' => $page->id]))
            ->assertForbidden();
    });

    it('lets a listed manager edit the page and add children', function () {
        $page = CmsPage::factory()->create();
        $manager = cmsBackendUser([['cms', 'page']]);
        cmsAddGrant($page, 'manage', $manager);

        $this->actingAs($manager)->get(backend_route('cms.page.form', ['id' => $page->id]))->assertOk();
        $this->actingAs($manager)->get(backend_route('cms.page.form', ['parentId' => $page->id]))->assertOk();
    });

    it('forbids adding a child under a page the user cannot manage', function () {
        $page = CmsPage::factory()->create();
        cmsAddGrant($page, 'manage', cmsBackendUser([['cms', 'page']]));

        $this->actingAs(cmsBackendUser([['cms', 'page']]))
            ->get(backend_route('cms.page.form', ['parentId' => $page->id]))
            ->assertForbidden();
    });

    it('rejects moving a page under a parent the user cannot manage', function () {
        $locked = CmsPage::factory()->create();
        cmsAddGrant($locked, 'manage', cmsBackendUser([['cms', 'page']]));
        $mine = CmsPage::factory()->create();

        Livewire::actingAs(cmsBackendUser([['cms', 'page']]))
            ->test('cms::backend.page-form', ['id' => $mine->id])
            ->set('form.parent_id', $locked->id)
            ->call('save')
            ->assertForbidden();

        expect($mine->fresh()->parent_id)->toBeNull();
    });

    it('does not delete a page the user cannot manage', function () {
        $page = CmsPage::factory()->create();
        cmsAddGrant($page, 'manage', cmsBackendUser([['cms', 'page']]));

        Livewire::actingAs(cmsBackendUser([['cms', 'page']]))
            ->test('cms::backend.page-list')
            ->call('delete', $page->delete_token);

        expect(CmsPage::find($page->id))->not->toBeNull();
    });

    it('saves visibility and lists for users with cms.access.update', function () {
        $page = CmsPage::factory()->create();
        $viewer = User::factory()->create();

        Livewire::actingAs(cmsBackendUser([['cms', 'page'], ['cms', 'access']]))
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->set('form.visibility', 'private')
            ->set('form.viewPrincipals', ['user:'.$viewer->id])
            ->call('save')
            ->assertHasNoErrors();

        expect($page->fresh()->visibility->value)->toBe('private')
            ->and(CmsAccessGrant::where('principal_id', $viewer->id)->where('is_base', false)->exists())->toBeTrue();
    });

    it('ignores access changes from users without cms.access.update', function () {
        $page = CmsPage::factory()->create();

        Livewire::actingAs(cmsBackendUser([['cms', 'page']]))
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSet('form.canEditAccess', false)
            ->set('form.visibility', 'private')
            ->set('form.viewPrincipals', ['user:1'])
            ->call('save');

        expect($page->fresh()->visibility->value)->toBe('public')
            ->and(CmsAccessGrant::count())->toBe(0);
    });

    it('keeps dev-set base entries locked for non-devs', function () {
        $page = CmsPage::factory()->create();
        $base = User::factory()->create();
        cmsAddGrant($page, 'view', $base, base: true);

        Livewire::actingAs(cmsBackendUser([['cms', 'page'], ['cms', 'access']]))
            ->test('cms::backend.page-form', ['id' => $page->id])
            ->assertSet('form.viewPrincipals', [])
            ->assertSet('form.lockedPrincipals.view.0.value', 'user:'.$base->id)
            ->call('save');

        expect(CmsAccessGrant::where('principal_id', $base->id)->exists())->toBeTrue();
    });
});

describe('sections & articles', function () {
    it('restricts article create and edit to section managers', function () {
        $section = CmsSection::factory()->create();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);
        $manager = cmsBackendUser([['cms', 'article']]);
        cmsAddGrant($section, 'manage', $manager);
        $other = cmsBackendUser([['cms', 'article']]);

        $this->actingAs($other)->get(backend_route('cms.section.article.form', ['sectionId' => $section->id]))->assertForbidden();
        $this->actingAs($other)->get(backend_route('cms.section.article.edit', ['sectionId' => $section->id, 'id' => $article->id]))->assertForbidden();
        $this->actingAs($manager)->get(backend_route('cms.section.article.edit', ['sectionId' => $section->id, 'id' => $article->id]))->assertOk();
    });

    it('404s when editing an article through another section id', function () {
        $mine = CmsSection::factory()->create();
        $theirs = CmsSection::factory()->create();
        $article = CmsArticle::factory()->create(['section_id' => $theirs->id]);

        $this->actingAs(cmsBackendUser([['cms', 'article']]))
            ->get(backend_route('cms.section.article.edit', ['sectionId' => $mine->id, 'id' => $article->id]))
            ->assertNotFound();
    });

    it('restricts section edit to section managers', function () {
        $section = CmsSection::factory()->create();
        cmsAddGrant($section, 'manage', cmsBackendUser([['cms', 'article', 'section']]));

        $this->actingAs(cmsBackendUser([['cms', 'article', 'section']]))
            ->get(backend_route('cms.section.form', ['id' => $section->id]))
            ->assertForbidden();
    });

    it('restricts category management to section managers', function () {
        $section = CmsSection::factory()->create();
        cmsAddGrant($section, 'manage', cmsBackendUser([['cms', 'article', 'category']]));

        $this->actingAs(cmsBackendUser([['cms', 'article', 'category']]))
            ->get(backend_route('cms.category.form', ['sectionId' => $section->id]))
            ->assertForbidden();
    });

    it('shows section viewers on the article form as inherited, read-only entries', function () {
        $section = CmsSection::factory()->create(['visibility' => 'private']);
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);
        $viewer = User::factory()->create();
        cmsAddGrant($section, 'view', $viewer);

        Livewire::actingAs(cmsBackendUser([['cms', 'article'], ['cms', 'access']]))
            ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
            ->assertSet('form.viewPrincipals', [])
            ->assertSet('form.lockedPrincipals.view.0.value', 'user:'.$viewer->id)
            ->assertSet('form.lockedPrincipals.view.0.reason', 'inherited');
    });

    it('saves an article visibility override', function () {
        $section = CmsSection::factory()->create();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);

        Livewire::actingAs(cmsBackendUser([['cms', 'article'], ['cms', 'access']]))
            ->test('cms::backend.article-form', ['sectionId' => $section->id, 'id' => $article->id])
            ->set('form.visibility', 'protected')
            ->call('save')
            ->assertHasNoErrors();

        expect($article->fresh()->visibility->value)->toBe('protected');
    });
});

describe('albums', function () {
    it('restricts album edit and image changes to album managers, but not viewing', function () {
        $album = CmsAlbum::factory()->create();
        $image = $album->images()->create(['file_id' => null, 'sort_order' => 0]);
        cmsAddGrant($album, 'manage', cmsBackendUser([['cms', 'gallery', 'album'], ['cms', 'gallery', 'image']]));
        $other = cmsBackendUser([['cms', 'gallery', 'album'], ['cms', 'gallery', 'image']]);

        $this->actingAs($other)->get(backend_route('cms.album.form', ['id' => $album->id]))->assertForbidden();
        $this->actingAs($other)->get(backend_route('cms.album.images', ['albumId' => $album->id]))->assertOk();

        Livewire::actingAs($other)
            ->test('cms::backend.album-image-manager', ['albumId' => $album->id])
            ->call('deleteImage', $image->delete_token)
            ->assertForbidden();

        expect($image->fresh())->not->toBeNull();
    });
});

describe('downloads', function () {
    it('restricts download edit and file changes to download managers, but not viewing', function () {
        $download = CmsDownload::factory()->create();
        $file = $download->files()->create(['file_id' => null, 'sort_order' => 0]);
        cmsAddGrant($download, 'manage', cmsBackendUser([['cms', 'download', 'category'], ['cms', 'download', 'file']]));
        $other = cmsBackendUser([['cms', 'download', 'category'], ['cms', 'download', 'file']]);

        $this->actingAs($other)->get(backend_route('cms.download.form', ['id' => $download->id]))->assertForbidden();
        $this->actingAs($other)->get(backend_route('cms.download.files', ['downloadId' => $download->id]))->assertOk();

        Livewire::actingAs($other)
            ->test('cms::backend.download-file-manager', ['downloadId' => $download->id])
            ->call('deleteFile', $file->delete_token)
            ->assertForbidden();

        expect($file->fresh())->not->toBeNull();
    });

    it('saves download visibility from the form', function () {
        $download = CmsDownload::factory()->create();

        Livewire::actingAs(cmsBackendUser([['cms', 'download', 'category'], ['cms', 'access']]))
            ->test('cms::backend.download-form', ['id' => $download->id])
            ->set('form.visibility', 'protected')
            ->call('save')
            ->assertHasNoErrors();

        expect($download->fresh()->visibility->value)->toBe('protected');
    });
});
