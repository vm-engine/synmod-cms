<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use VmEngine\Cms\Support\AnalyticsSettings;

/**
 * Consent Mode v2 banner: Accept/Reject updates gtag consent and stores the
 * choice in the `cms_consent` cookie (180 days). Rendered only where GA runs
 * and consent is required.
 */
class CookieConsent extends Component
{
    public static function isActive(): bool
    {
        return AnalyticsSettings::all()['require_consent'] && AnalyticsSettings::trackingActive();
    }

    public function shouldRender(): bool
    {
        return self::isActive();
    }

    public function render(): View
    {
        return view('cms::components.cookie-consent');
    }
}
