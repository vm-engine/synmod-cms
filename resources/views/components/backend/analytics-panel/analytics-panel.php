<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Exceptions\AnalyticsException;
use VmEngine\Cms\Services\Analytics\Ga4Reporter;
use VmEngine\Cms\Services\Analytics\SearchConsoleReporter;
use VmEngine\Cms\Support\TrendLine;

/**
 * One report source (GA or GSC) for the analytics dashboard. Fails on its
 * own: an API error renders inside this panel only.
 */
new class extends Component
{
    public string $source = 'ga';

    public string $range = '28';

    public function mount(): void
    {
        $user = Auth::user();

        abort_unless($user instanceof Authorizable && $user->can('cms.analytics.read'), 403);
    }

    public function title(): string
    {
        return $this->isSearchConsole() ? __('cms::labels.analytics_gsc_title') : __('cms::labels.analytics_ga_title');
    }

    public function isSearchConsole(): bool
    {
        return $this->source === 'gsc';
    }

    /**
     * @return array{state: 'ok'|'not_configured'|'error', message?: string, tiles?: list<array{label: string, value: string, icon: string}>, trend_label?: string, trend?: string, tables?: list<array{title: string, columns: list<string>, rows: list<list<string>>}>}
     */
    #[Computed]
    public function report(): array
    {
        $range = AnalyticsRange::tryFrom($this->range) ?? AnalyticsRange::Last28;

        try {
            return ['state' => 'ok'] + ($this->isSearchConsole() ? $this->searchConsole($range) : $this->analytics($range));
        } catch (AnalyticsException $exception) {
            return ['state' => $exception->notConfigured ? 'not_configured' : 'error', 'message' => $exception->getMessage()];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function analytics(AnalyticsRange $range): array
    {
        $reporter = app(Ga4Reporter::class);
        $overview = $reporter->overview($range);
        $totals = $overview['totals'];

        return [
            'tiles' => [
                ['label' => __('cms::labels.analytics_users'), 'value' => number_format($totals['users']), 'icon' => 'ph ph-users'],
                ['label' => __('cms::labels.analytics_sessions'), 'value' => number_format($totals['sessions']), 'icon' => 'ph ph-browsers'],
                ['label' => __('cms::labels.analytics_views'), 'value' => number_format($totals['views']), 'icon' => 'ph ph-eye'],
                ['label' => __('cms::labels.analytics_engagement_rate'), 'value' => self::percent($totals['engagement_rate']), 'icon' => 'ph ph-hand-pointing'],
            ],
            'trend_label' => __('cms::labels.analytics_trend', ['metric' => __('cms::labels.analytics_views')]),
            'trend' => TrendLine::points(array_column($overview['daily'], 'views')),
            'tables' => [[
                'title' => __('cms::labels.analytics_top_pages'),
                'columns' => [__('cms::labels.analytics_page'), __('cms::labels.analytics_views')],
                'rows' => array_map(fn (array $row): array => [$row['path'], number_format($row['views'])], $reporter->topPages($range)),
            ]],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchConsole(AnalyticsRange $range): array
    {
        $reporter = app(SearchConsoleReporter::class);
        $performance = $reporter->performance($range);
        $totals = $performance['totals'];
        $columns = fn (string $first): array => [$first, __('cms::labels.analytics_clicks'), __('cms::labels.analytics_impressions')];
        $rows = fn (array $items): array => array_map(
            fn (array $row): array => [$row['key'], number_format($row['clicks']), number_format($row['impressions'])],
            $items,
        );

        return [
            'tiles' => [
                ['label' => __('cms::labels.analytics_clicks'), 'value' => number_format($totals['clicks']), 'icon' => 'ph ph-cursor-click'],
                ['label' => __('cms::labels.analytics_impressions'), 'value' => number_format($totals['impressions']), 'icon' => 'ph ph-eye'],
                ['label' => __('cms::labels.analytics_ctr'), 'value' => self::percent($totals['ctr']), 'icon' => 'ph ph-percent'],
                ['label' => __('cms::labels.analytics_position'), 'value' => number_format($totals['position'], 1), 'icon' => 'ph ph-ranking'],
            ],
            'trend_label' => __('cms::labels.analytics_trend', ['metric' => __('cms::labels.analytics_clicks')]),
            'trend' => TrendLine::points(array_column($performance['daily'], 'clicks')),
            'tables' => [
                ['title' => __('cms::labels.analytics_top_queries'), 'columns' => $columns(__('cms::labels.analytics_query')), 'rows' => $rows($reporter->topQueries($range))],
                ['title' => __('cms::labels.analytics_top_pages'), 'columns' => $columns(__('cms::labels.analytics_page')), 'rows' => $rows($reporter->topPages($range))],
            ],
        ];
    }

    private static function percent(float $ratio): string
    {
        return number_format($ratio * 100, 1).'%';
    }
};
