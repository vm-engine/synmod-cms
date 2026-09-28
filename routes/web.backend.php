<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::group([], function () {

    // Pages
    Route::livewire('/page', 'cms::backend.page-list')->name('page.index')
        ->middleware('can-access:cms.page.read');
    Route::livewire('/page/form/{id?}', 'cms::backend.page-form')->name('page.form')
        ->middleware('can-access:cms.page.create|cms.page.update');

    // Sections
    Route::livewire('/section', 'cms::backend.section-list')->name('section.index')
        ->middleware('can-access:cms.article.section.read');
    Route::livewire('/section/form/{id?}', 'cms::backend.section-form')->name('section.form')
        ->middleware('can-access:cms.article.section.create|cms.article.section.update');

    // Categories (always section-scoped)
    Route::livewire('/section/{sectionId}/category', 'cms::backend.category-list')->name('category.index')
        ->middleware('can-access:cms.article.category.read');
    Route::livewire('/section/{sectionId}/category/form', 'cms::backend.category-form')->name('category.form')
        ->middleware('can-access:cms.article.category.create|cms.article.category.update');
    Route::livewire('/section/{sectionId}/category/{id}/form', 'cms::backend.category-form')->name('category.edit')
        ->middleware('can-access:cms.article.category.create|cms.article.category.update');

    // Articles - global list (for nav menu, overview)
    Route::livewire('/article', 'cms::backend.article-list')->name('article.index')
        ->middleware('can-access:cms.article.read');
    // Articles - section-scoped (for create/edit and section-level list)
    Route::livewire('/section/{sectionId}/article', 'cms::backend.article-list')->name('section.article.index')
        ->middleware('can-access:cms.article.read');
    Route::livewire('/section/{sectionId}/article/form', 'cms::backend.article-form')->name('section.article.form')
        ->middleware('can-access:cms.article.create|cms.article.update');
    Route::livewire('/section/{sectionId}/article/{id}/form', 'cms::backend.article-form')->name('section.article.edit')
        ->middleware('can-access:cms.article.create|cms.article.update');

    // Albums
    Route::livewire('/album', 'cms::backend.album-list')->name('album.index')
        ->middleware('can-access:cms.gallery.album.read|cms.gallery.banner.read');
    Route::livewire('/album/form/{id?}', 'cms::backend.album-form')->name('album.form')
        ->middleware('can-access:cms.gallery.album.create|cms.gallery.album.update|cms.gallery.banner.create|cms.gallery.banner.update');
    Route::livewire('/album/{albumId}/images', 'cms::backend.album-image-manager')->name('album.images')
        ->middleware('can-access:cms.gallery.image.read|cms.gallery.image.update');
    Route::livewire('/album/{albumId}/image/{id}/form', 'cms::backend.album-image-form')->name('album.image.form')
        ->middleware('can-access:cms.gallery.image.update');

    // Downloads
    Route::livewire('/download', 'cms::backend.download-list')->name('download.index')
        ->middleware('can-access:cms.download.category.read');
    Route::livewire('/download/form/{id?}', 'cms::backend.download-form')->name('download.form')
        ->middleware('can-access:cms.download.category.create|cms.download.category.update');
    Route::livewire('/download/{downloadId}/files', 'cms::backend.download-file-manager')->name('download.files')
        ->middleware('can-access:cms.download.file.read|cms.download.file.update');
    Route::livewire('/download/{downloadId}/file/{id}/form', 'cms::backend.download-file-form')->name('download.file.form')
        ->middleware('can-access:cms.download.file.update');

    // Menus
    Route::livewire('/menu', 'cms::backend.menu-list')->name('menu.index')
        ->middleware('can-access:cms.menu.read');
    Route::livewire('/menu/{menuId}/items', 'cms::backend.menu-item-manager')->name('menu.items')
        ->middleware('can-access:cms.menu.update');

    // Tags
    Route::livewire('/tag', 'cms::backend.tag-list')->name('tag.index')
        ->middleware('can-access:cms.tag.read');

    // Settings
    Route::livewire('/settings', 'cms::backend.settings')->name('settings')
        ->middleware('can-access:cms.settings.read');
});
