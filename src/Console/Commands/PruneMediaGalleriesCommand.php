<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Cms\Services\CmsMediaGalleryService;

class PruneMediaGalleriesCommand extends Command
{
    protected $signature = 'cms:prune-media-galleries {--dry-run : Report how many galleries would be deleted without deleting them}';

    protected $description = 'Delete media galleries whose page, article or download file no longer exists (e.g. after a section or download was deleted)';

    public function handle(CmsMediaGalleryService $galleries): int
    {
        if ($this->option('dry-run')) {
            $orphans = $galleries->orphans();

            foreach ($orphans as $gallery) {
                $this->line(sprintf('  #%d %s (%s #%d)', $gallery->id, $gallery->tag(), class_basename($gallery->owner_type), $gallery->owner_id));
            }

            $this->components->info($orphans->count().' orphaned media galleries would be deleted.');

            return self::SUCCESS;
        }

        $this->components->success($galleries->pruneOrphans().' orphaned media galleries deleted.');

        return self::SUCCESS;
    }
}
