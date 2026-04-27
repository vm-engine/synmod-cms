<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;

class CmsMenuService
{
    public function __construct(private readonly CmsActivityLogger $logger) {}

    // ----------------------------------------------------------------- Reads

    public function getMenuByPosition(string $position): ?CmsMenu
    {
        return CmsMenu::where('position', $position)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findMenuOrFailById(int $id, array $with = []): CmsMenu
    {
        return CmsMenu::with($with)->findOrFail($id);
    }

    /**
     * @param  array{q?: string}  $filters
     * @return LengthAwarePaginator<int, CmsMenu>
     */
    public function paginateMenus(array $filters, int $perPage): LengthAwarePaginator
    {
        $query = CmsMenu::withCount('items');

        if (! empty($filters['q']) && strlen($filters['q']) > 2) {
            $q = $filters['q'];
            $query->where(function ($qq) use ($q): void {
                $qq->where('name', 'like', "%{$q}%")
                    ->orWhere('position', 'like', "%{$q}%");
            });
        }

        return $query->paginate($perPage)->onEachSide(1);
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

        /** @var EloquentCollection<int, CmsMenuItem> $items */
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
        /** @var EloquentCollection<int, CmsMenuItem> $items */
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

    // ---------------------------------------------------------------- Writes

    /**
     * @param  array<string, mixed>  $data
     */
    public function createMenu(array $data): CmsMenu
    {
        $menu = CmsMenu::create($data);
        $this->logger->logCreate('menu', $menu, $menu->toArray());

        return $menu;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMenu(CmsMenu $menu, array $data): CmsMenu
    {
        $oldData = $menu->toArray();
        $menu->update($data);
        $this->logger->logUpdate('menu', $menu, $oldData);

        return $menu;
    }

    public function deleteMenuByToken(string $token): CmsMenu
    {
        $id = CmsMenu::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $menu = CmsMenu::findOrFail($id);
        $this->logger->logDelete('menu', $menu);
        $menu->delete();

        return $menu;
    }

    // ------------------------------------------------------------- Menu items

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findMenuItemOrFailInMenu(int $menuId, int $itemId, array $with = []): CmsMenuItem
    {
        return CmsMenuItem::with($with)
            ->where('menu_id', $menuId)
            ->findOrFail($itemId);
    }

    /**
     * Root items with two levels of nested children — used by backend menu-item manager.
     *
     * @return EloquentCollection<int, CmsMenuItem>
     */
    public function getRootItemsWithChildrenForMenu(int $menuId): EloquentCollection
    {
        /** @var EloquentCollection<int, CmsMenuItem> $items */
        $items = CmsMenuItem::where('menu_id', $menuId)
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();

        return $items;
    }

    /**
     * Flat list of all items in a menu, ordered by sort_order — used for parent picker logic.
     *
     * @return EloquentCollection<int, CmsMenuItem>
     */
    public function getAllItemsForMenu(int $menuId): EloquentCollection
    {
        /** @var EloquentCollection<int, CmsMenuItem> $items */
        $items = CmsMenuItem::where('menu_id', $menuId)->orderBy('sort_order')->get();

        return $items;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addItemToMenu(int $menuId, array $data, ?int $parentId = null): CmsMenuItem
    {
        $maxOrder = CmsMenuItem::where('menu_id', $menuId)
            ->where('parent_id', $parentId)
            ->max('sort_order') ?? -1;

        $item = CmsMenuItem::create([
            'menu_id' => $menuId,
            'parent_id' => $parentId,
            ...$data,
            'sort_order' => $maxOrder + 1,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->logger->logCreate('menu_item', $item, $item->toArray());

        return $item;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateMenuItem(CmsMenuItem $item, array $data): CmsMenuItem
    {
        $oldData = $item->toArray();
        $item->update($data);
        $this->logger->logUpdate('menu_item', $item, $oldData);

        return $item;
    }

    public function deleteMenuItemInMenu(int $menuId, int $itemId): void
    {
        $item = $this->findMenuItemOrFailInMenu($menuId, $itemId);
        $this->logger->logDelete('menu_item', $item);
        $item->delete();
    }

    /**
     * @param  array<int, int>  $ids  Map of position => menu_item_id
     */
    public function reorderItemsInMenu(int $menuId, array $ids): void
    {
        DB::transaction(function () use ($menuId, $ids): void {
            foreach ($ids as $order => $id) {
                CmsMenuItem::where('menu_id', $menuId)
                    ->where('id', $id)
                    ->update(['sort_order' => $order]);
            }
        });
    }
}
