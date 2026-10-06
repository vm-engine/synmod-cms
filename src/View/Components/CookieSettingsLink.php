<?php

declare(strict_types=1);

namespace VmEngine\Cms\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * "Cookie settings" button that reopens the consent banner. Renders only
 * where the banner itself is active; themes place it in their footer.
 */
class CookieSettingsLink extends Component
{
    public function shouldRender(): bool
    {
        return CookieConsent::isActive();
    }

    public function render(): View
    {
        return view('cms::components.cookie-settings-link');
    }
}
