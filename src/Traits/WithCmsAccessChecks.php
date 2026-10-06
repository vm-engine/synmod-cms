<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use Illuminate\Database\Eloquent\Model;
use VmEngine\Cms\Services\CmsAccessService;

/**
 * Per-row manage checks for backend list views (`$this->canManage($row, 'cms.page.update')`).
 */
trait WithCmsAccessChecks
{
    public function canManage(Model $item, string $ability): bool
    {
        return app(CmsAccessService::class)->canManage($item, $ability);
    }

    /** Same, with the ability derived from the item (e.g. album vs banner). */
    public function canManageAction(Model $item, string $action): bool
    {
        $access = app(CmsAccessService::class);

        return $access->canManage($item, $access->abilityFor($item, $action));
    }
}
