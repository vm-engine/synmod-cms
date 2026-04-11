<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Services\CmsActivityLogger;

class CategoryFormObject extends Form
{
    public ?CmsCategory $category = null;

    public ?int $section_id = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $cover_image = '';

    public string $status = 'published';

    public int $sort_order = 0;

    public bool $inherit_config = true;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $og_image = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setCategory(CmsCategory $category): void
    {
        $this->category = $category;
        $this->section_id = $category->section_id;
        $this->title = $category->title;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->cover_image = $category->cover_image ?? '';
        $this->status = $category->status;
        $this->sort_order = $category->sort_order;
        $this->inherit_config = $category->inherit_config;
        $this->meta_title = $category->meta_title ?? '';
        $this->meta_description = $category->meta_description ?? '';
        $this->og_image = $category->og_image ?? '';
        $config = $category->config ?? [];
        $this->customVars = $config['custom_var'] ?? [];
        $this->customVarAllowAdd = $config['custom_var_allow_add'] ?? false;
    }

    public function rules(): array
    {
        return [
            'section_id' => 'required|integer|exists:cms_sections,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:draft,published',
            'sort_order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    public function store(int $createdBy): CmsCategory
    {
        $this->validate();

        $category = CmsCategory::create([
            'section_id' => $this->section_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'inherit_config' => $this->inherit_config,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
            'created_by' => $createdBy,
        ]);

        app(CmsActivityLogger::class)->logCreate('category', $category, $category->toArray());

        $this->reset();

        return $category;
    }

    public function update(): void
    {
        $this->validate();

        $oldData = $this->category->toArray();

        $this->category->update([
            'section_id' => $this->section_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'inherit_config' => $this->inherit_config,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
        ]);

        app(CmsActivityLogger::class)->logUpdate('category', $this->category, $oldData);
    }
}
