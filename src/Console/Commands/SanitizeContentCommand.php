<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

class SanitizeContentCommand extends Command
{
    protected $signature = 'cms:sanitize-content {--dry-run : Report how many rows would change without saving}';

    protected $description = 'Re-clean RTE body/description fields on existing CMS content through the cms_rte purifier profile';

    /**
     * @var array<class-string<Model>, string>
     */
    private const TARGETS = [
        CmsArticle::class => 'body',
        CmsPage::class => 'body',
        CmsSection::class => 'description',
        CmsCategory::class => 'description',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        foreach (self::TARGETS as $modelClass => $field) {
            $changed = $this->sanitizeModel($modelClass, $field, $dryRun);

            $this->components->info(
                sprintf('%s.%s: %d row(s) %s', class_basename($modelClass), $field, $changed, $dryRun ? 'would change' : 'updated')
            );
        }

        $this->components->success($dryRun ? 'Dry run complete. No changes were saved.' : 'Content sanitization complete.');

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function sanitizeModel(string $modelClass, string $field, bool $dryRun): int
    {
        $changed = 0;

        DB::beginTransaction();

        $modelClass::query()->whereNotNull($field)->chunkById(100, function ($rows) use ($field, $dryRun, &$changed): void {
            foreach ($rows as $row) {
                $original = $row->getAttribute($field);
                $clean = Purifier::clean($original, 'cms_rte');

                if ($clean === $original) {
                    continue;
                }

                $changed++;

                if (! $dryRun) {
                    $row->forceFill([$field => $clean])->saveQuietly();
                }
            }
        });

        if ($dryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        return $changed;
    }
}
