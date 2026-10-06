<?php

declare(strict_types=1);

namespace VmEngine\Cms\Enums;

use Carbon\CarbonImmutable;

/**
 * Reporting window for the analytics dashboard. Ranges end yesterday (today
 * is incomplete); `$lagDays` pushes both ends further back for sources whose
 * data arrives late (Search Console lags ~3 days).
 */
enum AnalyticsRange: string
{
    case Last7 = '7';
    case Last28 = '28';
    case Last90 = '90';

    public function end(int $lagDays = 0): CarbonImmutable
    {
        return CarbonImmutable::today()->subDays(1 + $lagDays);
    }

    public function start(int $lagDays = 0): CarbonImmutable
    {
        return $this->end($lagDays)->subDays((int) $this->value - 1);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $range): array => ['value' => $range->value, 'label' => __('cms::labels.analytics_range', ['days' => $range->value])],
            self::cases(),
        );
    }
}
