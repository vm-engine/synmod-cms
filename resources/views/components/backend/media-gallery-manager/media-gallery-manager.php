<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Cms\Models\CmsMediaGallery;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsMediaGalleryService;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Fm\Models\FmFile;

/**
 * Inline media galleries of one page / article / download file. Anyone who
 * can update the owner manages its galleries; the owner's container must
 * have the dev-only media_gallery flag on.
 */
new class extends Component
{
    #[Locked]
    public string $ownerType = '';

    #[Locked]
    public int $ownerId = 0;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $activeGalleryId = null;

    public string $title = '';

    public string $key = '';

    public string $layout = 'slider';

    public string $newImageUrl = '';

    public ?int $newFileId = null;

    /** @var array<int|string, array<string, string>> image id => locale => caption */
    public array $captions = [];

    public function mount(CmsPage|CmsArticle|CmsDownloadFile $owner): void
    {
        $this->ownerType = $owner::class;
        $this->ownerId = (int) $owner->getKey();
        $this->authorizeOwner();
    }

    #[Computed()]
    public function owner(): CmsPage|CmsArticle|CmsDownloadFile
    {
        return $this->service()->findOwnerOrFail($this->ownerType, $this->ownerId);
    }

    /**
     * @return Collection<int, CmsMediaGallery>
     */
    #[Computed()]
    public function galleries(): Collection
    {
        return $this->service()->galleriesOf($this->owner);
    }

    #[Computed()]
    public function activeGallery(): ?CmsMediaGallery
    {
        return $this->activeGalleryId === null
            ? null
            : $this->service()->findInOwnerOrFail($this->owner, $this->activeGalleryId)->load('images.file');
    }

    /**
     * @return list<string>
     */
    #[Computed()]
    public function locales(): array
    {
        return CmsLocale::all();
    }

    public function create(): void
    {
        $this->authorizeOwner();
        $this->reset('editingId', 'title', 'key', 'layout');
        $this->resetValidation();
        $this->dispatch('open-modal-media-gallery-form');
    }

    public function edit(int $id): void
    {
        $this->authorizeOwner();
        $gallery = $this->service()->findInOwnerOrFail($this->owner, $id);
        $this->editingId = $gallery->id;
        $this->title = $gallery->title;
        $this->key = $gallery->key;
        $this->layout = $gallery->layout;
        $this->resetValidation();
        $this->dispatch('open-modal-media-gallery-form');
    }

    /** New galleries get their key from the title until saved. */
    public function updatedTitle(): void
    {
        if ($this->editingId === null) {
            $this->key = Str::slug($this->title);
        }
    }

    public function saveGallery(): void
    {
        $this->authorizeOwner();

        /** @var array{title: string, key: string, layout: string} $data */
        $data = $this->validate([
            'title' => 'required|string|max:255',
            'key' => [
                'required', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('cms_media_galleries', 'key')
                    ->where('owner_type', $this->owner->getMorphClass())
                    ->where('owner_id', $this->ownerId)
                    ->ignore($this->editingId),
            ],
            'layout' => ['required', Rule::in(CmsMediaGallery::LAYOUTS)],
        ]);

        if ($this->editingId !== null) {
            $this->service()->update($this->service()->findInOwnerOrFail($this->owner, $this->editingId), $data);
        } else {
            $this->service()->create($this->owner, $data);
        }

        unset($this->galleries, $this->activeGallery);
        $this->dispatch('close-modal-media-gallery-form');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.media_gallery_saved'));
    }

    public function deleteGallery(int $id): void
    {
        $this->authorizeOwner();
        $this->service()->delete($this->service()->findInOwnerOrFail($this->owner, $id));

        if ($this->activeGalleryId === $id) {
            $this->closeImages();
        }

        unset($this->galleries);
        $this->dispatch('synapse-confirmed');
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.media_gallery_deleted'));
    }

    public function openImages(int $id): void
    {
        $this->authorizeOwner();
        $this->activeGalleryId = $this->service()->findInOwnerOrFail($this->owner, $id)->id;
        $this->reset('newImageUrl', 'newFileId');
        unset($this->activeGallery);
        $this->fillCaptions();
    }

    public function closeImages(): void
    {
        $this->activeGalleryId = null;
        $this->captions = [];
        unset($this->activeGallery);
    }

    #[On('fm:file-selected')]
    public function onFileSelected(string $url, string $path, int $fileId, string $key = ''): void
    {
        if ($key === 'media-gallery-add') {
            $this->newFileId = $fileId;
        }
    }

    public function addImage(): void
    {
        $this->authorizeOwner();
        $gallery = $this->requireActiveGallery();

        $this->validate([
            'newImageUrl' => 'required|string',
            'newFileId' => 'required|integer|exists:fm_files,id',
        ]);

        if (! FmFile::findOrFail($this->newFileId)->isImage()) {
            $this->addError('newFileId', __('cms::labels.media_gallery_not_image'));

            return;
        }

        $this->service()->addImage($gallery, (int) $this->newFileId);

        $this->reset('newImageUrl', 'newFileId');
        unset($this->activeGallery, $this->galleries);
        $this->fillCaptions();
    }

    public function removeImage(int $imageId): void
    {
        $this->authorizeOwner();
        $this->service()->removeImage($this->requireActiveGallery(), $imageId);

        unset($this->captions[$imageId], $this->activeGallery, $this->galleries);
        $this->dispatch('synapse-confirmed');
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function updateOrder(array $ids): void
    {
        $this->authorizeOwner();
        $this->service()->reorderImages($this->requireActiveGallery(), $ids);
        unset($this->activeGallery);
    }

    public function saveCaptions(): void
    {
        $this->authorizeOwner();
        $this->validate(['captions' => 'array', 'captions.*' => 'array', 'captions.*.*' => 'nullable|string|max:500']);

        $this->service()->saveCaptions($this->requireActiveGallery(), $this->captions);

        unset($this->activeGallery);
        $this->dispatch('notify', variant: 'success', title: 'Success', message: __('cms::labels.media_gallery_captions_saved'));
    }

    private function fillCaptions(): void
    {
        $this->captions = [];

        foreach ($this->activeGallery->images ?? [] as $image) {
            foreach ($this->locales as $locale) {
                $this->captions[$image->id][$locale] = (string) $image->translate('caption', $locale, fallback: false);
            }
        }
    }

    private function requireActiveGallery(): CmsMediaGallery
    {
        $gallery = $this->activeGallery;
        abort_if($gallery === null, 404);

        return $gallery;
    }

    private function authorizeOwner(): void
    {
        $access = app(CmsAccessService::class);
        $access->authorizeManage($this->owner, $access->abilityFor($this->owner, 'update'));
        abort_unless($this->owner->mediaGalleriesEnabled(), 403);
    }

    private function service(): CmsMediaGalleryService
    {
        return app(CmsMediaGalleryService::class);
    }

    public function render()
    {
        return $this->view();
    }
};
