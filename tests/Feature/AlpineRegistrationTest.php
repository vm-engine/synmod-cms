<?php

declare(strict_types=1);

// alpine:init fires once per full page load. After a wire:navigate from another layout these
// entries load with Alpine already running, so an alpine:init-only registration never runs and
// the page logs "Undefined variable: <component>".
it('registers its Alpine components even when Alpine is already running', function (string $file) {
    $source = (string) file_get_contents(dirname(__DIR__, 2).'/resources/js/'.$file);

    expect($source)->toContain("window.Alpine ? register() : document.addEventListener('alpine:init', register)")
        ->not->toContain("document.addEventListener('alpine:init', () =>");
})->with(['cms.js', 'cms-frontend.js']);
