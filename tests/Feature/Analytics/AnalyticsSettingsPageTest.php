<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Cms\Support\AnalyticsSettings;

uses(RefreshDatabase::class);

it('loads stored analytics settings on mount', function () {
    configureAnalytics(['require_consent' => false]);

    Livewire::actingAs(makeAnalyticsUser('settings', false))
        ->test('cms::backend.settings')
        ->assertSet('measurementId', 'G-TEST123')
        ->assertSet('requireConsent', false)
        ->assertSet('serviceAccountJson', '')
        ->assertSee('reporter@demo.iam.gserviceaccount.com');
});

it('saves analytics settings and the encrypted key, then flushes the report cache', function () {
    $versionBefore = AnalyticsCache::version();

    Livewire::actingAs(makeAnalyticsUser('settings', true))
        ->test('cms::backend.settings')
        ->set('measurementId', 'G-NEW1')
        ->set('gscVerification', 'abc_DEF-1')
        ->set('requireConsent', false)
        ->set('propertyId', '987')
        ->set('gscSite', 'https://example.com/')
        ->set('serviceAccountJson', analyticsServiceAccountFixture()['json'])
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('serviceAccountJson', '');

    expect(AnalyticsSettings::all())->toBe([
        'measurement_id' => 'G-NEW1',
        'gsc_verification' => 'abc_DEF-1',
        'require_consent' => false,
        'property_id' => '987',
        'gsc_site' => 'https://example.com/',
    ])
        ->and(AnalyticsSettings::serviceAccount())->not->toBeNull()
        ->and(AnalyticsCache::version())->toBe($versionBefore + 1);
});

it('keeps the existing key when the key field is left empty', function () {
    configureAnalytics();

    Livewire::actingAs(makeAnalyticsUser('settings', true))
        ->test('cms::backend.settings')
        ->call('save')
        ->assertHasNoErrors();

    expect(AnalyticsSettings::serviceAccount())->not->toBeNull();
});

it('validates analytics fields', function (string $field, string $value) {
    Livewire::actingAs(makeAnalyticsUser('settings', true))
        ->test('cms::backend.settings')
        ->set($field, $value)
        ->call('save')
        ->assertHasErrors([$field]);
})->with([
    'measurement id' => ['measurementId', 'UA-12345-1'],
    'verification with markup' => ['gscVerification', '<meta name="x">'],
    'property id' => ['propertyId', 'G-123'],
    'site url' => ['gscSite', 'example.com'],
    'not a service account' => ['serviceAccountJson', '{"type":"authorized_user"}'],
    'not json' => ['serviceAccountJson', 'hello'],
]);

it('removes the service account', function () {
    configureAnalytics();

    Livewire::actingAs(makeAnalyticsUser('settings', true))
        ->test('cms::backend.settings')
        ->call('removeServiceAccount')
        ->assertDispatched('notify');

    expect(AnalyticsSettings::serviceAccount())->toBeNull();
});

it('does not remove the key without update permission', function () {
    configureAnalytics();

    Livewire::actingAs(makeAnalyticsUser('settings', false))
        ->test('cms::backend.settings')
        ->call('removeServiceAccount');

    expect(AnalyticsSettings::serviceAccount())->not->toBeNull();
});
