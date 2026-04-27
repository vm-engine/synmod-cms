<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\SectionFormObject;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public SectionFormObject $form;

    public ?int $sectionId = null;

    public function mount(?int $id = null): void
    {
        $this->sectionId = $id;

        if ($id) {
            $section = app(CmsArticleService::class)->findSectionOrFailById($id);
            $this->form->setSection($section);
        }

        synav()->setActiveMenu('cms.section');
    }

    public function title(): string
    {
        return $this->sectionId
            ? __('cms::labels.edit_section')
            : __('cms::labels.add_section');
    }

    public function save(): void
    {
        if ($this->sectionId) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.section_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.section_created'));
        }
        $this->redirect(backend_route('cms.section.index'), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'fa-solid fa-layer-group', url: backend_route('cms.section.index'))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
