<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use VmEngine\Cms\Enums\AnalyticsRange;
use VmEngine\Cms\Support\AnalyticsCache;
use VmEngine\Cms\Support\AnalyticsSettings;
use VmEngine\Synapse\Models\Config;

uses(RefreshDatabase::class);

describe('AnalyticsSettings', function () {
    it('returns defaults when nothing is stored', function () {
        expect(AnalyticsSettings::all())->toBe([
            'measurement_id' => '',
            'gsc_verification' => '',
            'require_consent' => true,
            'property_id' => '',
            'gsc_site' => '',
        ]);
    });

    it('round-trips saved values and drops unknown keys', function () {
        AnalyticsSettings::save(['measurement_id' => 'G-ABC', 'require_consent' => false, 'evil' => 'x']);

        expect(AnalyticsSettings::all())
            ->toMatchArray(['measurement_id' => 'G-ABC', 'require_consent' => false])
            ->not->toHaveKey('evil');
    });

    it('stores the service account encrypted and decrypts it back', function () {
        AnalyticsSettings::saveServiceAccount(analyticsServiceAccountFixture()['json']);

        $raw = Config::where('group', 'cms')->where('key', 'analytics_key')->value('value');

        expect($raw)->not->toContain('PRIVATE KEY')
            ->and(AnalyticsSettings::serviceAccount()['client_email'])->toBe('reporter@demo.iam.gserviceaccount.com');
    });

    it('returns null for a missing or removed service account', function () {
        expect(AnalyticsSettings::serviceAccount())->toBeNull();

        AnalyticsSettings::saveServiceAccount(analyticsServiceAccountFixture()['json']);
        AnalyticsSettings::saveServiceAccount(null);

        expect(AnalyticsSettings::serviceAccount())->toBeNull();
    });

    it('recognises service account json', function (mixed $data, bool $valid) {
        expect(AnalyticsSettings::isServiceAccount($data))->toBe($valid);
    })->with([
        'valid' => [['type' => 'service_account', 'client_email' => 'a@b', 'private_key' => 'k'], true],
        'wrong type' => [['type' => 'authorized_user', 'client_email' => 'a@b', 'private_key' => 'k'], false],
        'missing key' => [['type' => 'service_account', 'client_email' => 'a@b'], false],
        'not array' => ['nope', false],
    ]);

    it('only tracks in production for visitors without backend access', function () {
        configureAnalytics();
        expect(AnalyticsSettings::trackingActive())->toBeFalse(); // testing env

        app()->detectEnvironment(fn () => 'production');
        expect(AnalyticsSettings::trackingActive())->toBeTrue();

        $this->actingAs(makeAnalyticsUser('settings', false));
        expect(AnalyticsSettings::trackingActive())->toBeFalse();
    });

    it('does not track without a measurement id', function () {
        app()->detectEnvironment(fn () => 'production');

        expect(AnalyticsSettings::trackingActive())->toBeFalse();
    });
});

describe('AnalyticsRange', function () {
    it('ends yesterday and spans the given days', function () {
        Carbon::setTestNow('2026-10-06 10:00');

        expect(AnalyticsRange::Last7->end()->toDateString())->toBe('2026-10-05')
            ->and(AnalyticsRange::Last7->start()->toDateString())->toBe('2026-09-29');
    });

    it('shifts both ends by the lag', function () {
        Carbon::setTestNow('2026-10-06 10:00');

        expect(AnalyticsRange::Last28->end(2)->toDateString())->toBe('2026-10-03')
            ->and(AnalyticsRange::Last28->start(2)->toDateString())->toBe('2026-09-06');
    });
});

describe('AnalyticsCache', function () {
    it('remembers until flushed', function () {
        $calls = 0;
        $compute = function () use (&$calls) {
            return ++$calls;
        };

        AnalyticsCache::remember('x', $compute);
        AnalyticsCache::remember('x', $compute);
        expect($calls)->toBe(1);

        AnalyticsCache::flush();
        AnalyticsCache::remember('x', $compute);
        expect($calls)->toBe(2);
    });
});
