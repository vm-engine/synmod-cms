<?php

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Route;
use VmEngine\Cms\Services\CmsSitemapService;

Route::get('/sitemap.xml', function () {
    $service = app(CmsSitemapService::class);
    $xml = $service->generate();

    return Response::make($xml, 200, [
        'Content-Type' => 'application/xml',
        'Cache-Control' => 'public, max-age=3600',
    ]);
})->name('sitemap');
