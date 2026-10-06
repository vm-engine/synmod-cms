<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use VmEngine\Cms\CmsServiceProvider;
use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Services\Analytics\FakeGoogleApiClient;
use VmEngine\Cms\Services\Analytics\Ga4Reporter;
use VmEngine\Cms\Services\Analytics\GoogleApiClient;
use VmEngine\Cms\Services\Analytics\SearchConsoleReporter;

uses(RefreshDatabase::class);

function enableAnalyticsDummyMode(string $environment = 'local'): void
{
    config(['synapps.modules.cms.analytics.fake' => true]);
    app()->detectEnvironment(fn () => $environment);
    (new CmsServiceProvider(app()))->register();
}

it('uses the real client by default', function () {
    expect(app(GoogleApiClient::class))->not->toBeInstanceOf(FakeGoogleApiClient::class);
});

it('binds the fake client when enabled outside production', function () {
    enableAnalyticsDummyMode();

    expect(app(GoogleApiClient::class))->toBeInstanceOf(FakeGoogleApiClient::class);
});

it('never fakes in production or under tests', function (string $environment) {
    enableAnalyticsDummyMode($environment);

    expect(app(GoogleApiClient::class))->not->toBeInstanceOf(FakeGoogleApiClient::class);
})->with(['production', 'testing']);

it('feeds the reporters without a key or any http call', function () {
    Http::preventStrayRequests();
    enableAnalyticsDummyMode();
    configureAnalytics(withKey: false);

    $overview = app(Ga4Reporter::class)->overview(AnalyticsRange::Last28);
    $performance = app(SearchConsoleReporter::class)->performance(AnalyticsRange::Last28);

    expect($overview['daily'])->toHaveCount(28)
        ->and($overview['totals']['users'])->toBeGreaterThan(0)
        ->and($overview['totals']['engagement_rate'])->toBeBetween(0.0, 1.0)
        ->and(app(Ga4Reporter::class)->topPages(AnalyticsRange::Last7))->toHaveCount(10)
        ->and($performance['daily'])->toHaveCount(28)
        ->and($performance['totals']['clicks'])->toBeGreaterThan(0)
        ->and(app(SearchConsoleReporter::class)->topQueries(AnalyticsRange::Last7))->toHaveCount(10)
        ->and(app(SearchConsoleReporter::class)->topPages(AnalyticsRange::Last7, 5))->toHaveCount(5);
});

it('returns the same numbers for the same dates', function () {
    enableAnalyticsDummyMode();
    $client = app(GoogleApiClient::class);
    $body = ['dateRanges' => [['startDate' => '2026-09-01', 'endDate' => '2026-09-07']], 'dimensions' => [['name' => 'date']], 'metrics' => []];

    expect($client->post('https://analyticsdata.googleapis.com/v1beta/properties/1:runReport', $body))
        ->toBe($client->post('https://analyticsdata.googleapis.com/v1beta/properties/1:runReport', $body));
});

it('shows a dummy-data notice on the dashboard only in dummy mode', function () {
    $user = makeAnalyticsUser('analytics', false);

    Livewire::actingAs($user)->test('cms::backend.analytics')
        ->assertDontSee(__('cms::labels.analytics_dummy_notice'));

    enableAnalyticsDummyMode();

    Livewire::actingAs($user)->test('cms::backend.analytics')
        ->assertSee(__('cms::labels.analytics_dummy_notice'));
});

it('renders full panels from dummy data', function () {
    enableAnalyticsDummyMode();
    configureAnalytics(withKey: false);

    Livewire::actingAs(makeAnalyticsUser('analytics', false))
        ->test('cms::backend.analytics-panel', ['source' => 'gsc', 'range' => '28'])
        ->assertSee(__('cms::labels.analytics_top_queries'))
        ->assertSee('<polyline', false)
        ->assertDontSee(__('cms::labels.analytics_not_configured'));
});
