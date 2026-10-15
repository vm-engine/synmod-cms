<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Cms\Support\CmsPagePaletteIndex;

class PaletteReindexCommand extends Command
{
    protected $signature = 'cms:palette-reindex';

    protected $description = 'Rebuild the CMS pages in the backend global search (Ctrl/Cmd+K palette)';

    public function handle(): int
    {
        if (! synsearchable()->isEnabled()) {
            $this->warn('  Global search is not set up — run `php artisan synapps:searchable-setup` first.');

            return self::SUCCESS;
        }

        $this->line('  '.CmsPagePaletteIndex::reindex().' pages indexed');

        return self::SUCCESS;
    }
}
