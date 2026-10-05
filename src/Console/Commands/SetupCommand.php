<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Cms\Enums\RteDriver;

use function Laravel\Prompts\select;

/**
 * Wires CMS assets into the host app and picks the default rich-text editor:
 * cms.js (admin: editors, sortable) into app.js, cms-frontend.js (public:
 * search box) into frontend.js. Both editors are always wired so the choice
 * can change later from CMS settings.
 */
class SetupCommand extends Command
{
    protected $signature = 'cms:setup';

    protected $description = 'Wire CMS assets (Jodit + TinyMCE) into the host app and choose the default rich-text editor';

    private const CMS_JS_IMPORT = "import '../../vendor/vm-engine/synmod-cms/resources/js/cms.js';";

    private const CMS_FRONTEND_JS_IMPORT = "import '../../vendor/vm-engine/synmod-cms/resources/js/cms-frontend.js';";

    private const LEGACY_SYNAPPS_IMPORT = 'import "../../../vendor/vm-engine/synmod-cms/resources/js/cms.js";';

    private const TINYMCE_TARGET = "\n                    // TinyMCE (synmod-cms) — static assets (skins, themes, icons, plugins)"
        ."\n                    { src: 'node_modules/tinymce', dest: 'tinymce' },";

    public function handle(): int
    {
        $this->patchEntry('app.js', self::CMS_JS_IMPORT, 'synmod-cms/resources/js/cms.js');
        $this->patchEntry('frontend.js', self::CMS_FRONTEND_JS_IMPORT, 'synmod-cms/resources/js/cms-frontend.js');
        $this->removeLegacySynappsImport();
        $this->patchViteConfig();

        $driver = (string) select(
            label: 'Default rich-text editor',
            options: array_column(RteDriver::options(), 'label', 'value'),
            default: RteDriver::current()->value,
        );

        dbconf(['cms.editor.driver' => $driver]);

        $this->call('cms:search-reindex');

        $this->components->success('CMS setup complete. Default editor: '.RteDriver::from($driver)->label().'.');
        $this->line('  Next: run `npm install` (jodit, tinymce) and `npm run build`.');

        return self::SUCCESS;
    }

    /**
     * Append $import to resources/js/{$file} unless $marker is already there.
     * app.js gets the admin bundle (editors, sortable), frontend.js the public one (search box).
     */
    private function patchEntry(string $file, string $import, string $marker): void
    {
        $path = base_path("resources/js/{$file}");
        $contents = $this->read($path);

        if ($contents === null) {
            $this->warn("  resources/js/{$file} not found — add manually: {$import}");

            return;
        }

        if (str_contains($contents, $marker)) {
            $this->line("  {$file} — already patched (skipped)");

            return;
        }

        file_put_contents($path, rtrim($contents).PHP_EOL.$import.PHP_EOL);
        $this->line("  {$file} — CMS JS import added");
    }

    /**
     * The old mod-cms:patch-resources appended the import to synapps.js, which
     * loads on every page (frontend included); it now lives in app.js only.
     */
    private function removeLegacySynappsImport(): void
    {
        $path = base_path('synapps/resources/js/synapps.js');
        $contents = $this->read($path);

        if ($contents === null || ! str_contains($contents, self::LEGACY_SYNAPPS_IMPORT)) {
            return;
        }

        file_put_contents($path, str_replace(self::LEGACY_SYNAPPS_IMPORT.PHP_EOL, '', $contents));
        $this->line('  synapps.js — legacy CMS JS import removed (now loaded from app.js)');
    }

    private function patchViteConfig(): void
    {
        $path = base_path('vite.config.js');
        $contents = $this->read($path);

        if ($contents !== null && str_contains($contents, 'node_modules/tinymce')) {
            $this->line('  vite.config.js — already patched (skipped)');

            return;
        }

        $count = 0;
        $patched = $contents === null ? null : preg_replace(
            '/(viteStaticCopy\(\{\s*targets:\s*\[)/',
            '$1'.self::TINYMCE_TARGET,
            $contents,
            1,
            $count,
        );

        if ($patched === null || $count === 0) {
            $this->warn('  vite.config.js — viteStaticCopy targets not found; add it manually:');
            $this->line("    { src: 'node_modules/tinymce', dest: 'tinymce' }");

            return;
        }

        file_put_contents($path, $patched);
        $this->line('  vite.config.js — TinyMCE static copy added');
    }

    private function read(string $path): ?string
    {
        if (! is_file($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        return $contents === false ? null : $contents;
    }
}
