<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsGalleryService;
use VmEngine\Cms\Traits\HasCustomFieldValues;
use VmEngine\Cms\Traits\WithCmsAccessChecks;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
    use HasCustomFieldValues;
    use WithCmsAccessChecks;

    #[Locked]
    public int $albumId;

    public string $newImageUrl = '';

    public ?int $newFileId = null;

    public string $newImageTitle = '';

    public string $newImageCaption = '';

    /** @var array<int, array{key: string, label: string, value: string, editable: bool}> */
    public array $newImageCustomVars = [];

    public bool $newImageCustomVarAllowAdd = false;

    public function mount(int $albumId): void
    {
        $this->albumId = $albumId;
        app(CmsGalleryService::class)->findAlbumOrFailById($albumId);
        $this->fillCustomFields(new CmsAlbumImage(['album_id' => $albumId]));
        synav()->setActiveMenu('cms.gallery');
    }

    public function title(): string
    {
        return __('cms::labels.manage_images').': '.$this->album->title;
    }

    #[On('fm:file-selected')]
    public function onFileSelected(string $url, string $path, int $fileId, string $key = ''): void
    {
        app(CmsAccessService::class)->authorizeManage($this->album, 'cms.gallery.image.create');

        if ($key === '' || $key === 'album-add') {
            $this->newFileId = $fileId;
        }
    }

    #[On('custom-var-allow-add-changed')]
    public function onCustomVarAllowAddChanged(bool $value): void
    {
        $this->newImageCustomVarAllowAdd = $value;
    }

    public function addImage(): void
    {
        app(CmsAccessService::class)->authorizeManage($this->album, 'cms.gallery.image.create');

        $this->validate([
            'newImageUrl' => 'required|string',
            'newFileId' => 'required|integer|exists:fm_files,id',
            ...$this->customFieldRules(),
        ], [], $this->customFieldAttributes());

        $item = app(CmsGalleryService::class)->addImageToAlbum($this->albumId, [
            'file_id' => $this->newFileId,
            'title' => $this->newImageTitle ?: null,
            'caption' => $this->newImageCaption ?: null,
            'config' => ['custom_var' => $this->newImageCustomVars, 'custom_var_allow_add' => $this->newImageCustomVarAllowAdd],
        ]);
        $this->saveCustomFields($item);

        $this->reset('newImageUrl', 'newImageTitle', 'newImageCaption', 'newImageCustomVars', 'newImageCustomVarAllowAdd');
        $this->fillCustomFields(new CmsAlbumImage(['album_id' => $this->albumId]));
        $this->newFileId = null;
        unset($this->images);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.image_added'));
    }

    public function deleteImage(string $token): void
    {
        app(CmsAccessService::class)->authorizeManage($this->album, 'cms.gallery.image.delete');

        $id = CmsAlbumImage::validateDeleteToken($token);

        if ($id === false) {
            $this->dispatch('synapse-confirmed');
            $this->dispatch('notify', variant: 'danger', title: 'Error', message: __('cms::labels.invalid_delete_token'));

            return;
        }

        app(CmsGalleryService::class)->deleteImageInAlbum($this->albumId, (int) $id);
        unset($this->images);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.image_deleted'));
    }

    public function updateOrder(array $ids): void
    {
        app(CmsAccessService::class)->authorizeManage($this->album, 'cms.gallery.image.update');

        app(CmsGalleryService::class)->reorderImagesInAlbum($this->albumId, $ids);
        unset($this->images);
    }

    #[Computed()]
    public function album(): CmsAlbum
    {
        return app(CmsGalleryService::class)->findAlbumOrFailById($this->albumId);
    }

    #[Computed()]
    public function images()
    {
        return app(CmsGalleryService::class)->getAlbumImages($this->albumId);
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.album_list'), icon: 'ph ph-images', url: backend_route('cms.album.index'))
            ->add(label: $this->album->title, url: backend_route('cms.album.form', ['id' => $this->albumId]))
            ->add(label: __('cms::labels.manage_images'));
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
