<?php

declare(strict_types=1);

namespace VmEngine\Cms\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSearchable;
use VmEngine\Cms\Services\CmsFallbackService;
use VmEngine\Cms\Services\CmsSearchIndexer;

class SearchReindexCommand extends Command
{
    protected $signature = 'cms:search-reindex {--type=* : Only these types: page, article, category, album, download}';

    protected $description = 'Rebuild the public CMS search index (cms_searchables) from routed content';

    /** @var array<string, class-string<CmsPage|CmsArticle|CmsCategory|CmsAlbum|CmsDownload>> */
    private const SOURCES = [
        'page' => CmsPage::class,
        'article' => CmsArticle::class,
        'category' => CmsCategory::class,
        'album' => CmsAlbum::class,
        'download' => CmsDownload::class,
    ];

    public function handle(CmsFallbackService $fallback, CmsSearchIndexer $indexer): int
    {
        // The search page route needs no index table: register it first so a
        // failed or partial rebuild never leaves /{locale}/search a 404.
        $fallback->syncSearchPage();

        if (! $indexer->isEnabled()) {
            $this->components->error('The cms_searchables table is missing — run `php artisan migrate` first.');

            return self::FAILURE;
        }

        /** @var list<string> $types */
        $types = $this->option('type') ?: array_keys(self::SOURCES);
        $unknown = array_diff($types, array_keys(self::SOURCES));

        if ($unknown !== []) {
            $this->components->error('Unknown type(s): '.implode(', ', $unknown).'. Use: '.implode(', ', array_keys(self::SOURCES)).'.');

            return self::FAILURE;
        }

        $indexer->clear($types);

        // Re-sync through the fallback service so the index matches the routes
        // exactly (this also re-registers the routes — idempotent).
        foreach ($types as $type) {
            self::SOURCES[$type]::query()->chunkById(100, function ($models) use ($fallback): void {
                foreach ($models as $model) {
                    $this->sync($fallback, $model);
                }
            });
        }

        $this->table(
            ['Type', 'Rows'],
            CmsSearchable::query()->selectRaw('searchable_type, count(*) as total')->groupBy('searchable_type')
                ->pluck('total', 'searchable_type')->map(fn ($total, $type) => [$type, $total])->values()->all(),
        );
        $this->components->success('CMS search index rebuilt.');

        return self::SUCCESS;
    }

    private function sync(CmsFallbackService $fallback, Model $model): void
    {
        match (true) {
            $model instanceof CmsPage => $fallback->syncPage($model),
            $model instanceof CmsArticle => $fallback->syncArticle($model),
            $model instanceof CmsCategory => $fallback->syncCategory($model),
            $model instanceof CmsAlbum => $fallback->syncAlbum($model),
            $model instanceof CmsDownload => $fallback->syncDownload($model),
            default => null,
        };
    }
}
