<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsPage;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Support\CmsLocale;
use VmEngine\Cms\Support\CmsUrl;

class CmsSitemapService
{
    public function __construct(private readonly CmsAccessService $access) {}

    public function generate(): string
    {
        $urls = array_merge(
            $this->staticUrls(),
            $this->pageUrls(),
            $this->sectionUrls(),
            $this->categoryUrls(),
            $this->articleUrls(),
            $this->albumUrls(),
        );

        return $this->buildXml($urls);
    }

    /**
     * Non-CMS routes (e.g. the host's homepage) from `synapps.modules.cms.sitemap.static_paths`.
     *
     * @return array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>
     */
    private function staticUrls(): array
    {
        /** @var array<int, string> $paths */
        $paths = (array) config('synapps.modules.cms.sitemap.static_paths', ['/']);

        return array_map(fn (string $path): array => [
            'path' => $path,
            'lastmod' => now()->startOfDay()->toAtomString(),
            'changefreq' => 'weekly',
            'priority' => $path === '/' ? '1.0' : '0.7',
        ], array_values($paths));
    }

    /**
     * @return array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>
     */
    private function pageUrls(): array
    {
        return CmsPage::published()
            ->get()
            ->filter(fn (CmsPage $model) => $model->showInSitemap() && $this->access->isPublic($model))
            ->map(fn (CmsPage $p) => [
                'path' => CmsUrl::path($p),
                'lastmod' => $p->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => $p->parent_id === null ? '0.8' : '0.6',
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>
     */
    private function sectionUrls(): array
    {
        return CmsSection::published()
            ->get()
            ->filter(fn ($model) => $this->access->isPublic($model))
            ->map(fn (CmsSection $s) => [
                'path' => CmsUrl::path($s),
                'lastmod' => $s->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>
     */
    private function categoryUrls(): array
    {
        return CmsCategory::published()
            ->with('section')
            ->get()
            ->filter(fn (CmsCategory $c) => $c->section->status === 'published')
            ->filter(fn ($model) => $this->access->isPublic($model))
            ->map(fn (CmsCategory $c) => [
                'path' => CmsUrl::path($c),
                'lastmod' => $c->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6',
            ])
            ->toArray();
    }

    /**
     * @return array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>
     */
    private function articleUrls(): array
    {
        return CmsArticle::published()
            ->with(['section', 'category'])
            ->get()
            ->filter(fn ($model) => $this->access->isPublic($model))
            ->map(function (CmsArticle $a) {
                return [
                    'path' => CmsUrl::path($a),
                    'lastmod' => $a->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => $a->is_featured ? '0.9' : '0.7',
                ];
            })
            ->toArray();
    }

    /**
     * @return array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>
     */
    private function albumUrls(): array
    {
        return CmsAlbum::active()->normal()
            ->get()
            ->filter(fn ($model) => $this->access->isPublic($model))
            ->map(fn (CmsAlbum $a) => [
                'path' => CmsUrl::path($a),
                'lastmod' => $a->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ])
            ->toArray();
    }

    /**
     * One <url> per entry per enabled locale; with several locales, each lists
     * all language versions (+ x-default = primary) as xhtml:link alternates.
     *
     * @param  array<int, array{path: string|null, lastmod: string, changefreq: string, priority: string}>  $urls
     */
    private function buildXml(array $urls): string
    {
        $locales = CmsLocale::all();
        $multiLanguage = count($locales) > 1;

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            .($multiLanguage ? ' xmlns:xhtml="http://www.w3.org/1999/xhtml"' : '').'>'.PHP_EOL;

        foreach ($urls as $url) {
            if ($url['path'] === null) {
                continue;
            }

            foreach ($locales as $locale) {
                $xml .= '  <url>'.PHP_EOL;
                $xml .= '    <loc>'.htmlspecialchars(CmsUrl::absolute($url['path'], $locale)).'</loc>'.PHP_EOL;

                if ($multiLanguage) {
                    foreach ($locales as $alternate) {
                        $xml .= '    <xhtml:link rel="alternate" hreflang="'.str_replace('_', '-', $alternate).'" href="'
                            .htmlspecialchars(CmsUrl::absolute($url['path'], $alternate)).'"/>'.PHP_EOL;
                    }
                    $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="'
                        .htmlspecialchars(CmsUrl::absolute($url['path'], CmsLocale::primary())).'"/>'.PHP_EOL;
                }

                $xml .= '    <lastmod>'.$url['lastmod'].'</lastmod>'.PHP_EOL;
                $xml .= '    <changefreq>'.$url['changefreq'].'</changefreq>'.PHP_EOL;
                $xml .= '    <priority>'.$url['priority'].'</priority>'.PHP_EOL;
                $xml .= '  </url>'.PHP_EOL;
            }
        }

        $xml .= '</urlset>';

        return $xml;
    }
}
