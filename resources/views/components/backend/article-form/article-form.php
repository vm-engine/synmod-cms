<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\ArticleFormObject;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Traits\WithAccessPicker;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithAccessPicker;

    public ArticleFormObject $form;

    #[Locked]
    public ?int $articleId = null;

    #[Locked]
    public int $sectionId;

    public function mount(int $sectionId, ?int $id = null): void
    {
        $this->sectionId = $sectionId;
        $this->articleId = $id;

        $service = app(CmsArticleService::class);
        $access = app(CmsAccessService::class);
        $section = $service->findSectionOrFailById($sectionId);

        if ($id) {
            $article = $service->findArticleOrFailById($id, ['tags']);
            abort_unless($article->section_id === $sectionId, 404);
            $access->authorizeManage($article, 'cms.article.update');
            $this->form->setArticle($article);
        } else {
            $access->authorizeManage($section, 'cms.article.create');
            $this->form->section_id = $sectionId;
            $this->form->initAccessForNew($section);
            $this->form->fillCustomFields(new CmsArticle(['section_id' => $sectionId]));
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
        $this->authorizeSave();

        if ($this->articleId) {
            $this->form->update(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.article_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.article_created'));
        }
        $this->redirect(backend_route('cms.section.article.index', ['sectionId' => $this->sectionId]), navigate: true);
    }

    /** Re-check on save; the article always stays in the route's section. */
    private function authorizeSave(): void
    {
        $service = app(CmsArticleService::class);
        $access = app(CmsAccessService::class);

        if ($this->articleId) {
            $article = $service->findArticleOrFailById($this->articleId);
            abort_unless($article->section_id === $this->sectionId, 404);
            $access->authorizeManage($article, 'cms.article.update');
        } else {
            $access->authorizeManage($service->findSectionOrFailById($this->sectionId), 'cms.article.create');
        }

        $this->form->section_id = $this->sectionId;
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

    /** @return list<array{value: string, label: string}> */
    #[Computed()]
    public function displayOverrideOptions(): array
    {
        return array_map(
            fn (string $key) => ['value' => $key, 'label' => __('cms::labels.display_override_'.$key)],
            array_keys(ArticleFormObject::DISPLAY_OVERRIDES),
        );
    }

    #[Computed()]
    public function inheritedVisibility(): ?string
    {
        $section = app(CmsArticleService::class)->findSectionById($this->sectionId);

        return $section ? app(CmsAccessService::class)->effectiveVisibility($section)->label() : null;
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

    /** Whether the article's section has media galleries on (manager, or a save-first hint). */
    #[Computed()]
    public function mediaGalleriesAvailable(): bool
    {
        return (new CmsArticle(['section_id' => $this->form->section_id]))->mediaGalleriesEnabled();
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.section_list'), icon: 'ph ph-stack', url: backend_route('cms.section.index'))
            ->add(label: $this->sectionName, url: backend_route('cms.section.article.index', ['sectionId' => $this->sectionId]))
            ->add(label: __('cms::labels.article_list'), url: backend_route('cms.section.article.index', ['sectionId' => $this->sectionId]))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
