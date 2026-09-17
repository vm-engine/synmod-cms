<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Services\CmsArticleService;

class ArticleFormObject extends Form
{
    public ?CmsArticle $article = null;

    public ?int $section_id = null;

    public ?int $category_id = null;

    public string $title = '';

    public string $slug = '';

    public string $body = '';

    public string $excerpt = '';

    public string $cover_image = '';

    public string $status = 'draft';

    public string $published_at = '';

    public string $expires_at = '';

    public bool $is_featured = false;

    public int $sort_order = 0;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $og_image = '';

    public string $custom_view = '';

    public string $custom_layout = '';

    /** @var array<int, int> */
    public array $tag_ids = [];

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setArticle(CmsArticle $article): void
    {
        $this->article = $article;
        $this->section_id = $article->section_id;
        $this->category_id = $article->category_id;
        $this->title = $article->title;
        $this->slug = $article->slug;
        $this->body = $article->body ?? '';
        $this->excerpt = $article->excerpt ?? '';
        $this->cover_image = $article->cover_image ?? '';
        $this->status = $article->status->value;
        $this->published_at = $article->published_at ? $article->published_at->format('Y-m-d H:i') : '';
        $this->expires_at = $article->expires_at ? $article->expires_at->format('Y-m-d H:i') : '';
        $this->is_featured = $article->is_featured;
        $this->sort_order = $article->sort_order;
        $this->meta_title = $article->meta_title ?? '';
        $this->meta_description = $article->meta_description ?? '';
        $this->og_image = $article->og_image ?? '';
        $this->custom_view = $article->custom_view ?? '';
        $this->custom_layout = $article->custom_layout ?? '';
        $this->tag_ids = $article->tags()->pluck('cms_tags.id')->toArray();
        $this->customVars = ($article->config ?? [])['custom_var'] ?? [];
        $this->customVarAllowAdd = ($article->config ?? [])['custom_var_allow_add'] ?? false;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'section_id' => 'required|integer|exists:cms_sections,id',
            'category_id' => 'nullable|integer|exists:cms_categories,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:published_at',
            'sort_order' => 'integer|min:0',
            'tag_ids' => 'array',
            'tag_ids.*' => 'required',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'custom_view' => 'nullable|string|max:255',
            'custom_layout' => 'nullable|string|max:255',
        ];
    }

    public function store(int $createdBy): CmsArticle
    {
        $this->validate();

        $article = app(CmsArticleService::class)->createArticle($this->toData(), $this->tag_ids, $createdBy);

        $this->reset();

        return $article;
    }

    public function update(int $updatedBy): void
    {
        $this->validate();

        app(CmsArticleService::class)->updateArticle($this->article, $this->toData(), $this->tag_ids, $updatedBy);
    }

    /**
     * @return array<string, mixed>
     */
    private function toData(): array
    {
        return [
            'section_id' => $this->section_id,
            'category_id' => $this->category_id ?: null,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'excerpt' => $this->excerpt ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'published_at' => $this->published_at ?: null,
            'expires_at' => $this->expires_at ?: null,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
            'custom_view' => $this->custom_view ?: null,
            'custom_layout' => $this->custom_layout ?: null,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
        ];
    }
}
