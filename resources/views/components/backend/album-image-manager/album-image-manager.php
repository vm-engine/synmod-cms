<?php

declare(strict_types=1);

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Cms\Services\CmsActivityLogger;
use VmEngine\Synapse\Services\Helper\Breadcrumbs;

new class extends Component
{
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
        CmsAlbum::findOrFail($albumId);
        synav()->setActiveMenu('cms.gallery');
    }

    public function title(): string
    {
        return __('cms::labels.manage_images').': '.$this->album->title;
    }

    #[On('fm:file-selected')]
    public function onFileSelected(string $url, string $path, int $fileId, string $key = ''): void
    {
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
        $this->validate([
            'newImageUrl' => 'required|string',
            'newFileId' => 'required|integer|exists:fm_files,id',
        ]);

        $maxOrder = CmsAlbumImage::where('album_id', $this->albumId)->max('sort_order') ?? -1;

        $image = CmsAlbumImage::create([
            'album_id' => $this->albumId,
            'file_id' => $this->newFileId,
            'title' => $this->newImageTitle ?: null,
            'caption' => $this->newImageCaption ?: null,
            'sort_order' => $maxOrder + 1,
            'config' => ['custom_var' => $this->newImageCustomVars, 'custom_var_allow_add' => $this->newImageCustomVarAllowAdd],
        ]);
        app(CmsActivityLogger::class)->logCreate('album_image', $image, $image->toArray());

        $this->reset('newImageUrl', 'newImageTitle', 'newImageCaption', 'newImageCustomVars', 'newImageCustomVarAllowAdd');
        $this->newFileId = null;
        unset($this->images);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.image_added'));
    }

    public function deleteImage(int $id): void
    {
        $image = CmsAlbumImage::where('album_id', $this->albumId)->findOrFail($id);
        app(CmsActivityLogger::class)->logDelete('album_image', $image);
        $image->delete();
        unset($this->images);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.image_deleted'));
    }

    public function updateOrder(array $ids): void
    {
        foreach ($ids as $order => $id) {
            CmsAlbumImage::where('album_id', $this->albumId)
                ->where('id', $id)
                ->update(['sort_order' => $order]);
        }
        unset($this->images);
    }

    #[Computed()]
    public function album(): CmsAlbum
    {
        return CmsAlbum::findOrFail($this->albumId);
    }

    #[Computed()]
    public function images()
    {
        return CmsAlbumImage::where('album_id', $this->albumId)
            ->with('file')
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed()]
    public function breadcrumbs(): Breadcrumbs
    {
        return Breadcrumbs::make(label: __('cms::labels.album_list'), icon: 'fa-solid fa-images', url: backend_route('cms.album.index'))
            ->add(label: $this->album->title, url: backend_route('cms.album.form', ['id' => $this->albumId]))
            ->add(label: __('cms::labels.manage_images'));
    }

    public function render()
    {
        return $this->view()->title(page_title($this->title()));
    }
};
