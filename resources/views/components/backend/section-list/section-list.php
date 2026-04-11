<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsActivityLogger;
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
    public int $limit = 15;

    #[Url()]
    public string $sort = 'sort_order';

    #[Url()]
    public string $sortDirection = 'asc';

    public function mount(): void
    {
        synav()->setActiveMenu('cms.section');
    }

    protected function rememberedParams(): array
    {
        return ['q', 'filterStatus'];
    }

    public function title(): string
    {
        return __('cms::labels.section_list');
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
            $id = CmsSection::validateDeleteToken($token);
            if (! $id) {
                throw new Exception(__('cms::labels.invalid_delete_token'));
            }
            $section = CmsSection::findOrFail($id);
            app(CmsActivityLogger::class)->logDelete('section', $section);
            $section->delete();
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.section_deleted'));
        unset($this->sectionList);
    }

    #[Computed()]
    public function sectionList()
    {
        $model = CmsSection::withCount(['categories', 'articles']);

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
    public function statusOptions(): array
    {
        return [
            '' => '-- All --',
            'draft' => 'Draft',
            'published' => 'Published',
        ];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'fa-solid fa-layer-group');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
