<?php

declare(strict_types=1);

use Livewire\Component;
use VmEngine\Cms\Models\CmsArticle;

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
        $layout = theme_service()->resolveThemeLayout('frontend');

        if (! view()->exists($layout)) {
            $layout = 'synapps::components.layouts.layout';
        }

        return view('cms::components.frontend.article-viewer.article-viewer')
            ->title($this->title())
            ->layout($layout);
    }
};
