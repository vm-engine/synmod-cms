<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\SynAuth\Models\Role;

/**
 * Frontend visibility + per-item manager resolution for CMS content.
 *
 * Request-scoped: grants and the page tree are memoized for the request, so
 * list/menu rendering costs a handful of queries regardless of row count.
 * ponytail: whole grant set per content class is loaded at once — fine while
 * cms_access_grants stays small; switch to per-id loading if it grows large.
 */
class CmsAccessService
{
    /** @var array<string, array<array-key, EloquentCollection<int, CmsAccessGrant>>> morph class => "id|ability" => grants */
    private array $grants = [];

    /** @var Collection<int, CmsPage>|null id => page (id, parent_id, visibility) */
    private ?Collection $pageTree = null;

    /** @var array<int, list<string>> user id => principal keys */
    private array $principalKeys = [];

    // ------------------------------------------------------------- View

    public function canView(Model $item, ?Authenticatable $user = null): bool
    {
        if ($user !== null && $this->isDev($user)) {
            return true;
        }

        $visibility = $this->effectiveVisibility($item);

        if ($visibility === CmsVisibility::Public) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($visibility === CmsVisibility::Protected) {
            return true;
        }

        return $this->isListed($this->viewChain($item), CmsAccessGrant::VIEW, $user)
            || $this->canManage($item, $this->abilityFor($item, 'update'), $user);
    }

    /**
     * Frontend gate for a single item: guests go to login, others get a 404.
     */
    public function authorizeView(Model $item): void
    {
        $user = auth()->user();

        if ($this->canView($item, $user)) {
            return;
        }

        if ($user === null) {
            throw new HttpResponseException(redirect()->guest(route('auth.login')));
        }

        abort(404);
    }

    /** Effectively public = safe for the shared search index and sitemap. */
    public function isPublic(Model $item): bool
    {
        return $this->effectiveVisibility($item) === CmsVisibility::Public;
    }

    public function effectiveVisibility(Model $item): CmsVisibility
    {
        foreach ($this->viewChain($item) as $node) {
            $visibility = $node->getAttribute('visibility');

            if ($visibility instanceof CmsVisibility && $visibility !== CmsVisibility::Inherit) {
                return $visibility;
            }
        }

        return CmsVisibility::Public;
    }

    /**
     * Filter loaded pages by canView() (pages inherit, so there is no SQL scope).
     *
     * @param  EloquentCollection<int, CmsPage>  $pages
     * @return EloquentCollection<int, CmsPage>
     */
    public function filterVisiblePages(EloquentCollection $pages, ?Authenticatable $user = null): EloquentCollection
    {
        $user ??= auth()->user();

        return $pages->filter(fn (CmsPage $page) => $this->canView($page, $user))->values();
    }

    /**
     * SQL equivalent of canView() for flat types (section, article, album, download).
     *
     * @param  Builder<covariant Model>  $query
     */
    public function applyVisibleScope(Builder $query, ?Authenticatable $user): void
    {
        if ($user !== null && $this->isDev($user)) {
            return;
        }

        $model = $query->getModel();
        $table = $model->getTable();
        $allowed = $user === null ? ['public'] : ['public', 'protected'];

        $query->where(function (Builder $w) use ($model, $table, $allowed, $user): void {
            if ($model instanceof CmsArticle) {
                $w->whereIn("{$table}.visibility", $allowed)
                    ->orWhere(fn (Builder $i) => $i->where("{$table}.visibility", CmsVisibility::Inherit->value)
                        ->whereIn("{$table}.section_id", CmsSection::query()->select('id')->whereIn('visibility', $allowed)));

                if ($user !== null) {
                    $w->orWhereIn("{$table}.id", $this->grantedIds(CmsArticle::class, CmsAccessGrant::VIEW, $user))
                        ->orWhereIn("{$table}.section_id", $this->grantedIds(CmsSection::class, CmsAccessGrant::VIEW, $user));
                    $this->orManageable($w, CmsSection::class, "{$table}.section_id", 'cms.article.update', $user);
                }

                return;
            }

            $w->whereIn("{$table}.visibility", $allowed);

            if ($user === null) {
                return;
            }

            $w->orWhereIn("{$table}.id", $this->grantedIds($model::class, CmsAccessGrant::VIEW, $user));

            if ($model instanceof CmsAlbum) {
                $abilities = [
                    AlbumType::Normal->value => 'cms.gallery.album.update',
                    AlbumType::Banner->value => 'cms.gallery.banner.update',
                ];

                foreach ($abilities as $type => $ability) {
                    if ($this->userCan($user, $ability)) {
                        $w->orWhere(fn (Builder $t) => $t->where("{$table}.type", $type)
                            ->where(fn (Builder $m) => $this->orManageable($m, CmsAlbum::class, "{$table}.id", $ability, $user)));
                    }
                }

                return;
            }

            $this->orManageable($w, $model::class, "{$table}.id", $this->abilityFor($model, 'update'), $user);
        });
    }

