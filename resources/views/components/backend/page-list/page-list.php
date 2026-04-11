<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsActivityLogger;
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
            $id = CmsPage::validateDeleteToken($token);
            if (! $id) {
                throw new Exception(__('cms::labels.invalid_delete_token'));
            }
            $page = CmsPage::findOrFail($id);
            app(CmsActivityLogger::class)->logDelete('page', $page);
            $page->delete();
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
        if (! empty($this->q) && strlen($this->q) > 2) {
            return CmsPage::search($this->q)
                ->when(! empty($this->filterStatus), fn ($q) => $q->where('status', $this->filterStatus))
                ->orderBy('full_slug')
                ->get();
        }

        return CmsPage::roots()
            ->when(! empty($this->filterStatus), fn ($q) => $q->where('status', $this->filterStatus))
            ->with([
                'children' => fn ($q) => $q->orderBy('sort_order'),
                'children.children' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->orderBy('sort_order')
            ->get();
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
            '' => '-- All Status --',
            'draft' => 'Draft',
            'published' => 'Published',
            'scheduled' => 'Scheduled',
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
