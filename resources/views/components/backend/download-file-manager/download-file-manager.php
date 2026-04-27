<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public int $downloadId;

    public ?int $newFileId = null;

    public string $newFileUrl = '';

    public string $newFileTitle = '';

    public string $newFileDescription = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $newFileCustomVars = [];

    public bool $newFileCustomVarAllowAdd = false;

    public function mount(int $downloadId): void
    {
        $this->downloadId = $downloadId;
        app(CmsDownloadService::class)->findOrFailById($downloadId);
        synav()->setActiveMenu('cms.download');
    }

    public function title(): string
    {
        return __('cms::labels.manage_files').': '.$this->download->title;
    }

    #[On('fm:file-selected')]
    public function onFileSelected(string $url, string $path, int $fileId, string $key = ''): void
    {
        if ($key === '' || $key === 'download-add') {
            $this->newFileId = $fileId;
            $this->newFileUrl = $url;
        }
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->newFileCustomVarAllowAdd = $value;
    }

    public function addFile(): void
    {
        $this->validate([
            'newFileUrl' => 'required|string',
            'newFileId' => 'required|integer|exists:fm_files,id',
        ]);

        app(CmsDownloadService::class)->addFileToDownload($this->downloadId, [
            'file_id' => $this->newFileId,
            'title' => $this->newFileTitle ?: null,
            'description' => $this->newFileDescription ?: null,
            'config' => ['custom_var' => $this->newFileCustomVars, 'custom_var_allow_add' => $this->newFileCustomVarAllowAdd],
        ]);

        $this->reset('newFileUrl', 'newFileTitle', 'newFileDescription', 'newFileCustomVars', 'newFileCustomVarAllowAdd');
        $this->newFileId = null;
        unset($this->files);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.file_added'));
    }

    public function deleteFile(int $id): void
    {
        app(CmsDownloadService::class)->deleteFileInDownload($this->downloadId, $id);
        unset($this->files);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.file_deleted'));
    }

    public function updateOrder(array $ids): void
    {
        app(CmsDownloadService::class)->reorderFilesInDownload($this->downloadId, $ids);
        unset($this->files);
    }

    #[Computed()]
    public function download(): CmsDownload
    {
        return app(CmsDownloadService::class)->findOrFailById($this->downloadId);
    }

    #[Computed()]
    public function files()
    {
        return app(CmsDownloadService::class)->getDownloadFiles($this->downloadId);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.download_list'), icon: 'fa-solid fa-download', url: backend_route('cms.download.index'))
            ->add(label: $this->download->title, url: backend_route('cms.download.form', ['id' => $this->downloadId]))
            ->add(label: __('cms::labels.manage_files'));
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