    // ----------------------------------------------------------- Manage

    /**
     * Global ACL $ability AND (empty managers list across the chain OR user/role listed).
     */
    public function canManage(Model $scope, string $ability, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        if ($this->isDev($user)) {
            return true;
        }

        if (! $this->userCan($user, $ability)) {
            return false;
        }

        $chain = $this->manageChain($scope);
        $hasList = collect($chain)->contains(fn (Model $node) => $this->grantsOf($node, CmsAccessGrant::MANAGE)->isNotEmpty());

        return ! $hasList || $this->isListed($chain, CmsAccessGrant::MANAGE, $user);
    }

    public function authorizeManage(Model $scope, string $ability): void
    {
        abort_unless($this->canManage($scope, $ability), 403);
    }

    /** Dev, or a manager of $scope holding cms.access.update. */
    public function canEditAccess(Model $scope, string $ability, ?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if ($user === null) {
            return false;
        }

        return $this->isDev($user)
            || ($this->userCan($user, 'cms.access.update') && $this->canManage($scope, $ability, $user));
    }

    /** Default ACL ability for an action on an existing item. */
    public function abilityFor(Model $item, string $action): string
    {
        return match (true) {
            $item instanceof CmsPage => "cms.page.{$action}",
            $item instanceof CmsSection => "cms.article.section.{$action}",
            $item instanceof CmsArticle => "cms.article.{$action}",
            $item instanceof CmsCategory => "cms.article.category.{$action}",
            $item instanceof CmsAlbum => $item->type === AlbumType::Banner ? "cms.gallery.banner.{$action}" : "cms.gallery.album.{$action}",
            $item instanceof CmsAlbumImage => "cms.gallery.image.{$action}",
            $item instanceof CmsDownload => "cms.download.category.{$action}",
            $item instanceof CmsDownloadFile => "cms.download.file.{$action}",
            default => throw new InvalidArgumentException('Unsupported CMS model '.$item::class),
        };
    }

    public function isDev(Authenticatable $user): bool
    {
        return method_exists($user, 'isDev') && $user->isDev();
    }

    public function userCan(Authenticatable $user, string $ability): bool
    {
        return $user instanceof Authorizable && $user->can($ability);
    }

    /** Drop the memoized page tree (after a page is saved within the same request). */
    public function forgetPageTree(): void
    {
        $this->pageTree = null;
    }

    // ------------------------------------------------------------ Grants

    /**
     * Own grants of an item for one ability (no ancestors).
     *
     * @return Collection<int, CmsAccessGrant>
     */
    public function grantsOf(Model $item, string $ability): Collection
    {
        $class = $item->getMorphClass();

        $this->grants[$class] ??= CmsAccessGrant::where('grantable_type', $class)
            ->get()
            ->groupBy(fn (CmsAccessGrant $g) => $g->grantable_id.'|'.$g->ability)
            ->all();

        return $this->grants[$class][$item->getKey().'|'.$ability] ?? new EloquentCollection;
    }

