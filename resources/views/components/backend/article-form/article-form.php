<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\ArticleFormObject;
use VmEngine\Cms\Services\CmsArticleService;
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
            $article = app(CmsArticleService::class)->findArticleOrFailById($id, ['tags']);
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
        return app(CmsArticleService::class)->searchTagOptions($query);
    }

    public function loadTags(array $ids): array
    {
        return app(CmsArticleService::class)->loadTagOptions($ids);
    }

    #[Computed()]
    public function sectionName(): string
    {
        return app(CmsArticleService::class)->findSectionById($this->sectionId)?->title ?? '';
    }

    #[Computed()]
    public function categoryOptions(): array
    {
        return app(CmsArticleService::class)->getCategoryOptions(
            sectionId: $this->sectionId,
            emptyLabel: '-- No Category --',
        );
    }

    #[Computed()]
    public function tagOptions(): array
    {
        return app(CmsArticleService::class)->getTagSuggestionMap();
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
