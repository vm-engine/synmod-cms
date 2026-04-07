<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Models\CmsMenuItem;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
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

    public function mount(int $menuId): void
    {
        $this->menuId = $menuId;
        CmsMenu::findOrFail($menuId);
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
        $this->reset('newLabel', 'newTargetId', 'newTargetUrl', 'newOpenNewTab');
        $this->newTargetType = 'url';
        $this->addingChildFor = $parentId;
    }

    public function cancelAddChild(): void
    {
        $this->reset('newLabel', 'newTargetId', 'newTargetUrl', 'newOpenNewTab');
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
        ]);

        $parentId = $this->addingChildFor;
        $maxOrder = CmsMenuItem::where('menu_id', $this->menuId)
            ->where('parent_id', $parentId)
            ->max('sort_order') ?? -1;

        CmsMenuItem::create([
            'menu_id' => $this->menuId,
            'parent_id' => $parentId,
            'label' => $this->newLabel,
            'target_type' => $this->newTargetType,
            'target_id' => ! in_array($this->newTargetType, $noIdTypes) ? $this->newTargetId : null,
            'target_url' => in_array($this->newTargetType, ['url', 'album_list', 'download_list']) ? ($this->newTargetUrl ?: null) : null,
            'open_new_tab' => $this->newOpenNewTab,
            'sort_order' => $maxOrder + 1,
            'is_active' => true,
        ]);

        $this->reset('newLabel', 'newTargetId', 'newTargetUrl', 'newOpenNewTab');
        $this->newTargetType = 'url';
        $this->addingChildFor = null;
        unset($this->items);
    }

    public function startEdit(int $id): void
    {
        $item = CmsMenuItem::findOrFail($id);
        $this->addingChildFor = null;
        $this->editingId = $id;
        $this->editLabel = $item->label;
        $this->editTargetType = $item->target_type;
        $this->editTargetId = $item->target_id;
        $this->editTargetUrl = $item->target_url ?? '';
        $this->editOpenNewTab = $item->open_new_tab;
        $this->editParentId = $item->parent_id;
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
        ]);

        CmsMenuItem::findOrFail($this->editingId)->update([
            'label' => $this->editLabel,
            'target_type' => $this->editTargetType,
            'target_id' => ! in_array($this->editTargetType, $noIdTypes) ? $this->editTargetId : null,
            'target_url' => in_array($this->editTargetType, ['url', 'album_list', 'download_list']) ? ($this->editTargetUrl ?: null) : null,
            'open_new_tab' => $this->editOpenNewTab,
            'parent_id' => $this->editParentId,
        ]);

        $this->reset('editingId', 'editLabel', 'editTargetType', 'editTargetId', 'editTargetUrl', 'editOpenNewTab', 'editParentId');
        unset($this->items);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.item_updated'));
    }

    public function cancelEdit(): void
    {
        $this->reset('editingId', 'editLabel', 'editTargetType', 'editTargetId', 'editTargetUrl', 'editOpenNewTab', 'editParentId');
    }

    public function deleteItem(int $id): void
    {
        CmsMenuItem::where('menu_id', $this->menuId)->findOrFail($id)->delete();
        unset($this->items);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.item_deleted'));
    }

    public function updateOrder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            CmsMenuItem::where('menu_id', $this->menuId)
                ->where('id', $id)
                ->update(['sort_order' => $order]);
        }
        unset($this->items);
    }

    // ── Content search / load methods for adv-select ────────────────────────

    public function searchPages(string $query): array
    {
        return CmsPage::where('title', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'title', 'full_slug'])
            ->map(fn ($p) => ['value' => $p->id, 'label' => $p->title.' (/'.$p->full_slug.')'])
            ->toArray();
    }

    public function loadPages(int|array $ids): array
    {
        return CmsPage::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn ($p) => ['value' => $p->id, 'label' => $p->title])
            ->toArray();
    }

    public function searchSections(string $query): array
    {
        return CmsSection::where('title', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'title'])
            ->map(fn ($s) => ['value' => $s->id, 'label' => $s->title])
            ->toArray();
    }

    public function loadSections(int|array $ids): array
    {
        return CmsSection::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn ($s) => ['value' => $s->id, 'label' => $s->title])
            ->toArray();
    }

    public function searchCategories(string $query): array
    {
        return CmsCategory::with('section')
            ->where('title', 'like', "%{$query}%")
            ->limit(20)
            ->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->section->title.' › '.$c->title])
            ->toArray();
    }

    public function loadCategories(int|array $ids): array
    {
        return CmsCategory::with('section')
            ->whereIn('id', (array) $ids)
            ->get()
            ->map(fn ($c) => ['value' => $c->id, 'label' => $c->section->title.' › '.$c->title])
            ->toArray();
    }

    public function searchArticles(string $query): array
    {
        return CmsArticle::with(['section', 'category'])
            ->where('title', 'like', "%{$query}%")
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'value' => $a->id,
                'label' => $a->section->title.($a->category ? ' › '.$a->category->title : '').' › '.$a->title,
            ])
            ->toArray();
    }

    public function loadArticles(int|array $ids): array
    {
        return CmsArticle::with(['section', 'category'])
            ->whereIn('id', (array) $ids)
            ->get()
            ->map(fn ($a) => [
                'value' => $a->id,
                'label' => $a->section->title.($a->category ? ' › '.$a->category->title : '').' › '.$a->title,
            ])
            ->toArray();
    }

    public function searchAlbums(string $query): array
    {
        return CmsAlbum::where('title', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'title'])
            ->map(fn ($a) => ['value' => $a->id, 'label' => $a->title])
            ->toArray();
    }

    public function loadAlbums(int|array $ids): array
    {
        return CmsAlbum::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn ($a) => ['value' => $a->id, 'label' => $a->title])
            ->toArray();
    }

    public function searchAlbumImages(string $query): array
    {
        return CmsAlbumImage::with('album')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhereHas('album', fn ($aq) => $aq->where('title', 'like', "%{$query}%"));
            })
            ->limit(20)
            ->get()
            ->map(fn ($i) => [
                'value' => $i->id,
                'label' => $i->album->title.' › '.($i->title ?: "Image #{$i->id}"),
            ])
            ->toArray();
    }

    public function loadAlbumImages(int|array $ids): array
    {
        return CmsAlbumImage::with('album')
            ->whereIn('id', (array) $ids)
            ->get()
            ->map(fn ($i) => [
                'value' => $i->id,
                'label' => $i->album->title.' › '.($i->title ?: "Image #{$i->id}"),
            ])
            ->toArray();
    }

    public function searchDownloads(string $query): array
    {
        return CmsDownload::where('title', 'like', "%{$query}%")
            ->limit(20)
            ->get(['id', 'title'])
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->title])
            ->toArray();
    }

    public function loadDownloads(int|array $ids): array
    {
        return CmsDownload::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn ($d) => ['value' => $d->id, 'label' => $d->title])
            ->toArray();
    }

    // ── Computed ─────────────────────────────────────────────────────────────

    #[Computed()]
    public function menu(): CmsMenu
    {
        return CmsMenu::findOrFail($this->menuId);
    }

    #[Computed()]
    public function items()
    {
        return CmsMenuItem::where('menu_id', $this->menuId)
            ->whereNull('parent_id')
            ->with('children.children')
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed()]
    public function allItems()
    {
        return CmsMenuItem::where('menu_id', $this->menuId)->orderBy('sort_order')->get();
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
