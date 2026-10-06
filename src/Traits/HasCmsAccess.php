<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use VmEngine\Cms\Models\CmsAccessGrant;
use VmEngine\Cms\Services\CmsAccessService;

/**
 * Content with a `visibility` column and view/manage lists in cms_access_grants.
 */
trait HasCmsAccess
{
    public static function bootHasCmsAccess(): void
    {
        static::deleted(function (Model $model): void {
            CmsAccessGrant::where('grantable_type', $model->getMorphClass())
                ->where('grantable_id', $model->getKey())
                ->delete();
        });
    }

    /**
     * @return MorphMany<CmsAccessGrant, $this>
     */
    public function accessGrants(): MorphMany
    {
        return $this->morphMany(CmsAccessGrant::class, 'grantable');
    }

    /**
     * Frontend visibility filter (flat types: section, article, album, download).
     *
     * @param  Builder<static>  $builder
     */
    #[Scope]
    protected function visibleTo(Builder $builder, ?Authenticatable $user = null): void
    {
        app(CmsAccessService::class)->applyVisibleScope($builder, $user ?? auth()->user());
    }
}
