<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services\Analytics;

use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Exceptions\AnalyticsException;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Cms\Support\AnalyticsSettings;

/**
 * Search Console searchAnalytics reports. GSC data lags ~3 days, so ranges
 * end 3 days ago (AnalyticsRange already ends yesterday; +2 lag).
 */
class SearchConsoleReporter
{
    private const LAG_DAYS = 2;

    public function __construct(private GoogleApiClient $client) {}

    /**
     * @return array{totals: array{clicks: int, impressions: int, ctr: float, position: float}, daily: list<array{date: string, clicks: int}>}
     */
    public function performance(AnalyticsRange $range): array
    {
        return AnalyticsCache::remember('gsc.performance.'.$range->value, function () use ($range): array {
            $totals = $this->query($range, [])[0] ?? [];

            return [
                'totals' => [
                    'clicks' => (int) ($totals['clicks'] ?? 0),
                    'impressions' => (int) ($totals['impressions'] ?? 0),
                    'ctr' => (float) ($totals['ctr'] ?? 0),
                    'position' => (float) ($totals['position'] ?? 0),
                ],
                'daily' => array_map(fn (array $row): array => [
                    'date' => (string) $row['keys'][0],
                    'clicks' => (int) $row['clicks'],
                ], $this->query($range, ['dimensions' => ['date']])),
            ];
        });
    }

    /**
     * @return list<array{key: string, clicks: int, impressions: int}>
     */
    public function topQueries(AnalyticsRange $range, int $limit = 10): array
    {
        return $this->top($range, 'query', $limit);
    }

    /**
     * @return list<array{key: string, clicks: int, impressions: int}>
     */
    public function topPages(AnalyticsRange $range, int $limit = 10): array
    {
        return $this->top($range, 'page', $limit);
    }

    /**
     * @return list<array{key: string, clicks: int, impressions: int}>
     */
    private function top(AnalyticsRange $range, string $dimension, int $limit): array
    {
        return AnalyticsCache::remember("gsc.{$dimension}.{$range->value}.{$limit}", fn (): array => array_map(
            fn (array $row): array => [
                'key' => (string) $row['keys'][0],
                'clicks' => (int) $row['clicks'],
                'impressions' => (int) $row['impressions'],
            ],
            $this->query($range, ['dimensions' => [$dimension], 'rowLimit' => $limit]),
        ));
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<array<string, mixed>>
     */
    private function query(AnalyticsRange $range, array $body): array
    {
        $site = AnalyticsSettings::all()['gsc_site'];

        if ($site === '') {
            throw AnalyticsException::notConfigured();
        }

        $data = $this->client->post(
            'https://www.googleapis.com/webmasters/v3/sites/'.rawurlencode($site).'/searchAnalytics/query',
            ['startDate' => $range->start(self::LAG_DAYS)->toDateString(), 'endDate' => $range->end(self::LAG_DAYS)->toDateString()] + $body,
        );

        return array_values($data['rows'] ?? []);
    }
}
