<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\AnalyticsSettings;

/**
 * Google Search Console verification meta + GA4 gtag.js (Consent Mode v2)
 * for the layout <head>. When gtag renders it also allowlists Google's
 * hosts in the CSP — SecureHeaders builds the header after the view renders,
 * so only pages that actually load GA get the wider policy.
 */
class AnalyticsHead extends Component
{
    /** Google's documented GA4 CSP hosts. */
    private const CSP_HOSTS = [
        'script_src' => ['https://*.googletagmanager.com'],
        'connect_src' => ['https://*.google-analytics.com', 'https://*.analytics.google.com', 'https://*.googletagmanager.com'],
        'img_src' => ['https://*.google-analytics.com', 'https://*.googletagmanager.com'],
    ];

    public string $verification;

    public string $measurementId;

    public bool $requireConsent;

    public function __construct()
    {
        $settings = AnalyticsSettings::all();

        $this->verification = $settings['gsc_verification'];
        $this->measurementId = AnalyticsSettings::trackingActive() ? $settings['measurement_id'] : '';
        $this->requireConsent = $settings['require_consent'];

        if ($this->measurementId !== '') {
            self::allowGoogleInCsp();
        }
    }

    public function shouldRender(): bool
    {
        return $this->verification !== '' || $this->measurementId !== '';
    }

    public function render(): View
    {
        return view('cms::components.analytics-head');
    }

    private static function allowGoogleInCsp(): void
    {
        /** @var array<string, list<string>> $csp */
        $csp = config('synapps.apps.csp', []);

        foreach (self::CSP_HOSTS as $directive => $hosts) {
            $csp[$directive] = array_values(array_unique(array_merge($csp[$directive] ?? [], $hosts)));
        }

        config(['synapps.apps.csp' => $csp]);
    }
}
