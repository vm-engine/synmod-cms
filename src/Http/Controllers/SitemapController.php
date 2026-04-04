<?php

declare(strict_types=1);

namespace VmEngine\Cms\Http\Controllers;

use Illuminate\Http\Response;
use VmEngine\Cms\Services\CmsSitemapService;

class SitemapController
{
    public function __invoke(CmsSitemapService $service): Response
    {
        return response($service->generate(), 200, [
            'Content-Type' => 'application/xml',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
