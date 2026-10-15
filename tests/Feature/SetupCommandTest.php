<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use VmEngine\Cms\Enums\RteDriver;
use VmEngine\SynFallback\Facades\Fallback;

uses(RefreshDatabase::class);

const CMS_JS_IMPORT = "import '../../vendor/vm-engine/synmod-cms/resources/js/cms.js';";
const LEGACY_IMPORT = 'import "../../../vendor/vm-engine/synmod-cms/resources/js/cms.js";';
const CMS_FRONTEND_JS_IMPORT = "import '../../vendor/vm-engine/synmod-cms/resources/js/cms-frontend.js';";
const SYNAPSE_COMPONENTS_IMPORT = "import '../../synapps/resources/js/synapse-components.js';";
const FLATPICKR_CSS_IMPORT = "import 'flatpickr/dist/flatpickr.min.css';";

// Fixtures live in a temp dir set as the app base path, so the real host
// files are never touched — afterEach is not guaranteed to run when a
// PendingCommand fails, so restoring real files from a backup is unsafe.
beforeEach(function () {
    $this->basePath = sys_get_temp_dir().'/cms-setup-test-'.uniqid();
    mkdir($this->basePath.'/resources/js', 0755, true);
    mkdir($this->basePath.'/synapps/resources/js', 0755, true);
    mkdir($this->basePath.'/public', 0755, true);
    mkdir($this->basePath.'/synapps/resources/themes/default/layouts/partials', 0755, true);
    config(['synapps.apps.themes.active' => 'default']);

    $this->files = [
        'app' => $this->basePath.'/resources/js/app.js',
        'frontend' => $this->basePath.'/resources/js/frontend.js',
        'vite' => $this->basePath.'/vite.config.js',
        'synapps' => $this->basePath.'/synapps/resources/js/synapps.js',
        'robots' => $this->basePath.'/public/robots.txt',
        'head' => $this->basePath.'/synapps/resources/themes/default/layouts/partials/cms-head.blade.php',
    ];

    file_put_contents($this->files['app'], "import '../../synapps/resources/js/synapps-app.js';\n");
    file_put_contents($this->files['frontend'], "import '../../synapps/resources/js/synapps-frontend.js';\n");
    file_put_contents($this->files['robots'], "User-agent: *\nDisallow:\n");
    file_put_contents($this->files['head'], "<x-cms::hreflang />\n<x-cms::analytics-head />\n");
    file_put_contents($this->files['synapps'], "import './synapse-components.js';\n".LEGACY_IMPORT."\n");
    file_put_contents($this->files['vite'], <<<'JS'
        export default defineConfig({
            plugins: [
                viteStaticCopy({
                    targets: [
                        { src: 'synapps/resources/assets/**/*', dest: '../assets' },
                    ],
                }),
            ],
        });
        JS);

    $this->app->setBasePath($this->basePath);
});

afterEach(function () {
    File::deleteDirectory($this->basePath);
});

it('wires assets and stores the chosen editor', function () {
    $this->artisan('cms:setup')
        ->expectsQuestion('Default rich-text editor', 'tinymce')
        ->assertSuccessful();

    expect(file_get_contents($this->files['app']))->toContain(CMS_JS_IMPORT)
        ->and(file_get_contents($this->files['frontend']))->toContain(CMS_FRONTEND_JS_IMPORT)
        ->and(file_get_contents($this->files['frontend']))->not->toContain(CMS_JS_IMPORT)
        ->and(file_get_contents($this->files['frontend']))->toContain(FLATPICKR_CSS_IMPORT."\n".SYNAPSE_COMPONENTS_IMPORT)
        ->and(file_get_contents($this->files['synapps']))->not->toContain('synmod-cms/resources/js/cms.js')
        ->and(file_get_contents($this->files['vite']))->toContain("src: 'node_modules/tinymce'")
        ->and(RteDriver::current())->toBe(RteDriver::Tinymce);
});

it('is idempotent', function () {
    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();
    $first = array_map('file_get_contents', $this->files);

    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();

    expect(array_map('file_get_contents', $this->files))->toBe($first)
        ->and(substr_count($first['app'], CMS_JS_IMPORT))->toBe(1)
        ->and(substr_count($first['frontend'], CMS_FRONTEND_JS_IMPORT))->toBe(1)
        ->and(substr_count($first['frontend'], SYNAPSE_COMPONENTS_IMPORT))->toBe(1)
        ->and(substr_count($first['vite'], 'node_modules/tinymce'))->toBe(1);
});

