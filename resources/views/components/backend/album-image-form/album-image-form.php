<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Livewire\Forms\AlbumImageFormObject;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    public AlbumImageFormObject $form;

    #[Locked]
    public int $albumId;

    #[Locked]
    public int $imageId;

    public function mount(int $albumId, int $id): void
    {
        $this->albumId = $albumId;
        $this->imageId = $id;

        $image = app(CmsGalleryService::class)->findAlbumImageOrFailInAlbum($albumId, $id);
        app(CmsAccessService::class)->authorizeManage($image, 'cms.gallery.image.update');
        $this->form->setImage($image);

        synav()->setActiveMenu('cms.gallery');
    }

    public function title(): string
    {
        return __('cms::labels.edit_image');
    }

    public function save(): void
    {
        app(CmsAccessService::class)->authorizeManage(
            app(CmsGalleryService::class)->findAlbumImageOrFailInAlbum($this->albumId, $this->imageId),
            'cms.gallery.image.update',
        );

        $this->form->update();
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.image_updated'));
        $this->redirect(backend_route('cms.album.images', ['albumId' => $this->albumId]), navigate: true);
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->form->customVarAllowAdd = $value;
    }

    #[Computed()]
    public function album(): CmsAlbum
    {
        return app(CmsGalleryService::class)->findAlbumOrFailById($this->albumId);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.album_list'), icon: 'fa-solid fa-images', url: backend_route('cms.album.index'))
            ->add(label: $this->album->title, url: backend_route('cms.album.form', ['id' => $this->albumId]))
            ->add(label: __('cms::labels.manage_images'), url: backend_route('cms.album.images', ['albumId' => $this->albumId]))
            ->add(label: $this->title());
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
