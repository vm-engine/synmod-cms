<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Support\Collection;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;

class CmsMenuService
{
    public function getMenuByPosition(string $position): ?CmsMenu
    {
        return CmsMenu::where('position', $position)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Build nested menu tree for the given position.
     * Returns flat list of root items with nested children.
     *
     * @return Collection<int, CmsMenuItem>
     */
    public function buildTree(string $position): Collection
    {
        $menu = $this->getMenuByPosition($position);

        if (! $menu) {
            return collect();
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, CmsMenuItem> $items */
        $items = $menu->items()
            ->where('is_active', true)
            ->with('children.children')
            ->get();

        return $items->whereNull('parent_id')->values();
    }

    /**
     * Build nested tree from a CmsMenu instance.
     *
     * @return Collection<int, CmsMenuItem>
     */
    public function buildTreeFromMenu(CmsMenu $menu): Collection
    {
        /** @var Collection<int, CmsMenuItem> $items */
        $items = $menu->items()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return $this->nestItems($items, null);
    }

    /**
     * @param  Collection<int, CmsMenuItem>  $items
     * @return Collection<int, CmsMenuItem>
     */
    private function nestItems(Collection $items, ?int $parentId): Collection
    {
        return $items->where('parent_id', $parentId)->map(function (CmsMenuItem $item) use ($items) {
            $item->setRelation('children', $this->nestItems($items, $item->id));

            return $item;
        })->values();
    }
}
