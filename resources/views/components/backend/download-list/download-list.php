<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\Synapse\Traits\RemembersQueryParams;

new class extends Component
{
    use RemembersQueryParams, WithPagination;

    #[Url()]
    public string $q = '';

    #[Url()]
    public string $filterStatus = '';

    #[Url()]
    public string $filterAccess = '';

    #[Url()]
    public int $limit = 15;

    #[Url()]
    public string $sort = 'sort_order';

    #[Url()]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        synav()->setActiveMenu('cms.download');
    }

    protected function rememberedParams(): array
    {
        return ['q', 'filterStatus', 'filterAccess'];
    }

    public function title(): string
    {
        return __('cms::labels.download_list');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function sortData(string $sort): void
    {
        if ($this->sort === $sort && $this->sortDirection === 'asc') {
            $this->sortDirection = 'desc';
        } elseif ($this->sort === $sort && $this->sortDirection === 'desc') {
            $this->reset('sort', 'sortDirection');

            return;
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sort = $sort;
    }

    public function delete(string $token): void
    {
        try {
            app(CmsDownloadService::class)->deleteDownloadByToken($token);
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.download_deleted'));
        unset($this->downloadList);
    }

    #[Computed()]
    public function downloadList()
    {
        return app(CmsDownloadService::class)->paginate(
            filters: ['q' => $this->q, 'status' => $this->filterStatus, 'access' => $this->filterAccess],
            sort: $this->sort,
            direction: $this->sortDirection,
            perPage: $this->limit,
        );
    }

    #[Computed()]
    public function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => '-- All --'],
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
        ];
    }

    #[Computed()]
    public function accessOptions(): array
    {
        return [
            ['value' => '', 'label' => '-- All --'],
            ['value' => 'public', 'label' => 'Public'],
            ['value' => 'private', 'label' => 'Private'],
        ];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.download_list'), icon: 'fa-solid fa-download');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
