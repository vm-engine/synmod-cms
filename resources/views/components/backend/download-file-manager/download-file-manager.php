<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
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
        CmsDownload::findOrFail($downloadId);
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

        $maxOrder = CmsDownloadFile::where('download_id', $this->downloadId)->max('sort_order') ?? -1;

        CmsDownloadFile::create([
            'download_id' => $this->downloadId,
            'file_id' => $this->newFileId,
            'title' => $this->newFileTitle ?: null,
            'description' => $this->newFileDescription ?: null,
            'sort_order' => $maxOrder + 1,
            'config' => ['custom_var' => $this->newFileCustomVars, 'custom_var_allow_add' => $this->newFileCustomVarAllowAdd],
        ]);

        $this->reset('newFileUrl', 'newFileTitle', 'newFileDescription', 'newFileCustomVars', 'newFileCustomVarAllowAdd');
        $this->newFileId = null;
        unset($this->files);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.file_added'));
    }

    public function deleteFile(int $id): void
    {
        CmsDownloadFile::where('download_id', $this->downloadId)->findOrFail($id)->delete();
        unset($this->files);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.file_deleted'));
    }

    public function updateOrder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            CmsDownloadFile::where('download_id', $this->downloadId)
                ->where('id', $id)
                ->update(['sort_order' => $order]);
        }
        unset($this->files);
    }

    #[Computed()]
    public function download(): CmsDownload
    {
        return CmsDownload::findOrFail($this->downloadId);
    }

    #[Computed()]
    public function files()
    {
        return CmsDownloadFile::where('download_id', $this->downloadId)
            ->with('file')
            ->orderBy('sort_order')
            ->get();
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
