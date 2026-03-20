<?php

use Illuminate\Support\Facades\Route;

Route::group([], function () {

    // Pages
    Route::livewire('/page', 'cms::backend.page-list')->name('page.index')
        ->middleware('can-access:cms.page.read');
    Route::livewire('/page/form/{id?}', 'cms::backend.page-form')->name('page.form')
        ->middleware('can-access:cms.page.create|cms.page.update');

    // Sections
    Route::livewire('/section', 'cms::backend.section-list')->name('section.index')
        ->middleware('can-access:cms.section.read');
    Route::livewire('/section/form/{id?}', 'cms::backend.section-form')->name('section.form')
        ->middleware('can-access:cms.section.create|cms.section.update');

    // Categories (always section-scoped)
    Route::livewire('/section/{sectionId}/category', 'cms::backend.category-list')->name('category.index')
        ->middleware('can-access:cms.section.read');
    Route::livewire('/section/{sectionId}/category/form', 'cms::backend.category-form')->name('category.form')
        ->middleware('can-access:cms.section.create|cms.section.update');
    Route::livewire('/section/{sectionId}/category/{id}/form', 'cms::backend.category-form')->name('category.edit')
        ->middleware('can-access:cms.section.create|cms.section.update');

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
        ->middleware('can-access:cms.gallery.read');
    Route::livewire('/album/form/{id?}', 'cms::backend.album-form')->name('album.form')
        ->middleware('can-access:cms.gallery.create|cms.gallery.update');
    Route::livewire('/album/{albumId}/images', 'cms::backend.album-image-manager')->name('album.images')
        ->middleware('can-access:cms.gallery.update');

    // Menus
    Route::livewire('/menu', 'cms::backend.menu-list')->name('menu.index')
        ->middleware('can-access:cms.menu.read');
    Route::livewire('/menu/{menuId}/items', 'cms::backend.menu-item-manager')->name('menu.items')
        ->middleware('can-access:cms.menu.update');

    // Tags
    Route::livewire('/tag', 'cms::backend.tag-list')->name('tag.index')
        ->middleware('can-access:cms.tag.read');
});
