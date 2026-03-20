<?php

use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

describe('CMS Module Discovery', function () {
    it('checks package module is registered and working', function () {
        // Verify the package is loaded in composer.json
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);

        expect($composerJson['require'] ?? [])
            ->toHaveKey('vm-engine/synmod-cms');

        // Verify module models are autoloaded correctly
        expect(class_exists(CmsPage::class))->toBeTrue();
        expect(class_exists(CmsSection::class))->toBeTrue();
        expect(class_exists(CmsArticle::class))->toBeTrue();

        // Verify routes are registered
        $routeUrl = backend_route('cms.page.index');
        expect($routeUrl)->toBeString()->not->toBeEmpty();
    });
});