    /**
     * Grants inherited from ancestors (shown read-only in the Access panel).
     *
     * @return Collection<int, CmsAccessGrant>
     */
    public function inheritedGrants(Model $item, string $ability): Collection
    {
        $chain = $ability === CmsAccessGrant::VIEW ? $this->viewChain($item) : $this->manageChain($item);

        return collect($chain)
            ->reject(fn (Model $node) => $node->is($item))
            ->flatMap(fn (Model $node) => $this->grantsOf($node, $ability))
            ->values();
    }

    /**
     * Replace an item's list. Devs control every entry (new ones become base);
     * others can only add/remove non-base entries.
     *
     * @param  array<int, mixed>  $principalKeys  e.g. ['user:5', 'role:2']
     */
    public function syncGrants(Model $item, string $ability, array $principalKeys, Authenticatable $actor): void
    {
        $isDev = $this->isDev($actor);
        $wanted = collect($principalKeys)
            ->filter(fn ($key) => is_string($key) && preg_match('/^(user|role):\d+$/', $key) === 1)
            ->unique()
            ->values();

        /** @var Collection<int, CmsAccessGrant> $existing */
        $existing = CmsAccessGrant::where('grantable_type', $item->getMorphClass())
            ->where('grantable_id', $item->getKey())
            ->where('ability', $ability)
            ->get();

        $existing
            ->filter(fn (CmsAccessGrant $g) => ($isDev || ! $g->is_base) && ! $wanted->contains($g->principalKey()))
            ->each(fn (CmsAccessGrant $g) => $g->delete());

        $have = $existing->map(fn (CmsAccessGrant $g) => $g->principalKey());

        $wanted->reject(fn (string $key) => $have->contains($key))->each(function (string $key) use ($item, $ability, $isDev, $actor): void {
            [$type, $id] = explode(':', $key);

            CmsAccessGrant::create([
                'grantable_type' => $item->getMorphClass(),
                'grantable_id' => $item->getKey(),
                'ability' => $ability,
                'principal_type' => $type,
                'principal_id' => (int) $id,
                'is_base' => $isDev,
                'created_by' => $actor->getAuthIdentifier(),
            ]);
        });

        unset($this->grants[$item->getMorphClass()]);
    }

    /**
     * Remote search for the Access panel's user/role picker.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function searchPrincipals(string $query, int $limit = 10): array
    {
        $like = '%'.$query.'%';

        $roles = Role::where('name', 'like', $like)->limit($limit)->get(['id', 'name']);
        $users = $this->userModel()::query()
            ->where(fn (Builder $q) => $q->where('name', 'like', $like)
                ->orWhere('email', 'like', $like)
                ->orWhere('username', 'like', $like))
            ->limit($limit)
            ->get(['id', 'name']);

        return $this->principalOptions($roles, $users);
    }

    /**
     * @param  array<int, mixed>  $keys
     * @return array<int, array{value: string, label: string}>
     */
    public function loadPrincipals(array $keys): array
    {
        $ids = ['user' => [], 'role' => []];

        foreach ($keys as $key) {
            if (is_string($key) && preg_match('/^(user|role):(\d+)$/', $key, $m) === 1) {
                $ids[$m[1]][] = (int) $m[2];
            }
        }

        return $this->principalOptions(
            Role::whereIn('id', $ids['role'])->get(['id', 'name']),
            $this->userModel()::whereIn('id', $ids['user'])->get(['id', 'name']),
        );
    }

    // ---------------------------------------------------------- Internals

    /**
     * @param  iterable<Model>  $roles
     * @param  iterable<Model>  $users
     * @return array<int, array{value: string, label: string}>
     */
    private function principalOptions(iterable $roles, iterable $users): array
    {
        $options = [];

        foreach ($roles as $role) {
            $options[] = ['value' => 'role:'.$role->getKey(), 'label' => __('cms::access.principal_role', ['name' => $role->getAttribute('name')])];
        }

        foreach ($users as $user) {
            $options[] = ['value' => 'user:'.$user->getKey(), 'label' => __('cms::access.principal_user', ['name' => $user->getAttribute('name')])];
        }

        return $options;
    }

