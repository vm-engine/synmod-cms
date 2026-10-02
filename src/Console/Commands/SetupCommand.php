<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Cms\Enums\RteDriver;

use function Laravel\Prompts\select;

/**
 * Wires CMS assets into the host app and picks the default rich-text editor.
 * Both editors are always wired so the choice can change later from CMS settings.
 */
class SetupCommand extends Command
{
    protected $signature = 'cms:setup';

    protected $description = 'Wire CMS assets (Jodit + TinyMCE) into the host app and choose the default rich-text editor';

    private const CMS_JS_IMPORT = "import '../../vendor/vm-engine/synmod-cms/resources/js/cms.js';";

    private const LEGACY_SYNAPPS_IMPORT = 'import "../../../vendor/vm-engine/synmod-cms/resources/js/cms.js";';

    private const TINYMCE_TARGET = "\n                    // TinyMCE (synmod-cms) — static assets (skins, themes, icons, plugins)"
        ."\n                    { src: 'node_modules/tinymce', dest: 'tinymce' },";

    public function handle(): int
    {
        $this->patchAppJs();
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

    private function patchAppJs(): void
    {
        $path = base_path('resources/js/app.js');
        $contents = $this->read($path);

        if ($contents === null) {
            $this->warn('  resources/js/app.js not found — add manually: '.self::CMS_JS_IMPORT);

            return;
        }

        if (str_contains($contents, 'synmod-cms/resources/js/cms.js')) {
            $this->line('  app.js — already patched (skipped)');

            return;
        }

        file_put_contents($path, rtrim($contents).PHP_EOL.self::CMS_JS_IMPORT.PHP_EOL);
        $this->line('  app.js — CMS JS import added');
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
