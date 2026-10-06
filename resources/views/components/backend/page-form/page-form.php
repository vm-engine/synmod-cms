<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\PageFormObject;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Cms\Traits\WithAccessPicker;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithAccessPicker;

    public PageFormObject $form;

    #[Locked]
    public ?int $pageId = null;

    #[Locked]
    public ?int $originalParentId = null;

    public ?int $lockedParentId = null;

    public bool $parentLocked = false;

    #[Url()]
    public ?int $parentId = null;

    public function mount(?int $id = null): void
    {
        $this->pageId = $id;

        $access = app(CmsAccessService::class);
        $service = app(CmsPageService::class);

        if ($id) {
            $page = $service->findOrFailById($id);
            $access->authorizeManage($page, 'cms.page.update');
            $this->originalParentId = $page->parent_id;
            $this->form->setPage($page);
        } else {
            // New page: lock parent to whatever was pre-selected (or null for root pages)
            $this->parentLocked = true;
            $parent = $this->parentId ? $service->findOrFailById($this->parentId) : null;
            if ($parent !== null) {
                $access->authorizeManage($parent, 'cms.page.create');
                $this->form->parent_id = $parent->id;
                $this->lockedParentId = $parent->id;
            }
            $this->form->initAccessForNew($parent);
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
        $this->authorizeSave();

        if ($this->pageId) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.page_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.page_created'));
        }
        $this->redirect(backend_route('cms.page.index'), navigate: true);
    }

    /**
     * Re-check on every save: the page itself, and the parent when it is new or changed.
     */
    private function authorizeSave(): void
    {
        $access = app(CmsAccessService::class);
        $service = app(CmsPageService::class);

        if ($this->pageId) {
            $access->authorizeManage($service->findOrFailById($this->pageId), 'cms.page.update');
        }

        $parentId = $this->form->parent_id;
        if ($parentId !== null && ($this->pageId === null || $parentId !== $this->originalParentId)) {
            $access->authorizeManage($service->findOrFailById($parentId), $this->pageId ? 'cms.page.update' : 'cms.page.create');
        }
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function parentOptions(): array
    {
        $access = app(CmsAccessService::class);
        $service = app(CmsPageService::class);

        return array_values(array_filter(
            $service->getParentOptions($this->pageId),
            fn (array $option) => $option['value'] === '' || (int) $option['value'] === $this->form->parent_id
                || $access->canManage($service->findOrFailById((int) $option['value']), 'cms.page.create'),
        ));
    }

    #[Computed()]
    public function inheritedVisibility(): ?string
    {
        $parent = $this->form->parent_id ? app(CmsPageService::class)->findById($this->form->parent_id) : null;

        return $parent ? app(CmsAccessService::class)->effectiveVisibility($parent)->label() : null;
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
