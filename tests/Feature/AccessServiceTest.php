<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

function accessGrant(object $item, string $ability, string $type, int $id, bool $base = false): void
{
    CmsAccessGrant::create([
        'grantable_type' => $item->getMorphClass(),
        'grantable_id' => $item->getKey(),
        'ability' => $ability,
        'principal_type' => $type,
        'principal_id' => $id,
        'is_base' => $base,
    ]);
}

function accessEditor(string $module, string $feature, ?string $sub = null): User
{
    $role = Role::factory()
        ->has(RolePermission::factory()->forModule($module, $feature, $sub)->fullCrud())
        ->create(['level' => 5, 'can_access_backend' => true]);
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}

function accessDev(): User
{
    $dev = User::factory()->create();
    $dev->roles()->attach(Role::factory()->create(['slug' => 'dev']));

    return $dev;
}

/** Fresh request-scoped service (drops memoized grants / page tree). */
function accessService(): CmsAccessService
{
    app()->forgetScopedInstances();

    return app(CmsAccessService::class);
}

describe('visibility matrix', function () {
    it('shows public content to guests and users', function () {
        $page = CmsPage::factory()->create(['visibility' => 'public']);

        expect(accessService()->canView($page, null))->toBeTrue()
            ->and(accessService()->canView($page, User::factory()->create()))->toBeTrue();
    });

    it('requires login for protected content', function () {
        $page = CmsPage::factory()->create(['visibility' => 'protected']);

        expect(accessService()->canView($page, null))->toBeFalse()
            ->and(accessService()->canView($page, User::factory()->create()))->toBeTrue();
    });

    it('requires a listed user or role for private content', function () {
        $page = CmsPage::factory()->create(['visibility' => 'private']);
        $listed = User::factory()->create();
        $role = Role::factory()->create();
        $viaRole = User::factory()->create();
        $viaRole->roles()->attach($role);
        accessGrant($page, 'view', 'user', $listed->id);
        accessGrant($page, 'view', 'role', $role->id);

        expect(accessService()->canView($page, null))->toBeFalse()
            ->and(accessService()->canView($page, User::factory()->create()))->toBeFalse()
            ->and(accessService()->canView($page, $listed))->toBeTrue()
            ->and(accessService()->canView($page, $viaRole))->toBeTrue();
    });

    it('lets devs see everything', function () {
        $page = CmsPage::factory()->create(['visibility' => 'private']);

        expect(accessService()->canView($page, accessDev()))->toBeTrue();
    });

    it('lets managers see private content they manage', function () {
        $page = CmsPage::factory()->create(['visibility' => 'private']);
        $editor = accessEditor('cms', 'page');
        accessGrant($page, 'manage', 'user', $editor->id);

        expect(accessService()->canView($page, $editor))->toBeTrue()
            ->and(accessService()->canView($page, accessEditor('cms', 'page')))->toBeFalse();
    });
});

describe('inheritance', function () {
    it('child page inherits parent visibility and a cumulative view list', function () {
        $parent = CmsPage::factory()->create(['visibility' => 'private']);
        $child = CmsPage::factory()->create(['parent_id' => $parent->id, 'visibility' => 'inherit']);
        $parentViewer = User::factory()->create();
        $childViewer = User::factory()->create();
        accessGrant($parent, 'view', 'user', $parentViewer->id);
        accessGrant($child, 'view', 'user', $childViewer->id);

        expect(accessService()->effectiveVisibility($child))->toBe(CmsVisibility::Private)
            ->and(accessService()->canView($child, $parentViewer))->toBeTrue()
            ->and(accessService()->canView($child, $childViewer))->toBeTrue()
            ->and(accessService()->canView($parent, $childViewer))->toBeFalse();
    });

    it('lets a child page override to public', function () {
        $parent = CmsPage::factory()->create(['visibility' => 'private']);
        $child = CmsPage::factory()->create(['parent_id' => $parent->id, 'visibility' => 'public']);

        expect(accessService()->canView($child, null))->toBeTrue();
    });

    it('article inherits its section unless overridden', function () {
        $section = CmsSection::factory()->create(['visibility' => 'protected']);
        $inherit = CmsArticle::factory()->create(['section_id' => $section->id, 'visibility' => 'inherit']);
        $public = CmsArticle::factory()->create(['section_id' => $section->id, 'visibility' => 'public']);

        expect(accessService()->canView($inherit, null))->toBeFalse()
            ->and(accessService()->canView($public, null))->toBeTrue()
            ->and(accessService()->isPublic($inherit))->toBeFalse()
            ->and(accessService()->isPublic($public))->toBeTrue();
    });
});

