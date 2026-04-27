<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Services\CmsMenuService;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public int $menuId;

    // ── Add form ────────────────────────────────────────────────────────────
    public string $newLabel = '';

    public string $newTargetType = 'url';

    public ?int $newTargetId = null;

    public string $newTargetUrl = '';

    public bool $newOpenNewTab = false;

    public string $newCustomView = '';

    public string $newCustomLayout = '';

    /** When set, addItem() will create a child of this parent ID */
    public ?int $addingChildFor = null;

    // ── Edit form ───────────────────────────────────────────────────────────
    public ?int $editingId = null;

    public string $editLabel = '';

    public string $editTargetType = 'url';

    public ?int $editTargetId = null;

    public string $editTargetUrl = '';

    public bool $editOpenNewTab = false;

    public ?int $editParentId = null;

    public string $editCustomView = '';

    public string $editCustomLayout = '';

    public function mount(int $menuId): void
    {
        $this->menuId = $menuId;
        app(CmsMenuService::class)->findMenuOrFailById($menuId);
        synav()->setActiveMenu('cms.menu');
    }

    public function title(): string
    {
        return __('cms::labels.manage_items').': '.$this->menu->name;
    }

    public function updatedNewTargetType(): void
    {
        $this->newTargetId = null;
    }

    public function updatedEditTargetType(): void
    {
        $this->editTargetId = null;
    }

    public function startAddChild(int $parentId): void
    {
        $this->reset('newLabel', 'newTargetId', 'newTargetUrl', 'newOpenNewTab', 'newCustomView', 'newCustomLayout');
        $this->newTargetType = 'url';
        $this->addingChildFor = $parentId;
    }

    public function cancelAddChild(): void
    {
        $this->reset('newLabel', 'newTargetId', 'newTargetUrl', 'newOpenNewTab', 'newCustomView', 'newCustomLayout');
        $this->newTargetType = 'url';
        $this->addingChildFor = null;
    }

    public function addItem(): void
    {
        $validTypes = 'url,page,section,category,article,album,album_image,album_list,download,download_list';
        $noIdTypes = ['url', 'album_list', 'download_list'];
        $this->validate([
            'newLabel' => 'required|string|max:255',
            'newTargetType' => "required|in:{$validTypes}",
            'newTargetId' => ! in_array($this->newTargetType, $noIdTypes) ? 'required|integer' : 'nullable',
            'newTargetUrl' => $this->newTargetType === 'url' ? 'nullable|string|max:2048' : 'nullable',
            'newCustomView' => 'nullable|string|max:255',
            'newCustomLayout' => 'nullable|string|max:255',
        ]);

        $supportsCustomView = in_array($this->newTargetType, ['album', 'album_list', 'download', 'download_list']);

        app(CmsMenuService::class)->addItemToMenu($this->menuId, [
            'label' => $this->newLabel,
            'target_type' => $this->newTargetType,
            'target_id' => ! in_array($this->newTargetType, $noIdTypes) ? $this->newTargetId : null,
            'target_url' => in_array($this->newTargetType, ['url', 'album_list', 'download_list']) ? ($this->newTargetUrl ?: null) : null,
            'open_new_tab' => $this->newOpenNewTab,
            'custom_view' => $supportsCustomView ? ($this->newCustomView ?: null) : null,
            'custom_layout' => $supportsCustomView ? ($this->newCustomLayout ?: null) : null,
        ], $this->addingChildFor);

        $this->reset('newLabel', 'newTargetId', 'newTargetUrl', 'newOpenNewTab', 'newCustomView', 'newCustomLayout');
        $this->newTargetType = 'url';
        $this->addingChildFor = null;
        unset($this->items);
    }

    public function startEdit(int $id): void
    {
        $item = app(CmsMenuService::class)->findMenuItemOrFailInMenu($this->menuId, $id);
        $this->addingChildFor = null;
        $this->editingId = $id;
        $this->editLabel = $item->label;
        $this->editTargetType = $item->target_type;
        $this->editTargetId = $item->target_id;
        $this->editTargetUrl = $item->target_url ?? '';
        $this->editOpenNewTab = $item->open_new_tab;
        $this->editParentId = $item->parent_id;
        $this->editCustomView = $item->custom_view ?? '';
        $this->editCustomLayout = $item->custom_layout ?? '';
    }

    public function saveEdit(): void
    {
        $validTypes = 'url,page,section,category,article,album,album_image,album_list,download,download_list';
        $noIdTypes = ['url', 'album_list', 'download_list'];
        $this->validate([
            'editLabel' => 'required|string|max:255',
            'editTargetType' => "required|in:{$validTypes}",
            'editTargetId' => ! in_array($this->editTargetType, $noIdTypes) ? 'required|integer' : 'nullable',
            'editTargetUrl' => $this->editTargetType === 'url' ? 'nullable|string|max:2048' : 'nullable',
            'editCustomView' => 'nullable|string|max:255',
            'editCustomLayout' => 'nullable|string|max:255',
        ]);

        $supportsCustomView = in_array($this->editTargetType, ['album', 'album_list', 'download', 'download_list']);

        $item = app(CmsMenuService::class)->findMenuItemOrFailInMenu($this->menuId, $this->editingId);
        app(CmsMenuService::class)->updateMenuItem($item, [
            'label' => $this->editLabel,
            'target_type' => $this->editTargetType,
            'target_id' => ! in_array($this->editTargetType, $noIdTypes) ? $this->editTargetId : null,
            'target_url' => in_array($this->editTargetType, ['url', 'album_list', 'download_list']) ? ($this->editTargetUrl ?: null) : null,
            'open_new_tab' => $this->editOpenNewTab,
            'parent_id' => $this->editParentId,
            'custom_view' => $supportsCustomView ? ($this->editCustomView ?: null) : null,
            'custom_layout' => $supportsCustomView ? ($this->editCustomLayout ?: null) : null,
        ]);

        $this->reset('editingId', 'editLabel', 'editTargetType', 'editTargetId', 'editTargetUrl', 'editOpenNewTab', 'editParentId', 'editCustomView', 'editCustomLayout');
        unset($this->items);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.item_updated'));
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editLabel', 'editTargetType', 'editTargetId', 'editTargetUrl', 'editOpenNewTab', 'editParentId', 'editCustomView', 'editCustomLayout');
    }

    public function deleteItem(int $id): void
    {
        app(CmsMenuService::class)->deleteMenuItemInMenu($this->menuId, $id);
        unset($this->items);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.item_deleted'));
    }

    public function updateOrder(array $ids): void
    {
        app(CmsMenuService::class)->reorderItemsInMenu($this->menuId, $ids);
        unset($this->items);
    }

    // ── Content search / load methods for adv-select ────────────────────────

    public function searchPages(string $query): array
    {
        return app(CmsPageService::class)->searchOptions($query);
    }

    public function loadPages(int|array $ids): array
    {
        return app(CmsPageService::class)->loadOptions($ids);
    }

    public function searchSections(string $query): array
    {
        return app(CmsArticleService::class)->searchSectionOptions($query);
    }

    public function loadSections(int|array $ids): array
    {
        return app(CmsArticleService::class)->loadSectionOptions($ids);
    }

    public function searchCategories(string $query): array
    {
        return app(CmsArticleService::class)->searchCategoryOptions($query);
    }

    public function loadCategories(int|array $ids): array
    {
        return app(CmsArticleService::class)->loadCategoryOptions($ids);
    }

    public function searchArticles(string $query): array
    {
        return app(CmsArticleService::class)->searchArticleOptions($query);
    }

    public function loadArticles(int|array $ids): array
    {
        return app(CmsArticleService::class)->loadArticleOptions($ids);
    }

    public function searchAlbums(string $query): array
    {
        return app(CmsGalleryService::class)->searchAlbumOptions($query);
    }

    public function loadAlbums(int|array $ids): array
    {
        return app(CmsGalleryService::class)->loadAlbumOptions($ids);
    }

    public function searchAlbumImages(string $query): array
    {
        return app(CmsGalleryService::class)->searchAlbumImageOptions($query);
    }

    public function loadAlbumImages(int|array $ids): array
    {
        return app(CmsGalleryService::class)->loadAlbumImageOptions($ids);
    }

    public function searchDownloads(string $query): array
    {
        return app(CmsDownloadService::class)->searchOptions($query);
    }

    public function loadDownloads(int|array $ids): array
    {
        return app(CmsDownloadService::class)->loadOptions($ids);
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    #[Computed()]
    public function menu(): CmsMenu
    {
        return app(CmsMenuService::class)->findMenuOrFailById($this->menuId);
    }

    #[Computed()]
    public function items()
    {
        return app(CmsMenuService::class)->getRootItemsWithChildrenForMenu($this->menuId);
    }

    #[Computed()]
    public function allItems()
    {
        return app(CmsMenuService::class)->getAllItemsForMenu($this->menuId);
    }

    #[Computed()]
    public function targetTypeOptions(): array
    {
        return [
            'url' => __('cms::labels.type_url'),
            'page' => __('cms::labels.type_page'),
            'section' => __('cms::labels.type_section'),
            'category' => __('cms::labels.type_category'),
            'article' => __('cms::labels.type_article'),
            'album' => __('cms::labels.type_album'),
            'album_image' => __('cms::labels.type_album_image'),
            'album_list' => __('cms::labels.type_album_list'),
            'download' => __('cms::labels.type_download'),
            'download_list' => __('cms::labels.type_download_list'),
        ];
    }

    #[Computed()]
    public function parentOptions(): array
    {
        $options = ['' => '— '.__('cms::labels.no_parent').' —'];
        if (! $this->editingId) {
            return $options;
        }

        $allItems = $this->allItems;
        $rootIds = $allItems->whereNull('parent_id')->pluck('id');

        // Level-0 items can be parents (for level-1 children)
        foreach ($allItems->whereNull('parent_id')->where('id', '!=', $this->editingId) as $item) {
            $options[$item->id] = $item->label;
        }

        // Level-1 items can also be parents (for level-2 grandchildren)
        foreach ($allItems->whereNotNull('parent_id')->where('id', '!=', $this->editingId) as $item) {
            if ($rootIds->contains($item->parent_id)) {
                $parentItem = $allItems->firstWhere('id', $item->parent_id);
                $parentLabel = $parentItem !== null ? $parentItem->label : '';
                $options[$item->id] = $parentLabel.' › '.$item->label;
            }
        }

        return $options;
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.menu_list'), icon: 'fa-solid fa-bars', url: backend_route('cms.menu.index'))
            ->add(label: $this->menu->name)
            ->add(label: __('cms::labels.manage_items'));
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
