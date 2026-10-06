<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services\Analytics;

use Carbon\CarbonImmutable;
use Carbon\CarbonPeriod;

/**
 * Dummy-data stand-in for GoogleApiClient (dev only, never production — see
 * CmsServiceProvider). Answers GA4 runReport and Search Console queries with
 * Google-shaped responses, deterministic per date so refreshes don't jump.
 */
class FakeGoogleApiClient extends GoogleApiClient
{
    private const PAGES = ['/', '/about', '/news', '/contact', '/services', '/news/launching-our-new-site', '/gallery', '/downloads', '/faq', '/careers'];

    private const QUERIES = ['company name', 'company name contact', 'services jakarta', 'company news', 'about company name', 'company careers', 'download brochure', 'company gallery', 'faq company name', 'company address'];

    private const ENGAGEMENT_RATE = '0.62';

    public function token(): string
    {
        return 'fake-token';
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $url, array $body): array
    {
        return str_contains($url, 'analyticsdata.googleapis.com') ? $this->ga4($body) : $this->searchConsole($body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function ga4(array $body): array
    {
        $views = array_map(fn (CarbonImmutable $day): int => $this->dailyViews($day), $this->days($body['dateRanges'][0]['startDate'], $body['dateRanges'][0]['endDate']));

        if (($body['dimensions'][0]['name'] ?? null) === 'pagePath') {
            return ['rows' => $this->ranked(self::PAGES, array_sum($views), (int) ($body['limit'] ?? 10), fn (string $path, int $count): array => [
                'dimensionValues' => [['value' => $path]],
                'metricValues' => [['value' => (string) $count]],
            ])];
        }

        $rows = [];
        foreach ($views as $date => $count) {
            $rows[] = ['dimensionValues' => [['value' => $date]], 'metricValues' => self::gaMetrics($count, 3)];
        }

        return ['rows' => $rows, 'totals' => [['metricValues' => self::gaMetrics(array_sum($views), 4)]]];
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    private function searchConsole(array $body): array
    {
        $clicks = array_map(fn (CarbonImmutable $day): int => intdiv($this->dailyViews($day), 8), $this->days($body['startDate'], $body['endDate']));
        $total = array_sum($clicks);
        $limit = (int) ($body['rowLimit'] ?? 10);
        $row = fn (string $key, int $count): array => ['keys' => [$key], 'clicks' => $count, 'impressions' => $count * 18, 'ctr' => 0.055, 'position' => 7.4];

        return ['rows' => match ($body['dimensions'][0] ?? null) {
            'date' => array_map(fn (string $ymd, int $count): array => $row(CarbonImmutable::createFromFormat('Ymd', $ymd)->toDateString(), $count), array_keys($clicks), array_values($clicks)),
            'query' => $this->ranked(self::QUERIES, $total, $limit, $row),
            'page' => $this->ranked(array_map(fn (string $path): string => url($path), self::PAGES), $total, $limit, $row),
            default => [$row('', $total)],
        }];
    }

    /**
     * Users ≈ views / $usersDivisor (totals dedupe users across days), sessions ≈ views / 2.
     *
     * @return list<array{value: string}>
     */
    private static function gaMetrics(int $views, int $usersDivisor): array
    {
        return [
            ['value' => (string) intdiv($views, $usersDivisor)],
            ['value' => (string) intdiv($views, 2)],
            ['value' => (string) $views],
            ['value' => self::ENGAGEMENT_RATE],
        ];
    }

    /**
     * First $limit keys with decreasing shares of $total.
     *
     * @param  list<string>  $keys
     * @param  callable(string, int): array<string, mixed>  $row
     * @return list<array<string, mixed>>
     */
    private function ranked(array $keys, int $total, int $limit, callable $row): array
    {
        $result = [];

        foreach (array_slice($keys, 0, $limit) as $rank => $key) {
            $result[] = $row($key, intdiv($total, $rank + 3));
        }

        return $result;
    }

    /**
     * Weekday/weekend rhythm plus stable per-date noise.
     */
    private function dailyViews(CarbonImmutable $day): int
    {
        return ($day->isWeekend() ? 140 : 260) + crc32($day->toDateString()) % 90;
    }

    /**
     * @return array<string, CarbonImmutable> keyed by Ymd
     */
    private function days(string $start, string $end): array
    {
        $days = [];

        foreach (CarbonPeriod::create($start, $end) as $day) {
            $days[$day->format('Ymd')] = CarbonImmutable::instance($day);
        }

        return $days;
    }
}
