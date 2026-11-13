<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Traits\WithCmsAccessChecks;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithCmsAccessChecks, WithPagination;

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
            app(CmsArticleService::class)->deleteCategoryByToken(
                $token,
                fn (CmsCategory $item) => app(CmsAccessService::class)->authorizeManage($item, 'cms.article.category.delete'),
            );
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
        return app(CmsArticleService::class)->paginateCategoriesInSection(
            sectionId: $this->sectionId,
            filters: ['q' => $this->q, 'status' => $this->filterStatus],
            sort: $this->sort,
            direction: $this->sortDirection,
            perPage: $this->limit,
        );
    }

    #[Computed()]
    public function section(): ?CmsSection
    {
        return app(CmsArticleService::class)->findSectionById($this->sectionId);
    }

    #[Computed()]
    public function statusOptions(): array
    {
        return [
            ['value' => '', 'label' => '-- All --'],
            ['value' => 'draft', 'label' => 'Draft'],
            ['value' => 'published', 'label' => 'Published'],
        ];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        $bc = Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'ph ph-stack', url: backend_route('cms.section.index'));
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
