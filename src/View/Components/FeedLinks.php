<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\Routing\Route;
use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Models\CmsArticle;
use VmEngine\Cms\Models\CmsCategory;
use VmEngine\Cms\Models\CmsSection;
use VmEngine\Cms\Services\CmsFeedService;
use VmEngine\Cms\Support\CmsUrl;

/**
 * <link rel="alternate" type="application/rss+xml"> tags for the layout <head>:
 * the site feeds, plus the current section's feed on its section, category
 * and article pages (resolved from the fallback route parameters).
 */
class FeedLinks extends Component
{
    /** @var list<array{title: string, href: string}> */
    public array $feeds = [];

    public function __construct(CmsFeedService $feedService)
    {
        $section = $this->currentSection();

        if ($section !== null && $feedService->hasFeed($section)) {
            $this->feeds[] = $this->link($feedService, 'articles', $section);
        }

        foreach (['articles', 'downloads', 'gallery'] as $feed) {
            $this->feeds[] = $this->link($feedService, $feed);
        }
    }

    public function render(): View
    {
        return view('cms::components.feed-links');
    }

    /**
     * @return array{title: string, href: string}
     */
    private function link(CmsFeedService $feedService, string $feed, ?CmsSection $section = null): array
    {
        return [
            'title' => $feedService->title($feed, $section),
            'href' => CmsUrl::absolute($feedService->path($feed, $section)),
        ];
    }

    private function currentSection(): ?CmsSection
    {
        $route = request()->route();

        if (! $route instanceof Route) {
            return null;
        }

        $sectionId = $route->parameter('section_id')
            ?? $this->parentSectionId(CmsArticle::class, $route->parameter('article_id'))
            ?? $this->parentSectionId(CmsCategory::class, $route->parameter('category_id'));

        return $sectionId === null ? null : CmsSection::whereKey($sectionId)->first();
    }

    /**
     * @param  class-string<CmsArticle|CmsCategory>  $model
     */
    private function parentSectionId(string $model, mixed $id): mixed
    {
        return $id === null ? null : $model::whereKey($id)->value('section_id');
    }
}
