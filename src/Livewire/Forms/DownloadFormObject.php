<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Services\CmsDownloadService;

class DownloadFormObject extends Form
{
    public ?CmsDownload $download = null;

    public string $title = '';

    public string $slug = '';

    public string $description = '';

    public string $cover_image = '';

    public string $status = 'active';

    public string $access = 'public';

    public int $sort_order = 0;

    public string $meta_title = '';

    public string $meta_description = '';

    public string $custom_view = '';

    public string $custom_layout = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setDownload(CmsDownload $download): void
    {
        $this->download = $download;
        $this->title = $download->title;
        $this->slug = $download->slug;
        $this->description = $download->description ?? '';
        $this->cover_image = $download->cover_image ?? '';
        $this->status = $download->status;
        $this->access = $download->access->value;
        $this->sort_order = $download->sort_order;
        $this->meta_title = $download->meta_title ?? '';
        $this->meta_description = $download->meta_description ?? '';
        $this->custom_view = $download->custom_view ?? '';
        $this->custom_layout = $download->custom_layout ?? '';
        $this->customVars = ($download->config ?? [])['custom_var'] ?? [];
        $this->customVarAllowAdd = ($download->config ?? [])['custom_var_allow_add'] ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'status' => 'required|in:active,inactive',
            'access' => 'required|in:public,private',
            'sort_order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'custom_view' => 'nullable|string|max:255',
            'custom_layout' => 'nullable|string|max:255',
        ];
    }

    public function store(int $createdBy): CmsDownload
    {
        $this->validate();

        $download = app(CmsDownloadService::class)->createDownload($this->toData(), $createdBy);

        $this->reset();

        return $download;
    }

    public function update(): void
    {
        $this->validate();

        app(CmsDownloadService::class)->updateDownload($this->download, $this->toData());
    }

    /**
     * @return array<string, mixed>
     */
    private function toData(): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'cover_image' => $this->cover_image ?: null,
            'status' => $this->status,
            'access' => $this->access,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'custom_view' => $this->custom_view ?: null,
            'custom_layout' => $this->custom_layout ?: null,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
        ];
    }
}
