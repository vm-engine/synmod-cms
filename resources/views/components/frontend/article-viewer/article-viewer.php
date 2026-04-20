<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Support\FrontendViewResolver;

new class extends Component
{
    public int $article_id;

    public ?CmsArticle $article = null;

    public function mount(int $article_id): void
    {
        $this->article_id = $article_id;
        $this->article = CmsArticle::with(['section', 'category', 'tags', 'creator'])
            ->published()
            ->findOrFail($article_id);
    }

    public function title(): string
    {
        return $this->article ? ($this->article->meta_title ?: $this->article->title) : '';
    }

    public function render()
    {
        $template = FrontendViewResolver::view(
            $this->article?->resolveCustomView(),
            'cms::components.frontend.article-viewer.article-viewer',
        );

        return view($template, ['article' => $this->article])
            ->title($this->title())
            ->layout(FrontendViewResolver::layout($this->article?->resolveCustomLayout()));
    }
};
