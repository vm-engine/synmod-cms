<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

/**
 * Turns a series into SVG <polyline> points scaled to a width×height box
 * (y inverted, max value at the top). No chart library needed.
 */
final class TrendLine
{
    /**
     * @param  array<int, int|float>  $values
     */
    public static function points(array $values, int $width = 600, int $height = 120): string
    {
        $values = array_values($values);
        $count = count($values);

        if ($count < 2) {
            return '';
        }

        $max = max($values) ?: 1;
        $step = $width / ($count - 1);
        $points = [];

        foreach ($values as $index => $value) {
            $points[] = round($index * $step, 1).','.round($height - ($value / $max) * $height, 1);
        }

        return implode(' ', $points);
    }
}
