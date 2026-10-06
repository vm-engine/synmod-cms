<?php

declare(strict_types=1);

namespace VmEngine\Cms\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VmEngine\Cms\Services\CmsAccessService;
use VmEngine\Cms\Services\CmsDownloadService;

class CmsDownloadController extends Controller
{
    public function __construct(
        private readonly CmsDownloadService $downloads,
        private readonly CmsAccessService $access,
    ) {}

    public function __invoke(int $id): StreamedResponse
    {
        $downloadFile = $this->downloads->findFileOrFailById($id, ['download', 'file']);

        // Ensure the parent download is active
        abort_if($downloadFile->download->status !== 'active', 404);

        // Guests are redirected to login, other unauthorized users get a 404
        $this->access->authorizeView($downloadFile->download);

        $fmFile = $downloadFile->file;

        abort_if($fmFile === null, 404);

        $storagePath = $fmFile->getStoragePath();
        $disk = Storage::disk($fmFile->disk);

        abort_unless($disk->exists($storagePath), 404);

        $filename = $fmFile->original_name ?: $fmFile->filename;

        return $disk->download($storagePath, $filename, [
            'Content-Type' => $fmFile->mime_type,
        ]);
    }
}
