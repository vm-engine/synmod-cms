<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use VmEngine\Cms\Http\Controllers\FeedController;
use VmEngine\Cms\Http\Controllers\SitemapController;
use VmEngine\SynFallback\Facades\Fallback;
use VmEngine\SynFallback\Http\Middleware\SetFallbackLocale;

// Sitemap at root — no module URL prefix
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

// RSS feeds at root (primary locale); locale-prefixed copies only when multi-language is on
$feeds = function (string $nameSuffix = ''): void {
    Route::get('/feed/articles.xml', [FeedController::class, 'articles'])->name('feed.articles'.$nameSuffix);
    Route::get('/feed/articles/{section}.xml', [FeedController::class, 'section'])
        ->where('section', '[A-Za-z0-9_-]+')
        ->name('feed.section'.$nameSuffix);
    Route::get('/feed/downloads.xml', [FeedController::class, 'downloads'])->name('feed.downloads'.$nameSuffix);
    Route::get('/feed/gallery.xml', [FeedController::class, 'gallery'])->name('feed.gallery'.$nameSuffix);
};

$feeds();

if (Fallback::isMultiLanguage()) {
    $locales = array_map(
        fn (string $locale): string => preg_quote($locale, '/'),
        array_filter((array) config('synapps.apps.enabled_languages', []), 'is_string'),
    );

    Route::prefix('{locale}')
        ->where(['locale' => implode('|', $locales)])
        ->middleware(SetFallbackLocale::class)
        ->group(fn () => $feeds('.locale'));
}
