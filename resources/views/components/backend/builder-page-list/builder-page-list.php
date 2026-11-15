<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\Cms\Services\CmsBuilderPageService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithPagination;

    #[Url()]
    public string $q = '';

    #[Url()]
    public int $limit = 20;

    public function mount(): void
    {
        synav()->setActiveMenu('cms.builder');
    }

    public function title(): string
    {
        return __('cms::builder.list');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function delete(string $token): void
    {
        $user = auth()->user();
        if (! ($user instanceof Authorizable && $user->can('cms.builder.delete'))) {
            abort(403);
        }

        try {
            app(CmsBuilderPageService::class)->deleteByToken($token);
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::builder.deleted'));
        unset($this->pages);
    }

    /**
     * @return LengthAwarePaginator<int, CmsBuilderPage>
     */
    #[Computed()]
    public function pages(): LengthAwarePaginator
    {
        return app(CmsBuilderPageService::class)->paginate(['q' => $this->q], $this->limit);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::builder.list'), icon: 'ph ph-layout');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
