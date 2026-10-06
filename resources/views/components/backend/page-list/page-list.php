<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Cms\Traits\WithCmsAccessChecks;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\Synapse\Traits\RemembersQueryParams;

new class extends Component
{
    use RemembersQueryParams, WithCmsAccessChecks;

    #[Url()]
    public string $q = '';

    #[Url()]
    public string $filterStatus = '';

    public function mount(): void
    {
        synav()->setActiveMenu('cms.page');
    }

    protected function rememberedParams(): array
    {
        return ['q', 'filterStatus'];
    }

    public function title(): string
    {
        return __('cms::labels.page_list');
    }

    public function delete(string $token): void
    {
        try {
            app(CmsPageService::class)->deleteByToken(
                $token,
                fn (CmsPage $page) => app(CmsAccessService::class)->authorizeManage($page, 'cms.page.delete'),
            );
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.page_deleted'));
        unset($this->pageList);
    }

    /**
     * Persist a drag-sorted sibling group; $parentId null = root pages.
     *
     * @param  array<int, int|string>  $ids
     */
    public function updateOrder(?int $parentId, array $ids): void
    {
        $service = app(CmsPageService::class);
        abort_unless(($parentId !== null || is_dev()) && $this->canSortGroup($service->siblingsOf($parentId)), 403);

        $service->reorderChildren($parentId, array_map('intval', $ids));
        unset($this->pageList, $this->sortableGroups);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.order_saved'));
    }

    /**
     * A sibling group is sortable when it has 2+ pages and the user may update every one.
     *
     * @param  iterable<int, CmsPage>  $siblings
     */
    protected function canSortGroup(iterable $siblings): bool
    {
        $siblings = collect($siblings);

        return $siblings->count() > 1
            && $siblings->every(fn (CmsPage $page) => $this->canManage($page, 'cms.page.update'));
    }

    /**
     * Which sibling groups of the tree get drag handles, keyed by parent id or 'root'.
     * Root pages are dev-only (rarely reordered) and locked while a status filter hides some of them.
     *
     * @return array<int|string, bool>
     */
    #[Computed()]
    public function sortableGroups(): array
    {
        if ($this->isSearching) {
            return [];
        }

        $groups = ['root' => $this->filterStatus === '' && is_dev() && $this->canSortGroup($this->pageList)];

        foreach ($this->pageList as $root) {
            $groups[$root->id] = $this->canSortGroup($root->children);

            foreach ($root->children as $child) {
                $groups[$child->id] = $this->canSortGroup($child->children);
            }
        }

        return $groups;
    }

    #[Computed()]
    public function pageList(): Collection
    {
        return app(CmsPageService::class)->listForBackend(
            q: $this->q,
            status: $this->filterStatus !== '' ? $this->filterStatus : null,
        );
    }

    #[Computed()]
    public function isSearching(): bool
    {
        return ! empty($this->q) && strlen($this->q) > 2;
    }

    #[Computed()]
    public function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => '-- All Status --'],
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'published', 'label' => 'Published'],
            ['value' => 'scheduled', 'label' => 'Scheduled'],
        ];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(
            label: __('cms::labels.page_list'),
            icon: 'fa-solid fa-file-lines',
        );
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
