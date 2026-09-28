<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VmEngine\Cms\Http\Controllers\SitemapController;

// Sitemap at root — no module URL prefix
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
