<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsPage;

class PageFormObject extends Form
{
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

    public bool $show_children = false;

    public string $children_layout = 'grid';

    public string $meta_title = '';

    public string $meta_description = '';

    public string $og_image = '';

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
        $this->show_children = $config['show_children'] ?? false;
        $this->children_layout = $config['children_layout'] ?? 'grid';
        $this->meta_title = $page->meta_title ?? '';
        $this->meta_description = $page->meta_description ?? '';
        $this->og_image = $page->og_image ?? '';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:draft,published,scheduled',
            'published_at' => 'nullable|date',
            'sort_order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    public function store(int $createdBy): CmsPage
    {
        $this->validate();

        $fullSlug = $this->computeFullSlug();

        $page = CmsPage::create([
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'full_slug' => $fullSlug,
            'body' => $this->body,
            'excerpt' => $this->excerpt ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'published_at' => $this->published_at ?: null,
            'sort_order' => $this->sort_order,
            'config' => [
                'show_title' => $this->show_title,
                'show_breadcrumb' => $this->show_breadcrumb,
                'show_children' => $this->show_children,
                'children_layout' => $this->children_layout,
            ],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
            'created_by' => $createdBy,
        ]);

        $this->reset();

        return $page;
    }

    public function update(): void
    {
        $this->validate();

        $fullSlug = $this->computeFullSlug();

        $this->page->update([
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'full_slug' => $fullSlug,
            'body' => $this->body,
            'excerpt' => $this->excerpt ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'published_at' => $this->published_at ?: null,
            'sort_order' => $this->sort_order,
            'config' => [
                'show_title' => $this->show_title,
                'show_breadcrumb' => $this->show_breadcrumb,
                'show_children' => $this->show_children,
                'children_layout' => $this->children_layout,
            ],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
        ]);
    }

    private function computeFullSlug(): string
    {
        if ($this->parent_id === null) {
            return $this->slug;
        }

        $parent = CmsPage::find($this->parent_id);
        if ($parent === null) {
            return $this->slug;
        }

        return $parent->full_slug.'/'.$this->slug;
    }
}
