<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Services\CmsAccessService;

/**
 * Access-panel state for a Livewire form object: visibility + view/manage
 * principal keys ("user:5", "role:2"). Editability is always re-checked
 * server-side on save — $canEditAccess only drives the UI.
 */
trait HasAccessFields
{
    public string $visibility = 'public';

    /** @var array<int, string> editable view entries */
    public array $viewPrincipals = [];

    /** @var array<int, string> editable manage entries */
    public array $managePrincipals = [];

    /** @var array<string, array<int, array{value: string, label: string, reason: string}>> view|manage => read-only entries */
    public array $lockedPrincipals = ['view' => [], 'manage' => []];

    public bool $canEditAccess = false;

    /**
     * @param  Model|null  $model  the item being edited (null when creating)
     * @param  Model|null  $scope  what the edit right is checked against: the item, or its parent page/section when creating
     */
    protected function fillAccess(?Model $model, ?Model $scope, string $ability, CmsVisibility $default): void
    {
        $access = app(CmsAccessService::class);
        $user = auth()->user();
        $isDev = $user !== null && $access->isDev($user);

        $this->canEditAccess = $this->mayEditAccess($scope, $ability);

        $visibility = $model?->getAttribute('visibility');
        $this->visibility = ($visibility instanceof CmsVisibility ? $visibility : $default)->value;

        foreach ([CmsAccessGrant::VIEW, CmsAccessGrant::MANAGE] as $list) {
            $own = $model !== null ? $access->grantsOf($model, $list) : collect();
            $inherited = match (true) {
                $model !== null => $access->inheritedGrants($model, $list),
                $scope !== null => $access->inheritedGrants($scope, $list)->merge($access->grantsOf($scope, $list)),
                default => collect(),
            };

            $editable = $own->filter(fn (CmsAccessGrant $g) => $isDev || ! $g->is_base);
            $locked = $own->reject(fn (CmsAccessGrant $g) => $isDev || ! $g->is_base);

            $labels = collect($access->loadPrincipals(
                $own->merge($inherited)->map(fn (CmsAccessGrant $g) => $g->principalKey())->all()
            ))->pluck('label', 'value');

            $entry = fn (string $reason) => fn (CmsAccessGrant $g) => [
                'value' => $g->principalKey(),
                'label' => (string) $labels->get($g->principalKey(), $g->principalKey()),
                'reason' => $reason,
            ];

            $keys = $editable->map(fn (CmsAccessGrant $g) => $g->principalKey())->values()->all();

            if ($list === CmsAccessGrant::VIEW) {
                $this->viewPrincipals = $keys;
            } else {
                $this->managePrincipals = $keys;
            }

            $this->lockedPrincipals[$list] = $locked->toBase()->map($entry('locked'))
                ->merge($inherited->toBase()->map($entry('inherited')))
                ->unique('value')
                ->values()
                ->all();
        }
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function visibilityOptions(bool $withInherit): array
    {
        return CmsVisibility::options($withInherit);
    }

    /**
     * @return array<string, mixed>
     */
    protected function accessRules(bool $allowInherit): array
    {
        $cases = $allowInherit
            ? CmsVisibility::cases()
            : [CmsVisibility::Public, CmsVisibility::Protected, CmsVisibility::Private];

        return [
            'visibility' => 'required|in:'.implode(',', array_map(fn (CmsVisibility $v) => $v->value, $cases)),
            'viewPrincipals' => 'array',
            'viewPrincipals.*' => ['string', 'regex:/^(user|role):\d+$/'],
            'managePrincipals' => 'array',
            'managePrincipals.*' => ['string', 'regex:/^(user|role):\d+$/'],
        ];
    }

    /**
     * Visibility for the model's data array — only when the actor may change it.
     *
     * @return array<string, string>
     */
    protected function accessData(?Model $scope, string $ability): array
    {
        return $this->mayEditAccess($scope, $ability) ? ['visibility' => $this->visibility] : [];
    }

    /** Persist the lists after the model is saved. */
    protected function saveAccess(Model $model, ?Model $scope, string $ability, bool $withManagers): void
    {
        $user = auth()->user();

        if ($user === null || ! $this->mayEditAccess($scope, $ability)) {
            return;
        }

        $access = app(CmsAccessService::class);
        $access->syncGrants($model, CmsAccessGrant::VIEW, $this->viewPrincipals, $user);

        if ($withManagers) {
            $access->syncGrants($model, CmsAccessGrant::MANAGE, $this->managePrincipals, $user);
        }
    }

    /** New root items have no scope to manage — only the ACL applies. */
    private function mayEditAccess(?Model $scope, string $ability): bool
    {
        $user = auth()->user();
        $access = app(CmsAccessService::class);

        if ($user === null) {
            return false;
        }

        return $scope === null
            ? $access->isDev($user) || $access->userCan($user, 'cms.access.update')
            : $access->canEditAccess($scope, $ability, $user);
    }
}