it('skips the synapse components import when synapps-frontend.js already has it', function () {
    file_put_contents($this->basePath.'/synapps/resources/js/synapps-frontend.js', "import './synapse-components.js';\n");

    $this->artisan('cms:setup')
        ->expectsQuestion('Default rich-text editor', 'jodit')
        ->assertSuccessful();

    expect(file_get_contents($this->files['frontend']))->not->toContain('synapse-components.js')
        ->not->toContain('flatpickr');
});

it('warns when the vite anchor is missing', function () {
    file_put_contents($this->files['vite'], "export default defineConfig({ plugins: [] });\n");

    $this->artisan('cms:setup')
        ->expectsQuestion('Default rich-text editor', 'jodit')
        ->expectsOutputToContain('add it manually')
        ->assertSuccessful();

    expect(file_get_contents($this->files['vite']))->not->toContain('node_modules/tinymce');
});

it('keeps cms:patch-resources and mod-cms:patch-resources as deprecated aliases', function () {
    expect(Artisan::all())->toHaveKeys(['cms:patch-resources', 'mod-cms:patch-resources']);

    $this->artisan('mod-cms:patch-resources')
        ->expectsOutputToContain('deprecated')
        ->expectsQuestion('Default rich-text editor', 'jodit')
        ->assertSuccessful();

    expect(file_get_contents($this->files['app']))->toContain(CMS_JS_IMPORT);
});

it('warns instead of failing when frontend.js is missing', function () {
    unlink($this->files['frontend']);
    unset($this->files['frontend']);

    $this->artisan('cms:setup')
        ->expectsQuestion('Default rich-text editor', 'jodit')
        ->expectsOutputToContain('resources/js/frontend.js not found')
        ->assertSuccessful();
});

it('ships the search box in the frontend bundle only', function () {
    $js = dirname(__DIR__, 2).'/resources/js';

    expect(file_get_contents("{$js}/cms-frontend.js"))->toContain("Alpine.data('cmsSearchBox'")
        ->not->toContain('sortablejs')
        ->and(file_get_contents("{$js}/cms.js"))->not->toContain('cmsSearchBox');
});

it('adds the sitemap to robots.txt once', function () {
    config(['app.url' => 'https://example.com']);

    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();
    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();

    expect(file_get_contents($this->files['robots']))
        ->toBe("User-agent: *\nDisallow:\n\nSitemap: https://example.com/sitemap.xml\n");
});

it('creates robots.txt when the host has none', function () {
    config(['app.url' => 'https://example.com']);
    unlink($this->files['robots']);

    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();

    expect(file_get_contents($this->files['robots']))
        ->toBe("User-agent: *\nDisallow:\n\nSitemap: https://example.com/sitemap.xml\n");
});

it('leaves an existing sitemap directive alone', function () {
    file_put_contents($this->files['robots'], "User-agent: *\nSitemap: https://cdn.example.com/map.xml\n");

    $this->artisan('cms:patch-resources')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();

    expect(file_get_contents($this->files['robots']))->toBe("User-agent: *\nSitemap: https://cdn.example.com/map.xml\n");
});

it('adds the feed links to the theme head partial once', function () {
    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();
    $this->artisan('cms:setup')->expectsQuestion('Default rich-text editor', 'jodit')->assertSuccessful();

    expect(file_get_contents($this->files['head']))->toBe(
        "<x-cms::hreflang />\n<x-cms::analytics-head />\n{{-- CMS: RSS feed autodiscovery --}}\n<x-cms::feed-links />\n"
    );
});

it('warns when the theme head partial is missing', function () {
    unlink($this->files['head']);
    unset($this->files['head']);

    $this->artisan('cms:setup')
        ->expectsQuestion('Default rich-text editor', 'jodit')
        ->expectsOutputToContain('add <x-cms::feed-links /> to your layout <head> manually')
        ->assertSuccessful();
});

it('registers the frontend search page even when the search index cannot be rebuilt', function () {
    Schema::drop('cms_searchables');

    $this->artisan('cms:setup')
        ->expectsQuestion('Default rich-text editor', 'jodit')
        ->assertSuccessful();

    expect(Fallback::exists('search'))->toBeTrue();
});
