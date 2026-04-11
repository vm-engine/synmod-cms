<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Services\CmsActivityLogger;

class AlbumFormObject extends Form
{
    public ?CmsAlbum $album = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $cover_image = '';

    public string $type = 'normal';

    public string $status = 'active';

    public int $sort_order = 0;

    public string $meta_title = '';

    public string $meta_description = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setAlbum(CmsAlbum $album): void
    {
        $this->album = $album;
        $this->title = $album->title;
        $this->slug = $album->slug;
        $this->description = $album->description ?? '';
        $this->cover_image = $album->cover_image ?? '';
        $this->type = $album->type->value;
        $this->status = $album->status;
        $this->sort_order = $album->sort_order;
        $this->meta_title = $album->meta_title ?? '';
        $this->meta_description = $album->meta_description ?? '';
        $this->customVars = ($album->config ?? [])['custom_var'] ?? [];
        $this->customVarAllowAdd = ($album->config ?? [])['custom_var_allow_add'] ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'type' => 'required|in:normal,banner',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
        ];
    }

    public function store(int $createdBy): CmsAlbum
    {
        $this->validate();

        $album = CmsAlbum::create([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'type' => $this->type,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
            'created_by' => $createdBy,
        ]);

        app(CmsActivityLogger::class)->logCreate('album', $album, $album->toArray());

        $this->reset();

        return $album;
    }

    public function update(): void
    {
        $this->validate();

        $oldData = $this->album->toArray();

        $this->album->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'type' => $this->type,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
        ]);

        app(CmsActivityLogger::class)->logUpdate('album', $this->album, $oldData);
    }
}
