<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;
use VmEngine\Synapse\Traits\RemembersQueryParams;

new class extends Component
{
    use RemembersQueryParams;

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
            app(CmsPageService::class)->deleteByToken($token);
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.page_deleted'));
        unset($this->pageList);
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
