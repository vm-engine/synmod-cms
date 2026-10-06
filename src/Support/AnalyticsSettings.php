<?php

declare(strict_types=1);

namespace VmEngine\Cms\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use VmEngine\SynAuth\Models\User;

/**
 * Google Analytics / Search Console settings, stored in dbconf as one JSON
 * blob (one query per request) plus the encrypted service-account key, which
 * is kept separate so frontend requests never decrypt it.
 */
final class AnalyticsSettings
{
    private const CONFIG_KEY = 'cms.analytics';

    private const SECRET_KEY = 'cms.analytics_key';

    private const DEFAULTS = [
        'measurement_id' => '',
        'gsc_verification' => '',
        'require_consent' => true,
        'property_id' => '',
        'gsc_site' => '',
    ];

    /**
     * @return array{measurement_id: string, gsc_verification: string, require_consent: bool, property_id: string, gsc_site: string}
     */
    public static function all(): array
    {
        $stored = json_decode((string) dbconf(self::CONFIG_KEY, ''), true);

        /** @var array{measurement_id: string, gsc_verification: string, require_consent: bool, property_id: string, gsc_site: string} */
        return array_merge(self::DEFAULTS, is_array($stored) ? array_intersect_key($stored, self::DEFAULTS) : []);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function save(array $values): void
    {
        $merged = array_merge(self::DEFAULTS, array_intersect_key($values, self::DEFAULTS));

        dbconf([self::CONFIG_KEY => json_encode($merged, JSON_THROW_ON_ERROR)]);
    }

    /**
     * @return array{client_email: string, private_key: string}|null
     */
    public static function serviceAccount(): ?array
    {
        $encrypted = (string) dbconf(self::SECRET_KEY, '');

        if ($encrypted === '') {
            return null;
        }

        try {
            $data = json_decode(Crypt::decryptString($encrypted), true);
        } catch (DecryptException) {
            return null;
        }

        return self::isServiceAccount($data)
            ? ['client_email' => $data['client_email'], 'private_key' => $data['private_key']]
            : null;
    }

    public static function saveServiceAccount(?string $json): void
    {
        dbconf([self::SECRET_KEY => $json === null ? '' : Crypt::encryptString($json)]);
    }

    /**
     * @phpstan-assert-if-true array{type: string, client_email: string, private_key: string} $data
     */
    public static function isServiceAccount(mixed $data): bool
    {
        return is_array($data)
            && ($data['type'] ?? null) === 'service_account'
            && is_string($data['client_email'] ?? null)
            && is_string($data['private_key'] ?? null);
    }

    /**
     * GA runs only in production, only with a Measurement ID, and never for
     * backend users (keeps admin visits out of the stats).
     */
    public static function trackingActive(): bool
    {
        if (self::all()['measurement_id'] === '' || ! app()->environment('production')) {
            return false;
        }

        $user = Auth::user();

        return ! ($user instanceof User && $user->canAccessBackend());
    }
}
