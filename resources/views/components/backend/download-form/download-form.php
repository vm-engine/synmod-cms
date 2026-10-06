<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\DownloadFormObject;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Cms\Traits\WithAccessPicker;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use WithAccessPicker;

    public DownloadFormObject $form;

    #[Locked]
    public ?int $downloadId = null;

    public function mount(?int $id = null): void
    {
        $this->downloadId = $id;

        if ($id) {
            $download = app(CmsDownloadService::class)->findOrFailById($id);
            app(CmsAccessService::class)->authorizeManage($download, 'cms.download.category.update');
            $this->form->setDownload($download);
        } else {
            $this->form->initAccessForNew();
        }

        synav()->setActiveMenu('cms.download');
    }

    public function title(): string
    {
        return $this->downloadId ? __('cms::labels.edit_download') : __('cms::labels.add_download');
    }

    public function save(): void
    {
        if ($this->downloadId) {
            $download = app(CmsDownloadService::class)->findOrFailById($this->downloadId);
            app(CmsAccessService::class)->authorizeManage($download, 'cms.download.category.update');
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.download_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.download_created'));
        }
        $this->redirect(backend_route('cms.download.index'), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.download_list'), icon: 'fa-solid fa-download', url: backend_route('cms.download.index'))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
