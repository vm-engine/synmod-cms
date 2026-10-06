<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;

class CmsDownloadService
{
    public function __construct(private readonly CmsActivityLogger $logger) {}

    // -------------------------------------------------------------- Downloads

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findOrFailById(int $id, array $with = []): CmsDownload
    {
        return CmsDownload::with($with)->findOrFail($id);
    }

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findActiveOrFailById(int $id, array $with = []): CmsDownload
    {
        return CmsDownload::with($with)->active()->findOrFail($id);
    }

    /**
     * @param  array{q?: string, status?: string, visibility?: string}  $filters
     * @return LengthAwarePaginator<int, CmsDownload>
     */
    public function paginate(array $filters, string $sort, string $direction, int $perPage): LengthAwarePaginator
    {
        $query = CmsDownload::withCount('files');

        if (! empty($filters['q']) && strlen($filters['q']) > 2) {
            $query->search($filters['q']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        return $query->orderBy($sort, $this->normalizeDirection($direction))->paginate($perPage)->onEachSide(1);
    }

    /**
     * Frontend list: active downloads the current visitor may see.
     *
     * @return Collection<int, CmsDownload>
     */
    public function getFrontendList(): Collection
    {
        /** @var Collection<int, CmsDownload> $downloads */
        $downloads = CmsDownload::withCount('files')->active()->visibleTo()->orderBy('sort_order')->get();

        return $downloads;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createDownload(array $data, int $userId): CmsDownload
    {
        $data['created_by'] = $userId;
        $download = CmsDownload::create($data);
        $this->logger->logCreate('download', $download, $download->toArray());

        return $download;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDownload(CmsDownload $download, array $data): CmsDownload
    {
        $oldData = $download->toArray();
        $download->update($data);
        $this->logger->logUpdate('download', $download, $oldData);

        return $download;
    }

    public function deleteDownloadByToken(string $token, ?Closure $authorize = null): CmsDownload
    {
        $id = CmsDownload::validateDeleteToken($token);
        if (! $id) {
            throw new InvalidArgumentException(__('cms::labels.invalid_delete_token'));
        }

        $download = CmsDownload::findOrFail($id);

        $authorize?->__invoke($download);
        $this->logger->logDelete('download', $download);
        $download->delete();

        return $download;
    }

    // ----------------------------------------------------------- Download files

    /**
     * @param  array<int|string, mixed>  $with
     */
    public function findFileOrFailInDownload(int $downloadId, int $fileId, array $with = []): CmsDownloadFile
    {
        return CmsDownloadFile::with($with)
            ->where('download_id', $downloadId)
            ->findOrFail($fileId);
    }

    /**
     * Used by the public download controller.
     *
     * @param  array<int|string, mixed>  $with
     */
    public function findFileOrFailById(int $fileId, array $with = []): CmsDownloadFile
    {
        return CmsDownloadFile::with($with)->findOrFail($fileId);
    }

    /**
     * @return Collection<int, CmsDownloadFile>
     */
    public function getDownloadFiles(int $downloadId): Collection
    {
        /** @var Collection<int, CmsDownloadFile> $files */
        $files = CmsDownloadFile::where('download_id', $downloadId)
            ->with('file')
            ->orderBy('sort_order')
            ->get();

        return $files;
    }

    /**
     * @param  array{file_id: int, title?: string|null, description?: string|null, config?: array<string, mixed>}  $data
     */
    public function addFileToDownload(int $downloadId, array $data): CmsDownloadFile
    {
        $maxOrder = CmsDownloadFile::where('download_id', $downloadId)->max('sort_order') ?? -1;

        $file = CmsDownloadFile::create([
            'download_id' => $downloadId,
            'file_id' => $data['file_id'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'sort_order' => $maxOrder + 1,
            'config' => $data['config'] ?? [],
        ]);

        $this->logger->logCreate('download_file', $file, $file->toArray());

        return $file;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateFile(CmsDownloadFile $file, array $data): CmsDownloadFile
    {
        $oldData = $file->toArray();
        $file->update($data);
        $this->logger->logUpdate('download_file', $file, $oldData);

        return $file;
    }

    public function deleteFileInDownload(int $downloadId, int $fileId): void
    {
        $file = $this->findFileOrFailInDownload($downloadId, $fileId);
        $this->logger->logDelete('download_file', $file);
        $file->delete();
    }

    // ----------------------------------------------- Menu-item picker helpers

    /**
     * @return array<int, array{value: int, label: string}>
     */
    public function searchOptions(string $query, int $limit = 20): array
    {
        return CmsDownload::where('title', 'like', "%{$query}%")
            ->limit($limit)
            ->get(['id', 'title'])
            ->map(fn (CmsDownload $d) => ['value' => $d->id, 'label' => $d->title])
            ->all();
    }

    /**
     * @param  array<int, int>|int  $ids
     * @return array<int, array{value: int, label: string}>
     */
    public function loadOptions(int|array $ids): array
    {
        return CmsDownload::whereIn('id', (array) $ids)
            ->get(['id', 'title'])
            ->map(fn (CmsDownload $d) => ['value' => $d->id, 'label' => $d->title])
            ->all();
    }

    /**
     * @param  array<int, int>  $ids  Map of position => file_id
     */
    public function reorderFilesInDownload(int $downloadId, array $ids): void
    {
        $download = CmsDownload::findOrFail($downloadId);

        DB::transaction(function () use ($downloadId, $ids): void {
            foreach ($ids as $order => $id) {
                CmsDownloadFile::where('download_id', $downloadId)
                    ->where('id', $id)
                    ->update(['sort_order' => $order]);
            }
        });

        $this->logger->logReorder('download', 'files', $download, $ids);
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
