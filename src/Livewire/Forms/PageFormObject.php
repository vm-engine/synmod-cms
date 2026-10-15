<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsPageService;
use VmEngine\Cms\Traits\HasAccessFields;
use VmEngine\Cms\Traits\HasCustomFieldValues;
use VmEngine\Cms\Traits\HasFieldSchema;
use VmEngine\Cms\Traits\HasMediaGalleryFlag;
use VmEngine\Cms\Traits\HasTranslationFields;

class PageFormObject extends Form
{
    use HasAccessFields;
    use HasCustomFieldValues;
    use HasFieldSchema;
    use HasMediaGalleryFlag;
    use HasTranslationFields;

    public ?CmsPage $page = null;

    public ?int $parent_id = null;

    public string $title = '';

    public string $slug = '';

    public string $body = '';

    public string $excerpt = '';

    public string $cover_image = '';

    public string $status = 'draft';

    public string $published_at = '';

    public int $sort_order = 0;

    public bool $show_title = true;

    public bool $show_breadcrumb = true;

    public string $children_display = 'none';

    public bool $show_submenu = false;

    public bool $show_excerpt = false;

    public bool $show_in_sitemap = true;

    /** Dev-only; see {@see allowChildCreateConfig()}. */
    public bool $allow_child_create = true;

    public string $children_layout = 'grid';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $og_image = '';

    public string $custom_view = '';

    public string $custom_layout = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setPage(CmsPage $page): void
    {
        $this->page = $page;
        $this->parent_id = $page->parent_id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->body = $page->body ?? '';
        $this->excerpt = $page->excerpt ?? '';
        $this->cover_image = $page->cover_image ?? '';
        $this->status = $page->status;
        $this->published_at = $page->published_at ? $page->published_at->format('Y-m-d H:i') : '';
        $this->sort_order = $page->sort_order;
        $config = $page->config ?? [];
        $this->show_title = $config['show_title'] ?? true;
        $this->show_breadcrumb = $config['show_breadcrumb'] ?? true;
        $this->children_display = $page->childrenDisplay();
        $this->show_submenu = $config['show_submenu'] ?? false;
        $this->show_excerpt = $config['show_excerpt'] ?? false;
        $this->show_in_sitemap = $page->showInSitemap();
        $this->allow_child_create = $config['allow_child_create'] ?? true;
        $this->children_layout = $config['children_layout'] ?? 'grid';
        $this->meta_title = $page->meta_title ?? '';
        $this->meta_description = $page->meta_description ?? '';
        $this->og_image = $page->og_image ?? '';
        $this->custom_view = $page->custom_view ?? '';
        $this->custom_layout = $page->custom_layout ?? '';
        $this->customVars = $config['custom_var'] ?? [];
        $this->customVarAllowAdd = $config['custom_var_allow_add'] ?? false;

        $this->fillTranslations($page);
        $this->fillAccess($page, $page, 'cms.page.update', CmsVisibility::Public);
        $this->fillFieldSchema($page);
        $this->fillMediaGalleryFlag($page);
        $this->fillCustomFields($page, $page);
    }

    /** Access panel defaults for a new page (root, or child of $parent). */
    public function initAccessForNew(?CmsPage $parent): void
    {
        $this->fillAccess(null, $parent, 'cms.page.create', $parent ? CmsVisibility::Inherit : CmsVisibility::Public);
        $this->fillCustomFields(new CmsPage(['parent_id' => $parent?->id]));
    }

    /**
     * Root pages follow the dev's live schema; children follow their root's stored schema.
     *
     * @return list<array<string, mixed>>
     */
    public function inputFieldSchema(): array
    {
        return $this->parent_id === null && is_dev() ? $this->fieldSchema : $this->customFieldSchema;
    }

    /** Parent changed in the form: reload the new root's schema (values kept by name). */
    public function refreshCustomFields(): void
    {
        $kept = $this->customFieldValues;
        $this->fillCustomFields(new CmsPage(['parent_id' => $this->parent_id]), $this->page);

        foreach ($this->customFieldValues as $locale => $values) {
            $this->customFieldValues[$locale] = array_replace($values, array_intersect_key($kept[$locale] ?? [], $values));
        }
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'sort_order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'custom_view' => 'nullable|string|max:255',
            'custom_layout' => 'nullable|string|max:255',
        ];

        return [
            ...$rules,
            'children_display' => 'required|in:'.implode(',', CmsPage::CHILDREN_DISPLAYS),
            ...$this->accessRules(allowInherit: $this->parent_id !== null),
            ...($this->parent_id === null ? $this->fieldSchemaRules() : []),
            ...$this->customFieldRules(),
            ...$this->translationRules($rules, new CmsPage),
        ];
    }

    public function store(int $createdBy): CmsPage
    {
        $this->validate();

        $parent = $this->parentPage();
        $page = $this->saveWithTranslations(fn () => tap(
            app(CmsPageService::class)->create([...$this->toData(), ...$this->accessData($parent, 'cms.page.create')], $createdBy),
            fn (CmsPage $page) => $this->saveCustomData($page),
        ));
        $this->saveAccess($page, $parent, 'cms.page.create', withManagers: true);

        $this->reset();

        return $page;
    }

    public function update(): void
    {
        $this->validate();

        $this->saveWithTranslations(fn () => tap(
            app(CmsPageService::class)->update($this->page, [...$this->toData(), ...$this->accessData($this->page, 'cms.page.update')]),
            fn (CmsPage $page) => $this->saveCustomData($page),
        ));
        $this->saveAccess($this->page, $this->page, 'cms.page.update', withManagers: true);
    }

    private function saveCustomData(CmsPage $page): void
    {
        if ($page->parent_id === null) {
            $this->saveFieldSchema($page);
        }

        $this->saveCustomFields($page);
    }

    /** Non-devs never change the lock: re-write the stored value whatever they post. */
    private function allowChildCreateConfig(): bool
    {
        return is_dev() ? $this->allow_child_create : ($this->page?->config['allow_child_create'] ?? true) !== false;
    }

    private function parentPage(): ?CmsPage
    {
        return $this->parent_id ? app(CmsPageService::class)->findById($this->parent_id) : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function toData(): array
    {
        return [
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'body' => $this->body,
            'excerpt' => $this->excerpt ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'published_at' => $this->published_at ?: null,
            'sort_order' => $this->sort_order,
            'config' => [
                'show_title' => $this->show_title,
                'show_breadcrumb' => $this->show_breadcrumb,
                'children_display' => $this->children_display,
                'show_submenu' => $this->show_submenu,
                'show_excerpt' => $this->show_excerpt,
                'show_in_sitemap' => $this->show_in_sitemap,
                'allow_child_create' => $this->allowChildCreateConfig(),
                'children_layout' => $this->children_layout,
                'custom_var' => $this->customVars,
                'custom_var_allow_add' => $this->customVarAllowAdd,
                ...($this->parent_id === null ? $this->mediaGalleryConfig($this->page) : []),
            ],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
            'custom_view' => $this->custom_view ?: null,
            'custom_layout' => $this->custom_layout ?: null,
        ];
    }
}
