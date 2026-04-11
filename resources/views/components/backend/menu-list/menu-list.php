<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Livewire\Forms\MenuFormObject;
use VmEngine\Cms\Models\CmsMenu;
use VmEngine\Cms\Services\CmsActivityLogger;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithPagination;

    public MenuFormObject $form;

    #[Url()]
    public string $q = '';

    #[Url()]
    public int $limit = 15;

    public function mount(): void
    {
        synav()->setActiveMenu('cms.menu');
    }

    public function title(): string
    {
        return __('cms::labels.menu_list');
    }

    public function updated(): void
    {
        $this->resetPage();
    }

    #[On('load-menu')]
    public function loadMenu(int $id): void
    {
        $menu = CmsMenu::findOrFail($id);
        $this->form->setMenu($menu);
    }

    #[On('reset-menu-form')]
    public function resetForm(): void
    {
        $this->form->reset();
    }

    public function save(): void
    {
        if ($this->form->menu) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.menu_updated'));
        } else {
            $this->form->store();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.menu_created'));
        }
        $this->dispatch('close-modal-menu-form');
        unset($this->menuList);
    }

    public function delete(string $token): void
    {
        try {
            $id = CmsMenu::validateDeleteToken($token);
            if (! $id) {
                throw new Exception(__('cms::labels.invalid_delete_token'));
            }
            $menu = CmsMenu::findOrFail($id);
            app(CmsActivityLogger::class)->logDelete('menu', $menu);
            $menu->delete();
        } catch (Exception $e) {
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: $e->getMessage());

            return;
        }

        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.menu_deleted'));
        unset($this->menuList);
    }

    #[Computed()]
    public function menuList()
    {
        $model = CmsMenu::withCount('items');

        if (! empty($this->q) && strlen($this->q) > 2) {
            $model->where('name', 'like', "%{$this->q}%")
                ->orWhere('position', 'like', "%{$this->q}%");
        }

        return $model->paginate($this->limit)->onEachSide(1);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.menu_list'), icon: 'fa-solid fa-bars');
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
