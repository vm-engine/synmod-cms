<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Cms\Support\TrendLine;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

function fakeGoogleReports(): void
{
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
        'analyticsdata.googleapis.com/*' => Http::response([
            'rows' => [['dimensionValues' => [['value' => '/about']], 'metricValues' => [['value' => '4321'], ['value' => '1'], ['value' => '4321'], ['value' => '0.5']]]],
            'totals' => [['metricValues' => [['value' => '1234'], ['value' => '2000'], ['value' => '4321'], ['value' => '0.625']]]],
        ]),
        'www.googleapis.com/webmasters/*' => Http::response([
            'rows' => [['keys' => ['laravel cms'], 'clicks' => 77, 'impressions' => 900, 'ctr' => 0.0856, 'position' => 6.25]],
        ]),
    ]);
}

describe('TrendLine', function () {
    it('scales values into polyline points', function () {
        expect(TrendLine::points([0, 5, 10], 100, 50))->toBe('0,50 50,25 100,0');
    });

    it('returns empty for fewer than two points and survives all zeros', function () {
        expect(TrendLine::points([3]))->toBe('')
            ->and(TrendLine::points([0, 0], 10, 10))->toBe('0,10 10,10');
    });
});

describe('dashboard page', function () {
    it('is reachable with read access', function () {
        $this->actingAs(makeAnalyticsUser('analytics', false))
            ->get(backend_route('cms.analytics'))
            ->assertOk()
            ->assertSee(__('cms::labels.analytics'));
    });

    it('redirects users without analytics access', function () {
        $this->actingAs(User::factory()->create())
            ->get(backend_route('cms.analytics'))
            ->assertRedirect();
    });

    it('refreshes the cache only with update access', function () {
        $version = AnalyticsCache::version();

        Livewire::actingAs(makeAnalyticsUser('analytics', false))->test('cms::backend.analytics')->call('refresh');
        expect(AnalyticsCache::version())->toBe($version);

        Livewire::actingAs(makeAnalyticsUser('analytics', true))->test('cms::backend.analytics')->call('refresh')
            ->assertSet('version', $version + 1);
        expect(AnalyticsCache::version())->toBe($version + 1);
    });

    it('falls back to 28 days for an unknown range', function () {
        Livewire::actingAs(makeAnalyticsUser('analytics', false))
            ->withQueryParams(['range' => '999'])
            ->test('cms::backend.analytics')
            ->assertSet('range', '28');
    });
});

describe('analytics panel', function () {
    it('renders GA tiles, trend and top pages', function () {
        configureAnalytics();
        fakeGoogleReports();

        Livewire::actingAs(makeAnalyticsUser('analytics', false))
            ->test('cms::backend.analytics-panel', ['source' => 'ga', 'range' => '28'])
            ->assertSee('1,234')
            ->assertSee('62.5%')
            ->assertSee('/about');
    });

    it('renders GSC tiles and top queries', function () {
        configureAnalytics();
        fakeGoogleReports();

        Livewire::actingAs(makeAnalyticsUser('analytics', false))
            ->test('cms::backend.analytics-panel', ['source' => 'gsc', 'range' => '28'])
            ->assertSee('8.6%')
            ->assertSee('6.3')
            ->assertSee('laravel cms')
            ->assertSee(__('cms::labels.analytics_gsc_lag'));
    });

    it('shows the not-configured state, linking to settings only for settings readers', function () {
        Livewire::actingAs(makeAnalyticsUser('analytics', false))
            ->test('cms::backend.analytics-panel', ['source' => 'ga', 'range' => '28'])
            ->assertSee(__('cms::labels.analytics_not_configured'))
            ->assertDontSee(__('cms::labels.analytics_configure_link'));

        Livewire::actingAs(makeAnalyticsUser(['analytics', 'settings'], false))
            ->test('cms::backend.analytics-panel', ['source' => 'ga', 'range' => '28'])
            ->assertSee(__('cms::labels.analytics_configure_link'));
    });

    it('forbids the panel without analytics access', function () {
        Livewire::actingAs(makeAnalyticsUser('settings', true))
            ->test('cms::backend.analytics-panel', ['source' => 'ga', 'range' => '28'])
            ->assertForbidden();
    });

    it('shows a google error in one panel without breaking the other', function () {
        configureAnalytics();
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 't', 'expires_in' => 3599]),
            'analyticsdata.googleapis.com/*' => Http::response(['error' => ['message' => 'User does not have sufficient permissions for this property.']], 403),
            'www.googleapis.com/webmasters/*' => Http::response(['rows' => [['keys' => ['laravel cms'], 'clicks' => 77, 'impressions' => 900, 'ctr' => 0.08, 'position' => 6]]]),
        ]);
        $user = makeAnalyticsUser('analytics', false);

        Livewire::actingAs($user)->test('cms::backend.analytics-panel', ['source' => 'ga', 'range' => '28'])
            ->assertSee('User does not have sufficient permissions for this property.');
        Livewire::actingAs($user)->test('cms::backend.analytics-panel', ['source' => 'gsc', 'range' => '28'])
            ->assertSee('laravel cms');
    });
});
