<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsAlbumImage;
use VmEngine\Fm\Models\FmFile;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->makeImage = function (CmsAlbum $album): CmsAlbumImage {
        $file = FmFile::create([
            'disk' => 'public',
            'folder_path' => 'fm/public',
            'relative_path' => 'test-image.jpg',
            'filename' => 'test-image.jpg',
            'original_name' => 'test-image.jpg',
            'extension' => 'jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'has_thumbnail' => false,
            'is_trashed' => false,
        ]);

        return CmsAlbumImage::factory()->create(['album_id' => $album->id, 'file_id' => $file->id, 'caption' => 'Beach']);
    };
});

// Alpine runs in its CSP build: no JSON global (so no @js()/Js::from, which emit
// JSON.parse for non-empty data) and no ?. / ?? / arrow functions in attributes.
describe('Alpine CSP compatibility', function () {
    it('renders the album lightbox data without JSON.parse', function () {
        $album = CmsAlbum::factory()->create(['type' => 'normal', 'status' => 'active']);
        ($this->makeImage)($album);

        Livewire::test('cms::frontend.album-viewer', ['album_id' => $album->id])
            ->assertSeeHtml('cmsAlbumViewer([{')
            ->assertDontSeeHtml('JSON.parse')
            ->assertSeeHtml('x-text="currentCaption()"');
    });

    it('renders the banner slider data without JSON.parse', function () {
        $album = CmsAlbum::factory()->create(['slug' => 'hero', 'type' => 'banner', 'status' => 'active']);
        ($this->makeImage)($album);

        expect(Blade::render('<x-cms::widget.banner-slider album="hero" />'))
            ->toContain('cmsBannerSlider([{')
            ->not->toContain('JSON.parse');
    });

    it('uses only CSP-safe Alpine expressions in frontend templates', function () {
        $root = dirname(__DIR__, 2);
        $files = [
            ...glob("{$root}/resources/views/components/frontend/*/*.blade.php"),
            ...glob("{$root}/resources/views/components/widget/*/*.blade.php"),
        ];
        $offenders = [];

        foreach ($files as $file) {
            foreach (file($file) as $number => $line) {
                $unsafeExpression = preg_match('/(x-[a-z:.-]+|@[a-z.]+|:[a-z-]+)="[^"]*(\?\.|\?\?|=>)/', $line);
                $jsonParse = str_contains($line, '@js(') || str_contains($line, 'Js::from');

                if ($unsafeExpression || $jsonParse) {
                    $offenders[] = str_replace("{$root}/", '', $file).':'.($number + 1);
                }
            }
        }

        expect($offenders)->toBe([]);
    });

    it('uses no JS globals in Alpine attributes in any CMS view', function () {
        $root = dirname(__DIR__, 2);
        $offenders = [];

        $views = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/resources/views"));
        foreach ($views as $file) {
            if (! str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }

            // <script> blocks are real JS (Alpine.data registrations) — only markup is evaluated by the CSP parser.
            $markup = preg_replace('#<script\b.*?</script>#s', '', (string) file_get_contents($file->getPathname()));

            if (preg_match('/\b(parseInt|parseFloat|JSON\.|Math\.|window\.|document\.)/', (string) $markup, $match)) {
                $offenders[] = str_replace("{$root}/", '', $file->getPathname()).': '.$match[1];
            }
        }

        expect($offenders)->toBe([]);
    });
});