describe('manage', function () {
    it('allows anyone with the ACL when the managers list is empty', function () {
        $page = CmsPage::factory()->create();

        expect(accessService()->canManage($page, 'cms.page.update', accessEditor('cms', 'page')))->toBeTrue()
            ->and(accessService()->canManage($page, 'cms.page.update', User::factory()->create()))->toBeFalse();
    });

    it('restricts ACL holders to listed principals when the list is set', function () {
        $page = CmsPage::factory()->create();
        $listed = accessEditor('cms', 'page');
        accessGrant($page, 'manage', 'user', $listed->id);

        expect(accessService()->canManage($page, 'cms.page.update', $listed))->toBeTrue()
            ->and(accessService()->canManage($page, 'cms.page.update', accessEditor('cms', 'page')))->toBeFalse();
    });

    it('does not let a listing replace the ACL', function () {
        $page = CmsPage::factory()->create();
        $user = User::factory()->create();
        accessGrant($page, 'manage', 'user', $user->id);

        expect(accessService()->canManage($page, 'cms.page.update', $user))->toBeFalse();
    });

    it('accumulates page managers down the tree', function () {
        $root = CmsPage::factory()->create();
        $child = CmsPage::factory()->create(['parent_id' => $root->id]);
        $rootManager = accessEditor('cms', 'page');
        $childManager = accessEditor('cms', 'page');
        accessGrant($root, 'manage', 'user', $rootManager->id);
        accessGrant($child, 'manage', 'user', $childManager->id);

        expect(accessService()->canManage($child, 'cms.page.update', $rootManager))->toBeTrue()
            ->and(accessService()->canManage($child, 'cms.page.update', $childManager))->toBeTrue()
            ->and(accessService()->canManage($root, 'cms.page.update', $childManager))->toBeFalse();
    });

    it('uses the section list for article management', function () {
        $section = CmsSection::factory()->create();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);
        $manager = accessEditor('cms', 'article');
        accessGrant($section, 'manage', 'user', $manager->id);

        expect(accessService()->canManage($article, 'cms.article.update', $manager))->toBeTrue()
            ->and(accessService()->canManage($article, 'cms.article.update', accessEditor('cms', 'article')))->toBeFalse();
    });
});

describe('visibleTo scope', function () {
    it('filters flat types for guests, users and listed users', function () {
        CmsAlbum::factory()->create(['visibility' => 'public', 'slug' => 'a-public']);
        CmsAlbum::factory()->create(['visibility' => 'protected', 'slug' => 'a-protected']);
        $private = CmsAlbum::factory()->create(['visibility' => 'private', 'slug' => 'a-private']);
        $listed = User::factory()->create();
        accessGrant($private, 'view', 'user', $listed->id);

        accessService();
        expect(CmsAlbum::visibleTo(null)->pluck('slug')->all())->toBe(['a-public'])
            ->and(CmsAlbum::visibleTo(User::factory()->create())->orderBy('id')->pluck('slug')->all())->toBe(['a-public', 'a-protected'])
            ->and(CmsAlbum::visibleTo($listed)->count())->toBe(3);
    });

    it('resolves article inherit against the section in SQL', function () {
        $privateSection = CmsSection::factory()->create(['visibility' => 'private']);
        $sectionViewer = User::factory()->create();
        accessGrant($privateSection, 'view', 'user', $sectionViewer->id);
        CmsArticle::factory()->create(['section_id' => $privateSection->id, 'visibility' => 'inherit', 'slug' => 'hidden']);
        CmsArticle::factory()->create(['section_id' => $privateSection->id, 'visibility' => 'public', 'slug' => 'shown']);

        accessService();
        expect(CmsArticle::visibleTo(null)->pluck('slug')->all())->toBe(['shown'])
            ->and(CmsArticle::visibleTo($sectionViewer)->count())->toBe(2);
    });

    it('includes items the user manages', function () {
        CmsDownload::factory()->create(['visibility' => 'private']);

        accessService();
        expect(CmsDownload::visibleTo(accessEditor('cms', 'download', 'category'))->count())->toBe(1)
            ->and(CmsDownload::visibleTo(User::factory()->create())->count())->toBe(0);
    });

    it('includes albums the user manages, per album type ability', function () {
        CmsAlbum::factory()->create(['visibility' => 'private']);
        CmsAlbum::factory()->banner()->create(['visibility' => 'private']);

        accessService();
        expect(CmsAlbum::visibleTo(accessEditor('cms', 'gallery', 'album'))->count())->toBe(1)
            ->and(CmsAlbum::visibleTo(User::factory()->create())->count())->toBe(0);
    });
});

describe('syncGrants base locking', function () {
    it('makes dev entries base so non-dev edits keep them', function () {
        $page = CmsPage::factory()->create();
        $a = User::factory()->create();
        $b = User::factory()->create();

        accessService()->syncGrants($page, 'view', ['user:'.$a->id], accessDev());
        accessService()->syncGrants($page, 'view', ['user:'.$b->id], accessEditor('cms', 'page'));

        $rows = CmsAccessGrant::where('grantable_id', $page->id)->get()->keyBy('principal_id');
        expect($rows)->toHaveCount(2)
            ->and($rows[$a->id]->is_base)->toBeTrue()
            ->and($rows[$b->id]->is_base)->toBeFalse();
    });

    it('lets non-devs remove only non-base entries while devs remove anything', function () {
        $page = CmsPage::factory()->create();
        $dev = accessDev();
        $editor = accessEditor('cms', 'page');

        accessService()->syncGrants($page, 'view', ['user:'.User::factory()->create()->id], $dev);
        accessService()->syncGrants($page, 'view', ['user:'.User::factory()->create()->id], $editor);
        accessService()->syncGrants($page, 'view', [], $editor);
        expect(CmsAccessGrant::count())->toBe(1);

        accessService()->syncGrants($page, 'view', [], $dev);
        expect(CmsAccessGrant::count())->toBe(0);
    });

    it('ignores malformed principal keys', function () {
        $page = CmsPage::factory()->create();

        accessService()->syncGrants($page, 'view', ['user:1; drop', 'admin', 'role:x'], accessEditor('cms', 'page'));

        expect(CmsAccessGrant::count())->toBe(0);
    });

    it('removes grants when content is deleted', function () {
        $page = CmsPage::factory()->create();
        accessGrant($page, 'view', 'user', 1);

        $page->delete();

        expect(CmsAccessGrant::count())->toBe(0);
    });

    it('prunes article grants when their section is deleted', function () {
        $section = CmsSection::factory()->create();
        $article = CmsArticle::factory()->create(['section_id' => $section->id]);
        accessGrant($article, 'view', 'user', 1);

        $section->delete();

        expect(CmsAccessGrant::count())->toBe(0);
    });
});
