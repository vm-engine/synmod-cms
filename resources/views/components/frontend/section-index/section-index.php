<?php

declare(strict_types=1);

use Livewire\Component;
use Livewire\WithPagination;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    use WithPagination;

    public int $section_id;

    public ?CmsSection $section = null;

    public function mount(int $section_id): void
    {
        $this->section_id = $section_id;
        $this->section = app(CmsArticleService::class)->findPublishedSectionOrFailById(
            $section_id,
            ['categories'],
        );

        app(CmsAccessService::class)->authorizeView($this->section);
    }

    public function title(): string
    {
        return $this->section?->translatedMetaTitle() ?? '';
    }

    public function description(): ?string
    {
        return $this->section?->translatedMetaDescription();
    }

    public function render()
    {
        $config = $this->section?->config ?? [];
        $perPage = $config['items_per_page'] ?? 12;
        $orderBy = $config['order_by'] ?? 'published_at';
        $orderDir = $config['order_dir'] ?? 'desc';

        $articles = app(CmsArticleService::class)->paginatePublishedArticlesInSection(
            sectionId: $this->section_id,
            orderBy: $orderBy,
            orderDir: $orderDir,
            perPage: $perPage,
        );

        $template = FrontendViewResolver::view(
            $this->section?->resolveCustomView(),
            'cms::components.frontend.section-index.section-index',
        );

        return view($template, ['section' => $this->section, 'articles' => $articles])
            ->title($this->title())
            ->layoutData(['description' => $this->description()])
            ->layout(FrontendViewResolver::layout($this->section?->resolveCustomLayout()));
    }
};
