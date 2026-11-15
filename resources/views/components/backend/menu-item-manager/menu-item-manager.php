<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Services\CmsBuilderPageService;
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

    /** @var array<string, string> */
    public array $newLabelTranslations = [];

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

    /** @var array<string, string> */
    public array $editLabelTranslations = [];

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
        $this->reset('newLabel', 'newLabelTranslations', 'newTargetId', 'newTargetUrl', 'newOpenNewTab', 'newCustomView', 'newCustomLayout');
        $this->newTargetType = 'url';
        $this->addingChildFor = $parentId;
    }

    public function cancelAddChild(): void
    {
        $this->reset('newLabel', 'newLabelTranslations', 'newTargetId', 'newTargetUrl', 'newOpenNewTab', 'newCustomView', 'newCustomLayout');
        $this->newTargetType = 'url';
        $this->addingChildFor = null;
    }

    public function addItem(): void
    {
        $validTypes = 'url,page,builder_page,section,category,article,album,album_image,album_list,download,download_list';
        $noIdTypes = ['url', 'album_list', 'download_list'];
        $this->validate([
            'newLabel' => 'required|string|max:255',
            'newLabelTranslations.*' => 'nullable|string|max:255',
            'newTargetType' => "required|in:{$validTypes}",
            'newTargetId' => ! in_array($this->newTargetType, $noIdTypes) ? 'required|integer' : 'nullable',
            'newTargetUrl' => $this->newTargetType === 'url' ? 'nullable|string|max:2048' : 'nullable',
            'newCustomView' => 'nullable|string|max:255',
            'newCustomLayout' => 'nullable|string|max:255',
        ]);

        $supportsCustomView = in_array($this->newTargetType, ['album', 'album_list', 'download', 'download_list']);

        DB::transaction(function () use ($noIdTypes, $supportsCustomView): void {
            $item = app(CmsMenuService::class)->addItemToMenu($this->menuId, [
                'label' => $this->newLabel,
                'target_type' => $this->newTargetType,
                'target_id' => ! in_array($this->newTargetType, $noIdTypes) ? $this->newTargetId : null,
                'target_url' => in_array($this->newTargetType, ['url', 'album_list', 'download_list']) ? ($this->newTargetUrl ?: null) : null,
                'open_new_tab' => $this->newOpenNewTab,
                'custom_view' => $supportsCustomView ? ($this->newCustomView ?: null) : null,
                'custom_layout' => $supportsCustomView ? ($this->newCustomLayout ?: null) : null,
            ], $this->addingChildFor);
            $item->setTranslations($this->labelTranslationsByLocale($this->newLabelTranslations));
        });

        $this->reset('newLabel', 'newLabelTranslations', 'newTargetId', 'newTargetUrl', 'newOpenNewTab', 'newCustomView', 'newCustomLayout');
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
        $this->editLabelTranslations = array_map(fn (array $fields): string => $fields['label'] ?? '', $item->translationValues());
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
        $validTypes = 'url,page,builder_page,section,category,article,album,album_image,album_list,download,download_list';
        $noIdTypes = ['url', 'album_list', 'download_list'];
        $this->validate([
            'editLabel' => 'required|string|max:255',
            'editLabelTranslations.*' => 'nullable|string|max:255',
            'editTargetType' => "required|in:{$validTypes}",
            'editTargetId' => ! in_array($this->editTargetType, $noIdTypes) ? 'required|integer' : 'nullable',
            'editTargetUrl' => $this->editTargetType === 'url' ? 'nullable|string|max:2048' : 'nullable',
            'editCustomView' => 'nullable|string|max:255',
            'editCustomLayout' => 'nullable|string|max:255',
        ]);

        $supportsCustomView = in_array($this->editTargetType, ['album', 'album_list', 'download', 'download_list']);

        $item = app(CmsMenuService::class)->findMenuItemOrFailInMenu($this->menuId, $this->editingId);
        DB::transaction(function () use ($item, $noIdTypes, $supportsCustomView): void {
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
            $item->setTranslations($this->labelTranslationsByLocale($this->editLabelTranslations));
        });

        $this->reset('editingId', 'editLabel', 'editLabelTranslations', 'editTargetType', 'editTargetId', 'editTargetUrl', 'editOpenNewTab', 'editParentId', 'editCustomView', 'editCustomLayout');
        unset($this->items);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.item_updated'));
    }

    /**
     * ['id' => 'Beranda'] → ['id' => ['label' => 'Beranda']] for setTranslations().
     *
     * @param  array<string, string>  $labels
     * @return array<string, array<string, string>>
     */
    private function labelTranslationsByLocale(array $labels): array
    {
        return array_map(fn (string $label): array => ['label' => $label], $labels);
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editLabel', 'editLabelTranslations', 'editTargetType', 'editTargetId', 'editTargetUrl', 'editOpenNewTab', 'editParentId', 'editCustomView', 'editCustomLayout');
    }

    public function deleteItem(string $token): void
    {
        $id = CmsMenuItem::validateDeleteToken($token);

        if ($id === false) {
            $this->dispatch('synapse-confirmed');
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::labels.invalid_delete_token'));

            return;
        }

        app(CmsMenuService::class)->deleteMenuItemInMenu($this->menuId, (int) $id);
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

    public function searchBuilderPages(string $query): array
    {
        return app(CmsBuilderPageService::class)->searchOptions($query);
    }

    public function loadBuilderPages(int|array $ids): array
    {
        return app(CmsBuilderPageService::class)->loadOptions($ids);
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
            ['value' => 'url', 'label' => __('cms::labels.type_url')],
            ['value' => 'page', 'label' => __('cms::labels.type_page')],
            ['value' => 'builder_page', 'label' => __('cms::labels.type_builder_page')],
            ['value' => 'section', 'label' => __('cms::labels.type_section')],
            ['value' => 'category', 'label' => __('cms::labels.type_category')],
            ['value' => 'article', 'label' => __('cms::labels.type_article')],
            ['value' => 'album', 'label' => __('cms::labels.type_album')],
            ['value' => 'album_image', 'label' => __('cms::labels.type_album_image')],
            ['value' => 'album_list', 'label' => __('cms::labels.type_album_list')],
            ['value' => 'download', 'label' => __('cms::labels.type_download')],
            ['value' => 'download_list', 'label' => __('cms::labels.type_download_list')],
        ];
    }

    #[Computed()]
    public function parentOptions(): array
    {
        $options = [['value' => '', 'label' => '— '.__('cms::labels.no_parent').' —']];
        if (! $this->editingId) {
            return $options;
        }

        $allItems = $this->allItems;
        $rootIds = $allItems->whereNull('parent_id')->pluck('id');

        // Level-0 items can be parents (for level-1 children)
        foreach ($allItems->whereNull('parent_id')->where('id', '!=', $this->editingId) as $item) {
            $options[] = ['value' => $item->id, 'label' => $item->label];
        }

        // Level-1 items can also be parents (for level-2 grandchildren)
        foreach ($allItems->whereNotNull('parent_id')->where('id', '!=', $this->editingId) as $item) {
            if ($rootIds->contains($item->parent_id)) {
                $parentItem = $allItems->firstWhere('id', $item->parent_id);
                $parentLabel = $parentItem !== null ? $parentItem->label : '';
                $options[] = ['value' => $item->id, 'label' => $parentLabel.' › '.$item->label];
            }
        }

        return $options;
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.menu_list'), icon: 'ph ph-list', url: backend_route('cms.menu.index'))
            ->add(label: $this->menu->name)
            ->add(label: __('cms::labels.manage_items'));
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
