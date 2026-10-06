<?php

declare(strict_types=1);

namespace VmEngine\Cms\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use VmEngine\Cms\Services\CmsFeedService;
use VmEngine\Cms\Support\CmsLocale;

class FeedController
{
    public function __construct(private readonly CmsFeedService $feeds) {}

    public function articles(Request $request): Response
    {
        $this->useLocale($request);

        return $this->rss($this->feeds->articles());
    }

    public function section(Request $request): Response
    {
        $this->useLocale($request);

        $section = $this->feeds->publicSection((string) $request->route('section'));

        abort_if($section === null, 404);

        return $this->rss($this->feeds->articles($section));
    }

    public function downloads(Request $request): Response
    {
        $this->useLocale($request);

        return $this->rss($this->feeds->downloads());
    }

    public function gallery(Request $request): Response
    {
        $this->useLocale($request);

        return $this->rss($this->feeds->gallery());
    }

    /**
     * Prefixed routes carry {locale} (SetFallbackLocale already applied it);
     * unprefixed feeds are the CMS primary locale.
     */
    private function useLocale(Request $request): void
    {
        app()->setLocale((string) ($request->route('locale') ?? CmsLocale::primary()));
    }

    private function rss(string $xml): Response
    {
        return response($xml, 200, [
            'Content-Type' => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
