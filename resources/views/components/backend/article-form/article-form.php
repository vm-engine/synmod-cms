<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\ArticleFormObject;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Models\CmsTag;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public ArticleFormObject $form;

    public ?int $articleId = null;

    public int $sectionId;

    public function mount(int $sectionId, ?int $id = null): void
    {
        $this->sectionId = $sectionId;
        $this->articleId = $id;

        if ($id) {
            $article = CmsArticle::with(['tags'])->findOrFail($id);
            $this->form->setArticle($article);
        } else {
            $this->form->section_id = $sectionId;
        }

        synav()->setActiveMenu('cms.section');
    }

    public function title(): string
    {
        return $this->articleId
            ? __('cms::labels.edit_article')
            : __('cms::labels.add_article');
    }

    public function save(): void
    {
        if ($this->articleId) {
            $this->form->update(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.article_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.article_created'));
        }
        $this->redirect(backend_route('cms.section.article.index', ['sectionId' => $this->sectionId]), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    public function searchTags(string $query): array
    {
        return CmsTag::where('name', 'like', "%{$query}%")
            ->orderBy('name')
            ->limit(20)
            ->get()
            ->map(fn ($t) => ['value' => $t->id, 'label' => $t->name])
            ->toArray();
    }

    public function loadTags(array $ids): array
    {
        $intIds = array_filter($ids, 'is_numeric');

        return CmsTag::whereIn('id', $intIds)
            ->get()
            ->map(fn ($t) => ['value' => $t->id, 'label' => $t->name])
            ->toArray();
    }

    #[Computed()]
    public function sectionName(): string
    {
        return CmsSection::find($this->sectionId)?->title ?? '';
    }

    #[Computed()]
    public function categoryOptions(): array
    {
        $options = ['' => '-- No Category --'];
        foreach (CmsCategory::where('section_id', $this->sectionId)->orderBy('title')->get() as $c) {
            $options[$c->id] = $c->title;
        }

        return $options;
    }

    #[Computed()]
    public function tagOptions(): array
    {
        return CmsTag::orderBy('name')->pluck('name', 'id')->toArray();
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'fa-solid fa-layer-group', url: backend_route('cms.section.index'))
            ->add(label: $this->sectionName, url: backend_route('cms.section.article.index', ['sectionId' => $this->sectionId]))
            ->add(label: __('cms::labels.article_list'), url: backend_route('cms.section.article.index', ['sectionId' => $this->sectionId]))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
