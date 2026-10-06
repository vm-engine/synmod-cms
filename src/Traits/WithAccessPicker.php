<?php

declare(strict_types=1);

namespace VmEngine\Cms\Traits;

use VmEngine\Cms\Services\CmsAccessService;

/**
 * adv-select remote search/load for the Access panel's user/role pickers.
 */
trait WithAccessPicker
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function searchPrincipals(string $query): array
    {
        return app(CmsAccessService::class)->searchPrincipals($query);
    }

    /**
     * @param  array<int, mixed>  $keys
     * @return array<int, array{value: string, label: string}>
     */
    public function loadPrincipals(array $keys): array
    {
        return app(CmsAccessService::class)->loadPrincipals($keys);
    }
}
