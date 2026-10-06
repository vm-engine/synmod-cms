<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Exceptions\AnalyticsException;
use VmEngine\Cms\Services\Analytics\Ga4Reporter;
use VmEngine\Cms\Services\Analytics\SearchConsoleReporter;

uses(RefreshDatabase::class);

beforeEach(function () {
    Carbon::setTestNow('2026-10-06 10:00');
    configureAnalytics();
});

describe('Ga4Reporter', function () {
    it('maps the overview report', function () {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
            'analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [
                    ['dimensionValues' => [['value' => '20261004']], 'metricValues' => [['value' => '5'], ['value' => '6'], ['value' => '10'], ['value' => '0.5']]],
                    ['dimensionValues' => [['value' => '20261005']], 'metricValues' => [['value' => '7'], ['value' => '8'], ['value' => '12'], ['value' => '0.6']]],
                ],
                'totals' => [['metricValues' => [['value' => '11'], ['value' => '14'], ['value' => '22'], ['value' => '0.55']]]],
            ]),
        ]);

        $overview = app(Ga4Reporter::class)->overview(AnalyticsRange::Last7);

        expect($overview)->toBe([
            'totals' => ['users' => 11, 'sessions' => 14, 'views' => 22, 'engagement_rate' => 0.55],
            'daily' => [['date' => '20261004', 'views' => 10], ['date' => '20261005', 'views' => 12]],
        ]);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://analyticsdata.googleapis.com/v1beta/properties/123456:runReport'
            && $request['dateRanges'] === [['startDate' => '2026-09-29', 'endDate' => '2026-10-05']]
            && $request['metricAggregations'] === ['TOTAL']);
    });

    it('maps top pages', function () {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
            'analyticsdata.googleapis.com/*' => Http::response([
                'rows' => [['dimensionValues' => [['value' => '/about']], 'metricValues' => [['value' => '42']]]],
            ]),
        ]);

        expect(app(Ga4Reporter::class)->topPages(AnalyticsRange::Last28))->toBe([['path' => '/about', 'views' => 42]]);
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'runReport') && $request['limit'] === 10);
    });

    it('caches results until the cache is flushed', function () {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
            'analyticsdata.googleapis.com/*' => Http::response(['rows' => []]),
        ]);
        $reporter = app(Ga4Reporter::class);

        $reporter->topPages(AnalyticsRange::Last28);
        $reporter->topPages(AnalyticsRange::Last28);

        Http::assertSentCount(2); // 1 token + 1 report
    });

    it('requires a property id', function () {
        configureAnalytics(['property_id' => '']);

        expect(fn () => app(Ga4Reporter::class)->topPages(AnalyticsRange::Last7))
            ->toThrow(fn (AnalyticsException $e) => expect($e->notConfigured)->toBeTrue());
    });
});

describe('SearchConsoleReporter', function () {
    it('maps performance with a 3-day lag', function () {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
            'www.googleapis.com/webmasters/*' => function (Request $request) {
                return isset($request['dimensions'])
                    ? Http::response(['rows' => [['keys' => ['2026-10-03'], 'clicks' => 4, 'impressions' => 90, 'ctr' => 0.04, 'position' => 8.1]]])
                    : Http::response(['rows' => [['clicks' => 30, 'impressions' => 1000, 'ctr' => 0.03, 'position' => 9.5]]]);
            },
        ]);

        expect(app(SearchConsoleReporter::class)->performance(AnalyticsRange::Last7))->toBe([
            'totals' => ['clicks' => 30, 'impressions' => 1000, 'ctr' => 0.03, 'position' => 9.5],
            'daily' => [['date' => '2026-10-03', 'clicks' => 4]],
        ]);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.googleapis.com/webmasters/v3/sites/sc-domain%3Aexample.com/searchAnalytics/query'
            && $request['startDate'] === '2026-09-27'
            && $request['endDate'] === '2026-10-03');
    });

    it('maps top queries and top pages', function () {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
            'www.googleapis.com/webmasters/*' => Http::response(['rows' => [['keys' => ['laravel cms'], 'clicks' => 3, 'impressions' => 50, 'ctr' => 0.06, 'position' => 4.2]]]),
        ]);
        $reporter = app(SearchConsoleReporter::class);

        expect($reporter->topQueries(AnalyticsRange::Last28))->toBe([['key' => 'laravel cms', 'clicks' => 3, 'impressions' => 50]])
            ->and($reporter->topPages(AnalyticsRange::Last28))->toBe([['key' => 'laravel cms', 'clicks' => 3, 'impressions' => 50]]);
        Http::assertSent(fn (Request $request) => ($request['dimensions'] ?? null) === ['query'] && $request['rowLimit'] === 10);
        Http::assertSent(fn (Request $request) => ($request['dimensions'] ?? null) === ['page']);
    });

    it('requires a site url', function () {
        configureAnalytics(['gsc_site' => '']);

        expect(fn () => app(SearchConsoleReporter::class)->topQueries(AnalyticsRange::Last7))
            ->toThrow(fn (AnalyticsException $e) => expect($e->notConfigured)->toBeTrue());
    });
});
