<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsSearchTerm;
use VmEngine\Cms\Services\CmsSearchTermService;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithPagination;

    #[Url]
    public string $q = '';

    #[Url]
    public string $locale = '';

    #[Url]
    public int $perPage = 15;

    public function mount(): void
    {
        synav()->setActiveMenu('cms.search_term');
    }

    public function title(): string
    {
        return __('cms::labels.search_term_list');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    public function toggleHidden(int $id): void
    {
        $this->authorizeAcl('cms.search_term.update');
        app(CmsSearchTermService::class)->toggleHidden($id);
        unset($this->terms);
    }

    public function delete(string $token): void
    {
        $this->authorizeAcl('cms.search_term.delete');
        $id = CmsSearchTerm::validateDeleteToken($token);

        if ($id === false) {
            $this->dispatch('synapse-confirmed');
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::labels.invalid_delete_token'));

            return;
        }

        app(CmsSearchTermService::class)->delete((int) $id);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.search_term_deleted'));
        unset($this->terms);
    }

    /**
     * @return LengthAwarePaginator<int, CmsSearchTerm>
     */
    #[Computed]
    public function terms(): LengthAwarePaginator
    {
        return app(CmsSearchTermService::class)->paginate(['q' => $this->q, 'locale' => $this->locale], $this->perPage);
    }

    /**
     * @return list<string>
     */
    #[Computed]
    public function locales(): array
    {
        return CmsLocale::all();
    }

    #[Computed]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.search_term_list'), icon: 'ph ph-magnifying-glass');
    }

    private function authorizeAcl(string $acl): void
    {
        $user = auth()->user();
        abort_unless($user instanceof Authorizable && $user->can($acl), 403);
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
