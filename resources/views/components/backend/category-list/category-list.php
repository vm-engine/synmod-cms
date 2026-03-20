<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithPagination;

    public int $sectionId;

    #[Url()]
    public string $q = '';

    #[Url()]
    public string $filterStatus = '';

    #[Url()]
    public int $limit = 15;

    #[Url()]
    public string $sort = 'sort_order';

    #[Url()]
    public string $sortDirection = 'asc';

    public function mount(int $sectionId): void
    {
        $this->sectionId = $sectionId;
        synav()->setActiveMenu('cms.section');
    }

    public function title(): string
    {
        return __('cms::labels.category_list');
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
            $id = CmsCategory::validateDeleteToken($token);
            if (! $id) {
                throw new Exception(__('cms::labels.invalid_delete_token'));
            }
            CmsCategory::findOrFail($id)->delete();
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.category_deleted'));
        unset($this->categoryList);
    }

    #[Computed()]
    public function categoryList()
    {
        $model = CmsCategory::with('section')->withCount('articles')
            ->where('section_id', $this->sectionId);

        if (! empty($this->q) && strlen($this->q) > 2) {
            $model->search($this->q);
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
    public function section(): ?CmsSection
    {
        return CmsSection::find($this->sectionId);
    }

    #[Computed()]
    public function statusOptions(): array
    {
        return ['' => '-- All --', 'draft' => 'Draft', 'published' => 'Published'];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        $bc = Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'fa-solid fa-layer-group', url: backend_route('cms.section.index'));
        if ($this->section) {
            $bc->add(label: $this->section->title, url: backend_route('cms.section.index'));
        }

        return $bc->add(label: __('cms::labels.category_list'));
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
