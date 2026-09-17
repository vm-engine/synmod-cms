<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;

class PatchResourcesCommand extends Command
{
    protected $signature = 'mod-cms:patch-resources';

    protected $description = 'Patch main project resource files to import CMS module assets';

    public function handle(): int
    {
        $this->patchSynappsJs();
        $this->patchViteConfig();

        $this->info('mod-cms:patch-resources complete.');

        return self::SUCCESS;
    }

    private function patchSynappsJs(): void
    {
        $path = base_path('synapps/resources/js/synapps.js');

        if (! file_exists($path)) {
            $this->warn("File not found: {$path}");

            return;
        }

        $import = 'import "../../../vendor/vm-engine/synmod-cms/resources/js/cms.js";';
        $contents = file_get_contents($path);

        if ($contents === false) {
            $this->warn("Could not read {$path}");

            return;
        }

        if (str_contains($contents, $import)) {
            $this->line('  synapps.js — already patched (skipped)');

            return;
        }

        file_put_contents($path, $contents.PHP_EOL.$import.PHP_EOL);
        $this->line('  synapps.js — patched (CMS JS import added)');
    }

    private function patchViteConfig(): void
    {
        $path = base_path('vite.config.js');

        if (! file_exists($path)) {
            $this->warn("File not found: {$path}");

            return;
        }

        $contents = file_get_contents($path);
        $marker = 'node_modules/tinymce';

        if ($contents === false) {
            $this->warn("Could not read {$path}");

            return;
        }

        if (str_contains($contents, $marker)) {
            $this->line('  vite.config.js — already patched (skipped)');

            return;
        }

        // Insert TinyMCE copy target before the closing of viteStaticCopy targets array
        $tinyMceTarget = <<<'JS'
                // TinyMCE — copy all static assets (skins, themes, icons, plugins)
                {
                    src: 'node_modules/tinymce',
                    dest: 'tinymce'
                }
JS;

        // Find the last closing bracket of targets array inside viteStaticCopy
        $insertBefore = "            ]\n        })\n        ] : []),";
        $replacement = $tinyMceTarget."\n".$insertBefore;

        if (str_contains($contents, $insertBefore)) {
            $patched = str_replace($insertBefore, $replacement, $contents);
            file_put_contents($path, $patched);
            $this->line('  vite.config.js — patched (TinyMCE static copy added)');
        } else {
            $this->warn('  vite.config.js — could not locate insertion point; patch skipped');
            $this->warn('  Add TinyMCE static copy target manually to viteStaticCopy targets.');
        }
    }
}
