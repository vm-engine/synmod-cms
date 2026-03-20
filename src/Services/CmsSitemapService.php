<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;

class CmsSitemapService
{
    public function generate(): string
    {
        $urls = array_merge(
            $this->pageUrls(),
            $this->sectionUrls(),
            $this->categoryUrls(),
            $this->articleUrls(),
            $this->albumUrls(),
        );

        return $this->buildXml($urls);
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function pageUrls(): array
    {
        return CmsPage::published()
            ->get()
            ->map(fn (CmsPage $p) => [
                'loc' => url('/'.$p->full_slug),
                'lastmod' => $p->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => $p->parent_id === null ? '0.8' : '0.6',
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function sectionUrls(): array
    {
        return CmsSection::published()
            ->get()
            ->map(fn (CmsSection $s) => [
                'loc' => url('/'.$s->slug),
                'lastmod' => $s->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function categoryUrls(): array
    {
        return CmsCategory::published()
            ->with('section')
            ->get()
            ->filter(fn (CmsCategory $c) => $c->section->status === 'published')
            ->map(fn (CmsCategory $c) => [
                'loc' => url('/'.$c->section->slug.'/'.$c->slug),
                'lastmod' => $c->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function articleUrls(): array
    {
        return CmsArticle::published()
            ->with(['section', 'category'])
            ->get()
            ->map(function (CmsArticle $a) {
                $base = '/'.$a->section->slug;
                if ($a->category_id !== null && $a->category) {
                    $base .= '/'.$a->category->slug;
                }

                return [
                    'loc' => url($base.'/'.$a->slug),
                    'lastmod' => $a->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => $a->is_featured ? '0.9' : '0.7',
                ];
            })
            ->toArray();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>
     */
    private function albumUrls(): array
    {
        return CmsAlbum::active()->normal()
            ->get()
            ->map(fn (CmsAlbum $a) => [
                'loc' => url('/gallery/'.$a->slug),
                'lastmod' => $a->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ])
            ->toArray();
    }

    /**
     * @param  array<int, array{loc: string, lastmod: string, changefreq: string, priority: string}>  $urls
     */
    private function buildXml(array $urls): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

        foreach ($urls as $url) {
            $xml .= '  <url>'.PHP_EOL;
            $xml .= '    <loc>'.htmlspecialchars($url['loc']).'</loc>'.PHP_EOL;
            $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'.PHP_EOL;
            $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'.PHP_EOL;
            $xml .= '    <priority>'.$url['priority'].'</priority>'.PHP_EOL;
            $xml .= '  </url>'.PHP_EOL;
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
