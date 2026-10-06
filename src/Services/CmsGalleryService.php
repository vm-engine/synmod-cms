<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Fm\Models\FmFile;

class CmsGalleryService
{
    public function __construct(private readonly CmsActivityLogger $logger) {}

    // ---------------------------------------------------------------- Albums

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findAlbumOrFailById(int $id, array $with = []): CmsAlbum
    {
        return CmsAlbum::with($with)->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findActiveNormalAlbumOrFailById(int $id, array $with = []): CmsAlbum
    {
        return CmsAlbum::with($with)->active()->normal()->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findActiveAlbumBySlug(string $slug, array $with = []): ?CmsAlbum
    {
        return CmsAlbum::with($with)->where('slug', $slug)->active()->visibleTo()->first();
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findActiveBannerAlbumBySlug(string $slug, array $with = []): ?CmsAlbum
    {
        return CmsAlbum::with($with)->where('slug', $slug)->banner()->active()->visibleTo()->first();
    }

    /**
     * @param  array<int|string, mixed>  $with
     * @return Collection<int, CmsAlbum>
     */
    public function getActiveNormalAlbums(array $with = []): Collection
    {
        /** @var Collection<int, CmsAlbum> $albums */
        $albums = CmsAlbum::with($with)->active()->normal()->visibleTo()->orderBy('sort_order')->get();

        return $albums;
    }

    /**
     * @param  array<int, string>  $slugs
     * @param  array<int|string, mixed>  $with
     * @return \Illuminate\Support\Collection<string, CmsAlbum>
     */
    public function findAlbumsBySlugs(array $slugs, array $with = []): \Illuminate\Support\Collection
    {
        return CmsAlbum::query()
            ->with($with)
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');
    }

    /**
     * Backend list pagination with filters.
     *
     * @param  array{q?: string, type?: string, status?: string}  $filters
     * @return LengthAwarePaginator<int, CmsAlbum>
     */
    public function paginateAlbums(array $filters, string $sort, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = CmsAlbum::withCount('images');

        if (! empty($filters['q']) && strlen($filters['q']) > 2) {
            $query->search($filters['q']);
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy($sort, $this->normalizeDirection($direction))->paginate($perPage)->onEachSide(1);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createAlbum(array $data, int $userId): CmsAlbum
    {
        $data['created_by'] = $userId;
        $album = CmsAlbum::create($data);
        $this->logger->logCreate('album', $album, $album->toArray());

        return $album;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateAlbum(CmsAlbum $album, array $data): CmsAlbum
    {
        $oldData = $album->toArray();
        $album->update($data);
        $this->logger->logUpdate('album', $album, $oldData);

        return $album;
    }

    public function deleteAlbumByToken(string $token, ?Closure $authorize = null): CmsAlbum
    {
        $id = CmsAlbum::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $album = CmsAlbum::findOrFail($id);

        $authorize?->__invoke($album);
        $this->logger->logDelete('album', $album);
        $album->delete();

        return $album;
    }

    // ---------------------------------------------------------------- Images

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findAlbumImageOrFailInAlbum(int $albumId, int $imageId, array $with = []): CmsAlbumImage
    {
        return CmsAlbumImage::with($with)
            ->where('album_id', $albumId)
            ->findOrFail($imageId);
    }

    /**
     * @return Collection<int, CmsAlbumImage>
     */
    public function getAlbumImages(int $albumId): Collection
    {
        /** @var Collection<int, CmsAlbumImage> $images */
        $images = CmsAlbumImage::where('album_id', $albumId)
            ->with('file')
            ->orderBy('sort_order')
            ->get();

        return $images;
    }

    /**
     * @param  array{file_id: int, title?: string|null, caption?: string|null, config?: array<string, mixed>}  $data
     */
    public function addImageToAlbum(int $albumId, array $data): CmsAlbumImage
    {
        $maxOrder = CmsAlbumImage::where('album_id', $albumId)->max('sort_order') ?? -1;

        $image = CmsAlbumImage::create([
            'album_id' => $albumId,
            'file_id' => $data['file_id'],
            'title' => $data['title'] ?? null,
            'caption' => $data['caption'] ?? null,
            'sort_order' => $maxOrder + 1,
            'config' => $data['config'] ?? [],
        ]);

        $this->logger->logCreate('album_image', $image, $image->toArray());

        return $image;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateImage(CmsAlbumImage $image, array $data): CmsAlbumImage
    {
        $oldData = $image->toArray();
        $image->update($data);
        $this->logger->logUpdate('album_image', $image, $oldData);

        return $image;
    }

    public function deleteImageInAlbum(int $albumId, int $imageId): void
    {
        $image = $this->findAlbumImageOrFailInAlbum($albumId, $imageId);
        $this->logger->logDelete('album_image', $image);
        $image->delete();
    }

    /**
     * @param  array<int, int>  $ids  Map of position => image_id
     */
    public function reorderImagesInAlbum(int $albumId, array $ids): void
    {
        $album = CmsAlbum::findOrFail($albumId);

        DB::transaction(function () use ($albumId, $ids): void {
            foreach ($ids as $order => $id) {
                CmsAlbumImage::where('album_id', $albumId)
                    ->where('id', $id)
                    ->update(['sort_order' => $order]);
            }
        });

        $this->logger->logReorder('album', 'images', $album, $ids);
    }

    // ----------------------------------------------- Menu-item picker helpers

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchAlbumOptions(string $query, int $limit = 20): array
    {
        return CmsAlbum::where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'title'])
            ->map(fn (CmsAlbum $a) => ['value' => $a->id, 'label' => $a->title])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadAlbumOptions(int|array $ids): array
    {
        return CmsAlbum::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn (CmsAlbum $a) => ['value' => $a->id, 'label' => $a->title])
            ->all();
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchAlbumImageOptions(string $query, int $limit = 20): array
    {
        return CmsAlbumImage::with('album')
            ->where(function ($q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhereHas('album', fn ($aq) => $aq->where('title', 'like', "%{$query}%"));
            })
            ->limit($limit)
            ->get()
            ->map(fn (CmsAlbumImage $i) => [
                'value' => $i->id,
                'label' => $i->album->title.' › '.($i->title ?: "Image #{$i->id}"),
            ])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadAlbumImageOptions(int|array $ids): array
    {
        return CmsAlbumImage::with('album')
            ->whereIn('id', (array) $ids)
            ->get()
            ->map(fn (CmsAlbumImage $i) => [
                'value' => $i->id,
                'label' => $i->album->title.' › '.($i->title ?: "Image #{$i->id}"),
            ])
            ->all();
    }

    // -------------------------------------------------- Observer cascade helpers

    /**
     * Auto-set the album cover from the first image added if none exists.
     * Called from CmsAlbumImageObserver::created.
     */
    public function applyAutoCoverOnImageCreate(CmsAlbumImage $image): void
    {
        /** @var CmsAlbum|null $album */
        $album = $image->album()->first();

        if (! $album instanceof CmsAlbum || $album->cover_image !== null) {
            return;
        }

        $image->loadMissing('file');
        $file = $image->file;

        if ($file instanceof FmFile) {
            $album->update(['cover_image' => $file->getUrl()]);
        }
    }

    /**
     * Recompute album cover after an image is deleted.
     * Called from CmsAlbumImageObserver::deleted.
     */
    public function recomputeCoverOnImageDelete(CmsAlbumImage $image): void
    {
        /** @var CmsAlbum|null $album */
        $album = $image->album()->first();

        if (! $album instanceof CmsAlbum || $album->cover_image === null) {
            return;
        }

        $image->loadMissing('file');
        $file = $image->file;
        $imageUrl = $file instanceof FmFile ? $file->getUrl() : null;

        if ($imageUrl === null || $album->cover_image !== $imageUrl) {
            return;
        }

        $next = CmsAlbumImage::where('album_id', $album->id)
            ->where('id', '!=', $image->id)
            ->with('file')
            ->orderBy('sort_order')
            ->get()
            ->first(fn (CmsAlbumImage $i): bool => $i->file instanceof FmFile);

        $newCover = $next?->file instanceof FmFile ? $next->file->getUrl() : null;
        $album->update(['cover_image' => $newCover]);
    }

    /**
     * @return 'asc'|'desc'
     */
    private function normalizeDirection(string $direction): string
    {
        $normalized = strtolower($direction);

        return in_array($normalized, ['asc', 'desc'], true) ? $normalized : 'asc';
    }
}
