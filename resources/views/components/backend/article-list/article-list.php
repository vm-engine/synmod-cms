<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\Synapse\Traits\RemembersQueryParams;

new class extends Component
{
    use RemembersQueryParams, WithPagination;

    public ?int $sectionId = null;

    #[Url()]
    public string $q = '';

    #[Url()]
    public string $filterStatus = '';

    #[Url()]
    public ?int $filterSection = null;

    #[Url()]
    public ?int $filterCategory = null;

    #[Url()]
    public int $limit = 15;

    #[Url()]
    public string $sort = 'created_at';

    #[Url()]
    public string $sortDirection = 'desc';

    public function mount(?int $sectionId = null): void
    {
        $this->sectionId = $sectionId;
        if ($sectionId) {
            $this->filterSection = $sectionId;
        }
        synav()->setActiveMenu('cms.section');
    }

    protected function rememberedParams(): array
    {
        return ['q', 'filterStatus', 'filterSection', 'filterCategory'];
    }

    public function title(): string
    {
        return __('cms::labels.article_list');
    }

    public function updated(string $property): void
    {
        if ($property === 'filterSection') {
            $this->filterCategory = null;
            unset($this->categoryOptions);
        }
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
            app(CmsArticleService::class)->deleteArticleByToken($token);
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.article_deleted'));
        unset($this->articleList);
    }

    #[Computed()]
    public function articleList()
    {
        return app(CmsArticleService::class)->paginateArticles(
            filters: [
                'q' => $this->q,
                'status' => $this->filterStatus,
                'sectionId' => $this->sectionId ?: $this->filterSection,
                'categoryId' => $this->filterCategory,
            ],
            sort: $this->sort,
            direction: $this->sortDirection,
            perPage: $this->limit,
        );
    }

    #[Computed()]
    public function section(): ?CmsSection
    {
        return $this->sectionId ? app(CmsArticleService::class)->findSectionById($this->sectionId) : null;
    }

    #[Computed()]
    public function sectionOptions(): array
    {
        return app(CmsArticleService::class)->getSectionOptions();
    }

    #[Computed()]
    public function categoryOptions(): array
    {
        return app(CmsArticleService::class)->getCategoryOptions(
            sectionId: $this->sectionId ?? $this->filterSection,
        );
    }

    #[Computed()]
    public function statusOptions(): array
    {
        return [
            '' => '-- All --',
            'draft' => 'Draft',
            'published' => 'Published',
            'scheduled' => 'Scheduled',
        ];
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        $bc = Breadcrumbs::make(label: __('cms::labels.article_list'), icon: 'fa-solid fa-newspaper');
        if ($this->section) {
            $bc = Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'fa-solid fa-layer-group', url: backend_route('cms.section.index'))
                ->add(label: $this->section->title, url: backend_route('cms.section.index'))
                ->add(label: __('cms::labels.article_list'));
        }

        return $bc;
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
