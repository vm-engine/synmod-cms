<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\CategoryFormObject;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public CategoryFormObject $form;

    public ?int $categoryId = null;

    public int $sectionId;

    public function mount(int $sectionId, ?int $id = null): void
    {
        $this->sectionId = $sectionId;
        $this->categoryId = $id;

        if ($id) {
            $category = CmsCategory::findOrFail($id);
            $this->form->setCategory($category);
        } else {
            $this->form->section_id = $sectionId;
        }

        synav()->setActiveMenu('cms.section');
    }

    public function title(): string
    {
        return $this->categoryId ? __('cms::labels.edit_category') : __('cms::labels.add_category');
    }

    public function save(): void
    {
        if ($this->categoryId) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.category_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.category_created'));
        }
        $this->redirect(backend_route('cms.category.index', ['sectionId' => $this->sectionId]), navigate: true);
    }

    #[Computed()]
    public function sectionName(): string
    {
        return CmsSection::find($this->sectionId)?->title ?? '';
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'fa-solid fa-layer-group', url: backend_route('cms.section.index'))
            ->add(label: $this->sectionName, url: backend_route('cms.category.index', ['sectionId' => $this->sectionId]))
            ->add(label: __('cms::labels.category_list'), url: backend_route('cms.category.index', ['sectionId' => $this->sectionId]))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
