<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\PageFormObject;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public PageFormObject $form;

    public ?int $pageId = null;

    public ?int $lockedParentId = null;

    public bool $parentLocked = false;

    #[Url()]
    public ?int $parentId = null;

    public function mount(?int $id = null): void
    {
        $this->pageId = $id;

        if ($id) {
            $page = CmsPage::findOrFail($id);
            $this->form->setPage($page);
        } else {
            // New page: lock parent to whatever was pre-selected (or null for root pages)
            $this->parentLocked = true;
            if ($this->parentId) {
                $this->form->parent_id = $this->parentId;
                $this->lockedParentId = $this->parentId;
            }
        }

        synav()->setActiveMenu('cms.page');
    }

    public function title(): string
    {
        return $this->pageId
            ? __('cms::labels.edit_page')
            : __('cms::labels.add_page');
    }

    public function save(): void
    {
        if ($this->pageId) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.page_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.page_created'));
        }
        $this->redirect(backend_route('cms.page.index'), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function parentOptions(): array
    {
        $options = ['' => '-- No Parent --'];

        $pages = CmsPage::whereNull('parent_id')
            ->when($this->pageId, fn ($q) => $q->where('id', '!=', $this->pageId))
            ->orderBy('title')
            ->get();

        foreach ($pages as $page) {
            $options[$page->id] = $page->title;
            foreach ($page->children->where('id', '!=', $this->pageId) as $child) {
                $options[$child->id] = '— '.$child->title;
            }
        }

        return $options;
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.page_list'), icon: 'fa-solid fa-file-lines', url: backend_route('cms.page.index'))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
