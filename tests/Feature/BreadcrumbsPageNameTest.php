<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

// The shared breadcrumbs partial renders x-text="pageName" from an ancestor x-data scope; a page
// that includes it without providing pageName logs "Alpine Expression Error: Undefined variable: pageName".
it('provides pageName to the breadcrumbs partial on every backend page', function () {
    $missing = collect(File::allFiles(dirname(__DIR__, 2).'/resources/views/components/backend'))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.blade.php'))
        ->filter(fn ($file) => str_contains($file->getContents(), 'partials.breadcrumbs'))
        ->reject(fn ($file) => str_contains($file->getContents(), 'pageName'))
        ->map(fn ($file) => $file->getFilename())
        ->values()
        ->all();

    expect($missing)->toBe([]);
});
