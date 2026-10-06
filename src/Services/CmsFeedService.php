<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Mime\MimeTypes;
use VmEngine\Cms\Models\CmsAlbum;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Support\CmsUrl;
use XMLWriter;

/**
 * RSS 2.0 feeds of publicly visible CMS content in the current app locale.
 * Feeds are always the guest view: readers are anonymous and responses cacheable.
 */
class CmsFeedService
{
    private const LIMIT = 20;

    public function __construct(private readonly CmsAccessService $access) {}

    public function articles(?CmsSection $section = null): string
    {
        $query = CmsArticle::published()
            ->with(['section', 'category'])
            ->whereHas('section', fn (Builder $q) => $q->where('status', 'published'))
            ->when($section, fn (Builder $q) => $q->where('section_id', $section?->id))
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->limit(self::LIMIT);

        $this->access->applyVisibleScope($query, null);

        $items = $query->get()->map(fn (CmsArticle $a) => $this->item(
            $a,
            $a->published_at ?? $a->created_at,
            array_values(array_filter([$a->section->translate('title'), $a->category?->translate('title')])),
        ));

        return $this->build(
            feed: 'articles',
            link: CmsUrl::absolute($section !== null ? (string) CmsUrl::path($section) : ''),
            items: $items,
            section: $section,
        );
    }

    public function downloads(): string
    {
        $query = CmsDownload::active()->latest()->limit(self::LIMIT);
        $this->access->applyVisibleScope($query, null);

        return $this->build('downloads', CmsUrl::absolute(''), $query->get()->map(
            fn (CmsDownload $d) => $this->item($d, $d->created_at),
        ));
    }

    public function gallery(): string
    {
        $query = CmsAlbum::active()->normal()->latest()->limit(self::LIMIT);
        $this->access->applyVisibleScope($query, null);

        return $this->build('gallery', CmsUrl::absolute(''), $query->get()->map(
            fn (CmsAlbum $a) => $this->item($a, $a->created_at),
        ));
    }

    /**
     * The published, publicly visible section with $slug — the only ones with a feed.
     */
    public function publicSection(string $slug): ?CmsSection
    {
        $section = CmsSection::published()->where('slug', $slug)->first();

        return $section !== null && $this->hasFeed($section) ? $section : null;
    }

    /**
     * Only published, publicly visible sections get a feed.
     */
    public function hasFeed(CmsSection $section): bool
    {
        return $section->status === 'published' && $this->access->isPublic($section);
    }

    /**
     * Channel / autodiscovery title, e.g. "Site — Articles" or "Site — News".
     */
    public function title(string $feed, ?CmsSection $section = null): string
    {
        $label = $section !== null
            ? (string) $section->translate('title')
            : (string) __('cms::labels.feed_'.$feed);

        return config('app.name').' — '.$label;
    }

    /**
     * Feed path without locale prefix (pass to CmsUrl::absolute()).
     */
    public function path(string $feed, ?CmsSection $section = null): string
    {
        return $section !== null ? "feed/articles/{$section->slug}.xml" : "feed/{$feed}.xml";
    }

    /**
     * @param  list<string>  $categories
     * @return array{title: string, link: string, description: string, date: CarbonInterface, categories: list<string>, image: string|null}
     */
    private function item(CmsArticle|CmsDownload|CmsAlbum $model, CarbonInterface $date, array $categories = []): array
    {
        return [
            'title' => (string) $model->translate('title'),
            'link' => CmsUrl::absolute((string) CmsUrl::path($model)),
            'description' => (string) $model->translatedMetaDescription(),
            'date' => $date,
            'categories' => $categories,
            'image' => $model->cover_image,
        ];
    }

    /**
     * @param  Collection<int, array{title: string, link: string, description: string, date: CarbonInterface, categories: list<string>, image: string|null}>  $items
     */
    private function build(string $feed, string $link, Collection $items, ?CmsSection $section = null): string
    {
        $title = $this->title($feed, $section);

        $xml = new XMLWriter;
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('rss');
        $xml->writeAttribute('version', '2.0');
        $xml->writeAttribute('xmlns:atom', 'http://www.w3.org/2005/Atom');
        $xml->startElement('channel');
        $xml->writeElement('title', $title);
        $xml->writeElement('link', $link);
        $xml->writeElement('description', $title);
        $xml->writeElement('language', str_replace('_', '-', app()->getLocale()));

        if ($items->isNotEmpty()) {
            $xml->writeElement('lastBuildDate', $items->first()['date']->toRssString());
        }

        $xml->startElement('atom:link');
        $xml->writeAttribute('href', CmsUrl::absolute($this->path($feed, $section)));
        $xml->writeAttribute('rel', 'self');
        $xml->writeAttribute('type', 'application/rss+xml');
        $xml->endElement();

        foreach ($items as $item) {
            $xml->startElement('item');
            $xml->writeElement('title', $item['title']);
            $xml->writeElement('link', $item['link']);
            $xml->startElement('guid');
            $xml->writeAttribute('isPermaLink', 'true');
            $xml->text($item['link']);
            $xml->endElement();

            if ($item['description'] !== '') {
                $xml->writeElement('description', $item['description']);
            }

            $xml->writeElement('pubDate', $item['date']->toRssString());

            foreach ($item['categories'] as $category) {
                $xml->writeElement('category', $category);
            }

            $this->writeEnclosure($xml, $item['image']);
            $xml->endElement();
        }

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    /**
     * Cover image as <enclosure>; length is unknown for FM paths, and 0 is the accepted convention.
     */
    private function writeEnclosure(XMLWriter $xml, ?string $image): void
    {
        if ($image === null || $image === '') {
            return;
        }

        $extension = strtolower(pathinfo((string) parse_url($image, PHP_URL_PATH), PATHINFO_EXTENSION));
        $type = MimeTypes::getDefault()->getMimeTypes($extension)[0] ?? null;

        if ($type === null) {
            return;
        }

        $xml->startElement('enclosure');
        $xml->writeAttribute('url', Str::startsWith($image, ['http://', 'https://']) ? $image : url($image));
        $xml->writeAttribute('length', '0');
        $xml->writeAttribute('type', $type);
        $xml->endElement();
    }
}
