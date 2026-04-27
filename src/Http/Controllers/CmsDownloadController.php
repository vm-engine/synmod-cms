<?php

declare(strict_types=1);

namespace VmEngine\Cms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use VmEngine\Cms\Enums\DownloadAccess;
use VmEngine\Cms\Services\CmsDownloadService;

class CmsDownloadController extends Controller
{
    public function __construct(private readonly CmsDownloadService $downloads) {}

    public function __invoke(int $id): StreamedResponse|RedirectResponse
    {
        $downloadFile = $this->downloads->findFileOrFailById($id, ['download', 'file']);

        // Ensure the parent download is active
        abort_if($downloadFile->download->status !== 'active', 404);

        // Enforce private access
        if ($downloadFile->download->access === DownloadAccess::Private && ! auth()->check()) {
            return redirect()->route('auth.login');
        }

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
