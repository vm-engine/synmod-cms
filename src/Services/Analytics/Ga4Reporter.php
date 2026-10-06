<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services\Analytics;

use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Exceptions\AnalyticsException;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Cms\Support\AnalyticsSettings;

/**
 * GA4 Data API reports. Days with no traffic are omitted by GA, so `daily`
 * can have gaps — fine for a trend line.
 */
class Ga4Reporter
{
    public function __construct(private GoogleApiClient $client) {}

    /**
     * @return array{totals: array{users: int, sessions: int, views: int, engagement_rate: float}, daily: list<array{date: string, views: int}>}
     */
    public function overview(AnalyticsRange $range): array
    {
        return AnalyticsCache::remember('ga.overview.'.$range->value, function () use ($range): array {
            $data = $this->runReport($range, [
                'dimensions' => [['name' => 'date']],
                'metrics' => [['name' => 'activeUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews'], ['name' => 'engagementRate']],
                'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
                'metricAggregations' => ['TOTAL'],
            ]);
            $totals = $data['totals'][0]['metricValues'] ?? [];

            return [
                'totals' => [
                    'users' => (int) ($totals[0]['value'] ?? 0),
                    'sessions' => (int) ($totals[1]['value'] ?? 0),
                    'views' => (int) ($totals[2]['value'] ?? 0),
                    'engagement_rate' => (float) ($totals[3]['value'] ?? 0),
                ],
                'daily' => array_map(fn (array $row): array => [
                    'date' => (string) $row['dimensionValues'][0]['value'],
                    'views' => (int) $row['metricValues'][2]['value'],
                ], array_values($data['rows'] ?? [])),
            ];
        });
    }

    /**
     * @return list<array{path: string, views: int}>
     */
    public function topPages(AnalyticsRange $range, int $limit = 10): array
    {
        return AnalyticsCache::remember("ga.pages.{$range->value}.{$limit}", function () use ($range, $limit): array {
            $data = $this->runReport($range, [
                'dimensions' => [['name' => 'pagePath']],
                'metrics' => [['name' => 'screenPageViews']],
                'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
                'limit' => $limit,
            ]);

            return array_map(fn (array $row): array => [
                'path' => (string) $row['dimensionValues'][0]['value'],
                'views' => (int) $row['metricValues'][0]['value'],
            ], array_values($data['rows'] ?? []));
        });
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function runReport(AnalyticsRange $range, array $body): array
    {
        $propertyId = AnalyticsSettings::all()['property_id'];

        if ($propertyId === '') {
            throw AnalyticsException::notConfigured();
        }

        return $this->client->post(
            "https://analyticsdata.googleapis.com/v1beta/properties/{$propertyId}:runReport",
            ['dateRanges' => [['startDate' => $range->start()->toDateString(), 'endDate' => $range->end()->toDateString()]]] + $body,
        );
    }
}
