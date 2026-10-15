<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use VmEngine\Cms\Models\CmsPage;
use VmEngine\Synapse\Facades\SynSearchable;

/**
 * CMS pages in the backend Ctrl/Cmd+K palette (not the public cms_searchables
 * index): title, path and slug, opening the page edit form. A silent no-op
 * until synapps:searchable-setup has created the searchables table.
 */
final class CmsPagePaletteIndex
{
    public const SOURCE = 'content:cms.page';

    public static function put(CmsPage $page): void
    {
        SynSearchable::put(
            source: self::SOURCE,
            key: (string) $page->id,
            title: $page->title,
            description: '/'.$page->full_slug,
            route: 'cms.page.form',
            routeParams: ['id' => $page->id],
            icon: 'ph ph-file-text',
            acl: 'cms.page.update',
            keywords: $page->slug,
        );
    }

    public static function forget(int $id): void
    {
        SynSearchable::forget(self::SOURCE, (string) $id);
    }

    /**
     * Rebuild every page entry.
     */
    public static function reindex(): int
    {
        if (! synsearchable()->isEnabled()) {
            return 0;
        }

        SynSearchable::forgetSource(self::SOURCE);
        $count = 0;

        CmsPage::query()->chunkById(200, function ($pages) use (&$count): void {
            foreach ($pages as $page) {
                self::put($page);
                $count++;
            }
        });

        return $count;
    }
}
