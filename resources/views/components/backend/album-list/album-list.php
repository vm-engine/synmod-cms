<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\Synapse\Traits\RemembersQueryParams;

new class extends Component
{
    use RemembersQueryParams, WithPagination;

    #[Url()]
    public string $q = '';

    #[Url()]
    public string $filterType = '';

    #[Url()]
    public string $filterStatus = '';

    #[Url()]
    public int $limit = 15;

    #[Url()]
    public string $sort = 'sort_order';

    #[Url()]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        synav()->setActiveMenu('cms.gallery');
    }

    protected function rememberedParams(): array
    {
        return ['q', 'filterType', 'filterStatus'];
    }

    public function title(): string
    {
        return __('cms::labels.album_list');
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
            $id = CmsAlbum::validateDeleteToken($token);
            if (! $id) {
                throw new Exception(__('cms::labels.invalid_delete_token'));
            }
            $album = CmsAlbum::findOrFail($id);
            $album->delete();
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.album_deleted'));
        unset($this->albumList);
    }

    #[Computed()]
    public function albumList()
    {
        $model = CmsAlbum::withCount('images');

        if (! empty($this->q) && strlen($this->q) > 2) {
            $model->search($this->q);
        }

        if (! empty($this->filterType)) {
            $model->where('type', $this->filterType);
        }

        if (! empty($this->filterStatus)) {
            $model->where('status', $this->filterStatus);
        }

        return $model
            ->orderBy($this->sort, $this->sortDirection)
            ->paginate($this->limit)
            ->onEachSide(1);
    }

    #[Computed()]
    public function typeOptions(): array
    {
        return [
            '' => '-- All Types --',
            'normal' => 'Normal Gallery',
            'banner' => 'Banner Slider',
        ];
    }

    #[Computed()]
    public function statusOptions(): array
    {
        return [
            '' => '-- All --',
            'active' => 'Active',
            'inactive' => 'Inactive',
        ];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.album_list'), icon: 'fa-solid fa-images');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
