<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;

uses(RefreshDatabase::class);

describe('analytics head', function () {
    it('renders nothing when unconfigured', function () {
        expect(trim(Blade::render('<x-cms::analytics-head />')))->toBe('');
    });

    it('renders the verification meta even outside production', function () {
        configureAnalytics();

        expect(Blade::render('<x-cms::analytics-head />'))
            ->toContain('<meta name="google-site-verification" content="verify-token" />')
            ->not->toContain('googletagmanager.com/gtag/js');
    });

    it('renders gtag with denied consent default in production', function () {
        configureAnalytics();
        app()->detectEnvironment(fn () => 'production');

        expect(Blade::render('<x-cms::analytics-head />'))
            ->toContain('https://www.googletagmanager.com/gtag/js?id=G-TEST123')
            ->toContain("gtag('config', 'G-TEST123')")
            ->toContain('var cmsConsentGranted = false ||');
    });

    it('grants consent by default when consent is not required', function () {
        configureAnalytics(['require_consent' => false]);
        app()->detectEnvironment(fn () => 'production');

        expect(Blade::render('<x-cms::analytics-head />'))->toContain('var cmsConsentGranted = true ||');
    });

    it('skips gtag for backend users', function () {
        configureAnalytics();
        app()->detectEnvironment(fn () => 'production');
        $this->actingAs(makeAnalyticsUser('settings', false));

        expect(Blade::render('<x-cms::analytics-head />'))->not->toContain('gtag/js');
    });

    it('adds google hosts to the CSP only when gtag renders', function () {
        configureAnalytics();
        Blade::render('<x-cms::analytics-head />');
        expect(config('synapps.apps.csp.script_src'))->not->toContain('https://*.googletagmanager.com');

        app()->detectEnvironment(fn () => 'production');
        Blade::render('<x-cms::analytics-head />');

        expect(config('synapps.apps.csp.script_src'))->toContain('https://*.googletagmanager.com')
            ->and(config('synapps.apps.csp.connect_src'))->toContain('https://*.google-analytics.com');
    });
});

describe('cookie consent', function () {
    it('renders the banner when tracking is active and consent is required', function () {
        configureAnalytics();
        app()->detectEnvironment(fn () => 'production');

        expect(Blade::render('<x-cms::cookie-consent />'))
            ->toContain('x-data="cmsConsent"')
            ->toContain(__('cms::labels.consent_accept'))
            ->toContain("Alpine.data('cmsConsent'")
            ->not->toContain('JSON.parse');
    });

    it('does not render when consent is not required', function () {
        configureAnalytics(['require_consent' => false]);
        app()->detectEnvironment(fn () => 'production');

        expect(trim(Blade::render('<x-cms::cookie-consent />')))->toBe('');
    });

    it('does not render when tracking is inactive', function () {
        configureAnalytics();

        expect(trim(Blade::render('<x-cms::cookie-consent />')))->toBe('');
    });

    it('renders the settings link only alongside the banner', function () {
        configureAnalytics();
        expect(trim(Blade::render('<x-cms::cookie-settings-link />')))->toBe('');

        app()->detectEnvironment(fn () => 'production');
        expect(Blade::render('<x-cms::cookie-settings-link />'))->toContain(__('cms::labels.consent_settings'));
    });
});
