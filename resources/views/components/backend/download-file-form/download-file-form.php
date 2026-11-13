<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\DownloadFileFormObject;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public DownloadFileFormObject $form;

    #[Locked]
    public int $downloadId;

    #[Locked]
    public int $fileId;

    public function mount(int $downloadId, int $id): void
    {
        $this->downloadId = $downloadId;
        $this->fileId = $id;

        $file = app(CmsDownloadService::class)->findFileOrFailInDownload($downloadId, $id);
        app(CmsAccessService::class)->authorizeManage($file, 'cms.download.file.update');
        $this->form->setFile($file);

        synav()->setActiveMenu('cms.download');
    }

    public function title(): string
    {
        return __('cms::labels.edit_file');
    }

    public function save(): void
    {
        app(CmsAccessService::class)->authorizeManage(
            app(CmsDownloadService::class)->findFileOrFailInDownload($this->downloadId, $this->fileId),
            'cms.download.file.update',
        );

        $this->form->update();
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.file_updated'));
        $this->redirect(backend_route('cms.download.files', ['downloadId' => $this->downloadId]), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function download(): CmsDownload
    {
        return app(CmsDownloadService::class)->findOrFailById($this->downloadId);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.download_list'), icon: 'ph ph-download-simple', url: backend_route('cms.download.index'))
            ->add(label: $this->download->title, url: backend_route('cms.download.form', ['id' => $this->downloadId]))
            ->add(label: __('cms::labels.manage_files'), url: backend_route('cms.download.files', ['downloadId' => $this->downloadId]))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