    /**
     * @return class-string<Model>
     */
    private function userModel(): string
    {
        /** @var class-string<Model> $model */
        $model = config('auth.providers.users.model');

        return $model;
    }

    /**
     * Item first, then the nodes it inherits visibility / view lists from.
     *
     * @return list<Model>
     */
    private function viewChain(Model $item): array
    {
        return match (true) {
            $item instanceof CmsPage => $this->pageAncestry($item),
            $item instanceof CmsArticle => [$item, $item->section],
            default => $this->manageChain($item),
        };
    }

    /**
     * Nodes whose managers list governs the item.
     *
     * @return list<Model>
     */
    private function manageChain(Model $item): array
    {
        return match (true) {
            $item instanceof CmsPage => $this->pageAncestry($item),
            $item instanceof CmsArticle, $item instanceof CmsCategory => [$item->section],
            $item instanceof CmsAlbumImage => [$item->album],
            $item instanceof CmsDownloadFile => [$item->download],
            default => [$item],
        };
    }

    /**
     * @return list<Model>
     */
    private function pageAncestry(CmsPage $page): array
    {
        $this->pageTree ??= CmsPage::query()->get(['id', 'parent_id', 'visibility'])->keyBy('id');

        $chain = [$page];
        $seen = [$page->id => true];
        $parentId = $page->parent_id;

        while ($parentId !== null && ! isset($seen[$parentId]) && ($parent = $this->pageTree->get($parentId)) !== null) {
            $chain[] = $parent;
            $seen[$parentId] = true;
            $parentId = $parent->parent_id;
        }

        return $chain;
    }

    /**
     * @param  list<Model>  $chain
     */
    private function isListed(array $chain, string $ability, Authenticatable $user): bool
    {
        $keys = $this->principalKeysOf($user);

        foreach ($chain as $node) {
            if ($this->grantsOf($node, $ability)->contains(fn (CmsAccessGrant $g) => in_array($g->principalKey(), $keys, true))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>
     */
    private function principalKeysOf(Authenticatable $user): array
    {
        $id = (int) $user->getAuthIdentifier();

        if (! isset($this->principalKeys[$id])) {
            $keys = ['user:'.$id];

            foreach (method_exists($user, 'roles') ? $user->roles()->pluck('roles.id')->all() : [] as $roleId) {
                $keys[] = 'role:'.$roleId;
            }

            $this->principalKeys[$id] = $keys;
        }

        return $this->principalKeys[$id];
    }

    /**
     * Ids of $class items whose $ability list names the user or one of their roles.
     *
     * @param  class-string<Model>  $class
     * @return list<int>
     */
    private function grantedIds(string $class, string $ability, Authenticatable $user): array
    {
        $keys = array_map(fn (string $key) => explode(':', $key), $this->principalKeysOf($user));

        return array_values(CmsAccessGrant::query()
            ->where('grantable_type', (new $class)->getMorphClass())
            ->where('ability', $ability)
            ->where(function (Builder $q) use ($keys): void {
                foreach ($keys as [$type, $id]) {
                    $q->orWhere(fn (Builder $p) => $p->where('principal_type', $type)->where('principal_id', (int) $id));
                }
            })
            ->pluck('grantable_id')
            ->map(fn ($id) => (int) $id)
            ->all());
    }

    /**
     * OR-in "user manages the $class row referenced by $column": the ACL plus
     * (no managers list on that row OR user/role listed).
     *
     * @param  Builder<covariant Model>  $w
     * @param  class-string<Model>  $class
     */
    private function orManageable(Builder $w, string $class, string $column, string $ability, Authenticatable $user): void
    {
        if (! $this->userCan($user, $ability)) {
            return;
        }

        $withList = CmsAccessGrant::query()->select('grantable_id')
            ->where('grantable_type', (new $class)->getMorphClass())
            ->where('ability', CmsAccessGrant::MANAGE);

        $w->orWhereNotIn($column, $withList)
            ->orWhereIn($column, $this->grantedIds($class, CmsAccessGrant::MANAGE, $user));
    }
}
