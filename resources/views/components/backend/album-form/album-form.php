<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Enums\AlbumType;
use VmEngine\Cms\Livewire\Forms\AlbumFormObject;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public AlbumFormObject $form;

    public ?int $albumId = null;

    public function mount(?int $id = null): void
    {
        $this->albumId = $id;

        if ($id) {
            $album = CmsAlbum::findOrFail($id);
            $this->form->setAlbum($album);
        }

        synav()->setActiveMenu('cms.gallery');
    }

    public function title(): string
    {
        return $this->albumId ? __('cms::labels.edit_album') : __('cms::labels.add_album');
    }

    public function save(): void
    {
        if ($this->albumId) {
            $this->form->update();
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.album_updated'));
        } else {
            $this->form->store(auth()->id());
            $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.album_created'));
        }
        $this->redirect(backend_route('cms.album.index'), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function typeOptions(): array
    {
        return AlbumType::options();
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.album_list'), icon: 'fa-solid fa-images', url: backend_route('cms.album.index'))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
