<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Enums\CmsVisibility;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Traits\HasAccessFields;
use VmEngine\Cms\Traits\HasCustomFieldValues;
use VmEngine\Cms\Traits\HasFieldSchema;
use VmEngine\Cms\Traits\HasTranslationFields;

class AlbumFormObject extends Form
{
    use HasAccessFields;
    use HasCustomFieldValues;
    use HasFieldSchema;
    use HasTranslationFields;

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

    public string $custom_view = '';

    public string $custom_layout = '';

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
        $this->custom_view = $album->custom_view ?? '';
        $this->custom_layout = $album->custom_layout ?? '';
        $this->customVars = ($album->config ?? [])['custom_var'] ?? [];
        $this->customVarAllowAdd = ($album->config ?? [])['custom_var_allow_add'] ?? false;

        $this->fillTranslations($album);
        $this->fillFieldSchema($album);
        $this->fillCustomFields($album, $album);
        $this->fillAccess($album, $album, app(CmsAccessService::class)->abilityFor($this->album, 'update'), CmsVisibility::Public);
    }

    /** Access panel defaults for a new item. */
    public function initAccessForNew(): void
    {
        $this->fillAccess(null, null, $this->type === 'banner' ? 'cms.gallery.banner.create' : 'cms.gallery.album.create', CmsVisibility::Public);
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|regex:/^[a-z0-9-]+$/',
            'type' => 'required|in:normal,banner',
            'status' => 'required|in:active,inactive',
            'sort_order' => 'integer|min:0',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'custom_view' => 'nullable|string|max:255',
            'custom_layout' => 'nullable|string|max:255',
        ];

        return [...$rules, ...$this->accessRules(allowInherit: false), ...$this->translationRules($rules, new CmsAlbum), ...$this->fieldSchemaRules(), ...$this->customFieldRules()];
    }

    public function store(int $createdBy): CmsAlbum
    {
        $this->validate();

        $album = $this->saveWithTranslations(fn () => tap(
            app(CmsGalleryService::class)->createAlbum([...$this->toData(), ...$this->accessData(null, $this->type === 'banner' ? 'cms.gallery.banner.create' : 'cms.gallery.album.create')], $createdBy),
            fn (CmsAlbum $album) => $this->saveCustomData($album),
        ));

        $this->saveAccess($album, null, $this->type === 'banner' ? 'cms.gallery.banner.create' : 'cms.gallery.album.create', withManagers: true);

        $this->reset();

        return $album;
    }

    public function update(): void
    {
        $this->validate();

        $this->saveWithTranslations(fn () => tap(
            app(CmsGalleryService::class)->updateAlbum($this->album, [...$this->toData(), ...$this->accessData($this->album, app(CmsAccessService::class)->abilityFor($this->album, 'update'))]),
            fn (CmsAlbum $album) => $this->saveCustomData($album),
        ));

        $this->saveAccess($this->album, $this->album, app(CmsAccessService::class)->abilityFor($this->album, 'update'), withManagers: true);
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
            'type' => $this->type,
            'status' => $this->status,
            'sort_order' => $this->sort_order,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'custom_view' => $this->custom_view ?: null,
            'custom_layout' => $this->custom_layout ?: null,
            'config' => ['custom_var' => $this->customVars, 'custom_var_allow_add' => $this->customVarAllowAdd],
        ];
    }

    /**
     * Value inputs follow the dev's live schema; everyone else the stored one.
     *
     * @return list<array<string, mixed>>
     */
    public function inputFieldSchema(): array
    {
        return is_dev() ? $this->fieldSchema : $this->customFieldSchema;
    }

    private function saveCustomData(CmsAlbum $album): void
    {
        $this->saveFieldSchema($album);
        $this->saveCustomFields($album);
    }
}
