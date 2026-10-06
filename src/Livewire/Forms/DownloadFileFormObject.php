<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Cms\Traits\HasCustomFieldValues;
use VmEngine\Cms\Traits\HasTranslationFields;

class DownloadFileFormObject extends Form
{
    use HasCustomFieldValues;
    use HasTranslationFields;

    public ?CmsDownloadFile $file = null;

    public string $title = '';

    public string $description = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $customVars = [];

    public bool $customVarAllowAdd = false;

    public function setFile(CmsDownloadFile $file): void
    {
        $this->file = $file;
        $this->title = $file->title ?? '';
        $this->description = $file->description ?? '';
        $this->customVars = $file->config['custom_var'] ?? [];
        $this->customVarAllowAdd = $file->config['custom_var_allow_add'] ?? false;

        $this->fillTranslations($file);
        $this->fillCustomFields($file, $file);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'customVars' => 'array',
            'customVars.*.key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'customVars.*.label' => 'nullable|string|max:255',
            'customVars.*.value' => 'nullable|string|max:2000',
            'customVars.*.editable' => 'boolean',
        ];

        return [...$rules, ...$this->translationRules($rules, new CmsDownloadFile), ...$this->customFieldRules()];
    }

    public function update(): void
    {
        $this->validate();

        $config = $this->file->config ?? [];
        $config['custom_var'] = $this->customVars;
        $config['custom_var_allow_add'] = $this->customVarAllowAdd;

        $this->saveWithTranslations(fn () => tap(
            app(CmsDownloadService::class)->updateFile($this->file, [
                'title' => $this->title ?: null,
                'description' => $this->description ?: null,
                'config' => $config,
            ]),
            fn (CmsDownloadFile $file) => $this->saveCustomFields($file),
        ));
    }
}
