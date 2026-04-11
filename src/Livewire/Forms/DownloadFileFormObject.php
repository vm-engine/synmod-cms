<?php

declare(strict_types=1);

namespace VmEngine\Cms\Livewire\Forms;

use Livewire\Form;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Services\CmsActivityLogger;

class DownloadFileFormObject extends Form
{
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
    }

    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'customVars' => 'array',
            'customVars.*.key' => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'customVars.*.label' => 'nullable|string|max:255',
            'customVars.*.value' => 'nullable|string|max:2000',
            'customVars.*.editable' => 'boolean',
        ];
    }

    public function update(): void
    {
        $this->validate();

        $config = $this->file->config ?? [];
        $config['custom_var'] = $this->customVars;
        $config['custom_var_allow_add'] = $this->customVarAllowAdd;

        $oldData = $this->file->toArray();

        $this->file->update([
            'title' => $this->title ?: null,
            'description' => $this->description ?: null,
            'config' => $config,
        ]);

        app(CmsActivityLogger::class)->logUpdate('download_file', $this->file, $oldData);
    }
}
