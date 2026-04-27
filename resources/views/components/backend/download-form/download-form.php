<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Enums\DownloadAccess;
use VmEngine\Cms\Livewire\Forms\DownloadFormObject;
use VmEngine\Cms\Services\CmsDownloadService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public DownloadFormObject $form;

    public ?int $downloadId = null;

    public function mount(?int $id = null): void
    {
        $this->downloadId = $id;

        if ($id) {
            $download = app(CmsDownloadService::class)->findOrFailById($id);
            $this->form->setDownload($download);
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
    public function accessOptions(): array
    {
        return DownloadAccess::options();
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
