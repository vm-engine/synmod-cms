<?php

declare(strict_types=1);

namespace VmEngine\Cms\Exceptions;

use RuntimeException;

/**
 * A Google reporting failure. `$notConfigured` distinguishes "fill in the
 * settings" from a real API error so the dashboard can show the right state.
 */
class AnalyticsException extends RuntimeException
{
    public function __construct(string $message, public readonly bool $notConfigured = false)
    {
        parent::__construct($message);
    }

    public static function notConfigured(): self
    {
        return new self(__('cms::labels.analytics_not_configured'), notConfigured: true);
    }

    public static function api(string $message): self
    {
        return new self($message);
    }
}
