<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsArticleService;
use VmEngine\Cms\Traits\HasAccessFields;
use VmEngine\Cms\Traits\HasCustomFieldValues;
use VmEngine\Cms\Traits\HasTranslationFields;

class ArticleFormObject extends Form
{
    use HasAccessFields;
    use HasCustomFieldValues;
    use HasTranslationFields;

    /** Form values of a per-article display override; `inherit` follows the section. */
    public const DISPLAY_OVERRIDES = ['inherit' => null, 'show' => true, 'hide' => false];

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

    public string $show_date = 'inherit';

    public string $show_excerpt = 'inherit';

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
        $this->show_date = self::overrideKey(($article->config ?? [])['show_date'] ?? null);
        $this->show_excerpt = self::overrideKey(($article->config ?? [])['show_excerpt'] ?? null);

        $this->fillTranslations($article);
        $this->fillCustomFields($article, $article);
        $this->fillAccess($article, $article, 'cms.article.update', CmsVisibility::Inherit);
    }

    /** Access panel defaults for a new item. */
    public function initAccessForNew(CmsSection $section): void
    {
        $this->fillAccess(null, $section, 'cms.article.create', CmsVisibility::Inherit);
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
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

        $override = 'required|in:'.implode(',', array_keys(self::DISPLAY_OVERRIDES));

        return [...$rules, 'show_date' => $override, 'show_excerpt' => $override, ...$this->accessRules(allowInherit: true), ...$this->translationRules($rules, new CmsArticle), ...$this->customFieldRules()];
    }

    public function store(int $createdBy): CmsArticle
    {
        $this->validate();

        $article = $this->saveWithTranslations(fn () => tap(
            app(CmsArticleService::class)->createArticle([...$this->toData(), ...$this->accessData($this->sectionModel(), 'cms.article.create')], $this->tag_ids, $createdBy),
            fn (CmsArticle $article) => $this->saveCustomFields($article),
        ));

        $this->saveAccess($article, $this->sectionModel(), 'cms.article.create', withManagers: false);

        $this->reset();

        return $article;
    }

    public function update(int $updatedBy): void
    {
        $this->validate();

        $this->saveWithTranslations(fn () => tap(
            app(CmsArticleService::class)->updateArticle($this->article, [...$this->toData(), ...$this->accessData($this->article, 'cms.article.update')], $this->tag_ids, $updatedBy),
            fn (CmsArticle $article) => $this->saveCustomFields($article),
        ));

        $this->saveAccess($this->article, $this->article, 'cms.article.update', withManagers: false);
    }

    private function sectionModel(): CmsSection
    {
        return app(CmsArticleService::class)->findSectionOrFailById($this->section_id);
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
            'config' => [
                'custom_var' => $this->customVars,
                'custom_var_allow_add' => $this->customVarAllowAdd,
                'show_date' => self::DISPLAY_OVERRIDES[$this->show_date],
                'show_excerpt' => self::DISPLAY_OVERRIDES[$this->show_excerpt],
            ],
        ];
    }

    private static function overrideKey(?bool $value): string
    {
        return (string) array_search($value, self::DISPLAY_OVERRIDES, true);
    }
}
