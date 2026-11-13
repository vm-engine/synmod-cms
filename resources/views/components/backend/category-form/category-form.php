<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\CategoryFormObject;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public CategoryFormObject $form;

    #[Locked]
    public ?int $categoryId = null;

    #[Locked]
    public int $sectionId;

    public function mount(int $sectionId, ?int $id = null): void
    {
        $this->sectionId = $sectionId;
        $this->categoryId = $id;

        $this->authorizeSection($id ? 'cms.article.category.update' : 'cms.article.category.create');

        if ($id) {
            $category = app(CmsArticleService::class)->findCategoryOrFailById($id);
            abort_unless($category->section_id === $sectionId, 404);
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
        $this->authorizeSection($this->categoryId ? 'cms.article.category.update' : 'cms.article.category.create');
        $this->form->section_id = $this->sectionId;

        if ($this->categoryId) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.category_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.category_created'));
        }
        $this->redirect(backend_route('cms.category.index', ['sectionId' => $this->sectionId]), navigate: true);
    }

    /** Categories are managed by whoever manages their section. */
    private function authorizeSection(string $ability): void
    {
        app(CmsAccessService::class)->authorizeManage(
            app(CmsArticleService::class)->findSectionOrFailById($this->sectionId),
            $ability,
        );
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function sectionName(): string
    {
        return app(CmsArticleService::class)->findSectionById($this->sectionId)?->title ?? '';
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'ph ph-stack', url: backend_route('cms.section.index'))
            ->add(label: $this->sectionName, url: backend_route('cms.category.index', ['sectionId' => $this->sectionId]))
            ->add(label: __('cms::labels.category_list'), url: backend_route('cms.category.index', ['sectionId' => $this->sectionId]))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
