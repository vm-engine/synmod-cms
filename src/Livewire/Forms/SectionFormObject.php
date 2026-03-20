<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsSection;

class SectionFormObject extends Form
{
    public ?CmsSection $section = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $cover_image = '';

    public string $status = 'published';

    public int $sort_order = 0;

    public string $order_by = 'published_at';

    public string $order_dir = 'desc';

    public bool $show_date = true;

    public bool $show_author = true;

    public bool $show_thumbnail = true;

    public int $items_per_page = 12;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $og_image = '';

    public function setSection(CmsSection $section): void
    {
        $this->section = $section;
        $this->title = $section->title;
        $this->slug = $section->slug;
        $this->description = $section->description ?? '';
        $this->cover_image = $section->cover_image ?? '';
        $this->status = $section->status;
        $this->sort_order = $section->sort_order;
        $config = $section->config ?? [];
        $this->order_by = $config['order_by'] ?? 'published_at';
        $this->order_dir = $config['order_dir'] ?? 'desc';
        $this->show_date = $config['show_date'] ?? true;
        $this->show_author = $config['show_author'] ?? true;
        $this->show_thumbnail = $config['show_thumbnail'] ?? true;
        $this->items_per_page = $config['items_per_page'] ?? 12;
        $this->meta_title = $section->meta_title ?? '';
        $this->meta_description = $section->meta_description ?? '';
        $this->og_image = $section->og_image ?? '';
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:draft,published',
            'sort_order' => 'integer|min:0',
            'items_per_page' => 'integer|min:1|max:100',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    public function store(int $createdBy): CmsSection
    {
        $this->validate();

        $section = CmsSection::create([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'config' => [
                'order_by' => $this->order_by,
                'order_dir' => $this->order_dir,
                'show_date' => $this->show_date,
                'show_author' => $this->show_author,
                'show_thumbnail' => $this->show_thumbnail,
                'items_per_page' => $this->items_per_page,
            ],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
            'created_by' => $createdBy,
        ]);

        $this->reset();

        return $section;
    }

    public function update(): void
    {
        $this->validate();

        $this->section->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'config' => [
                'order_by' => $this->order_by,
                'order_dir' => $this->order_dir,
                'show_date' => $this->show_date,
                'show_author' => $this->show_author,
                'show_thumbnail' => $this->show_thumbnail,
                'items_per_page' => $this->items_per_page,
            ],
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image ?: null,
        ]);
    }
}
