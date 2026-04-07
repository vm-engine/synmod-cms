<?php

// Frontend public routes with /cms/ prefix are registered via fallback routes.
// See CmsFallbackService and model observers for slug registration.

use Illuminate\Support\Facades\Route;
use VmEngine\Cms\Http\Controllers\CmsDownloadController;

// File download streaming — enforces access control and serves with Content-Disposition: attachment
Route::get('/cms-download/{id}', CmsDownloadController::class)->name('download.stream');
