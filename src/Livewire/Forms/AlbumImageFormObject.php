<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Traits\HasCustomFieldValues;
use VmEngine\Cms\Traits\HasTranslationFields;

class AlbumImageFormObject extends Form
{
    use HasCustomFieldValues;
    use HasTranslationFields;

    public ?CmsAlbumImage $image = null;

    public string $title = '';

    public string $caption = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setImage(CmsAlbumImage $image): void
    {
        $this->image = $image;
        $this->title = $image->title ?? '';
        $this->caption = $image->caption ?? '';
        $this->customVars = $image->config['custom_var'] ?? [];
        $this->customVarAllowAdd = $image->config['custom_var_allow_add'] ?? false;

        $this->fillTranslations($image);
        $this->fillCustomFields($image, $image);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:1000',
            'customVars' => 'array',
            'customVars.*.key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'customVars.*.label' => 'nullable|string|max:255',
            'customVars.*.value' => 'nullable|string|max:2000',
            'customVars.*.editable' => 'boolean',
        ];

        return [...$rules, ...$this->translationRules($rules, new CmsAlbumImage), ...$this->customFieldRules()];
    }

    public function update(): void
    {
        $this->validate();

        $config = $this->image->config ?? [];
        $config['custom_var'] = $this->customVars;
        $config['custom_var_allow_add'] = $this->customVarAllowAdd;

        $this->saveWithTranslations(fn () => tap(
            app(CmsGalleryService::class)->updateImage($this->image, [
                'title' => $this->title ?: null,
                'caption' => $this->caption ?: null,
                'config' => $config,
            ]),
            fn (CmsAlbumImage $image) => $this->saveCustomFields($image),
        ));
    }
}
