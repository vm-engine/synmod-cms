<?php

declare(strict_types=1);

namespace VmEngine\Cms\Services\Analytics;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use VmEngine\Cms\Exceptions\AnalyticsException;
use VmEngine\Cms\Support\AnalyticsSettings;

/**
 * Minimal Google REST client authenticated as the configured service
 * account: a self-signed RS256 JWT is exchanged for an OAuth access token.
 */
class GoogleApiClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const SCOPES = 'https://www.googleapis.com/auth/analytics.readonly https://www.googleapis.com/auth/webmasters.readonly';

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    public function post(string $url, array $body): array
    {
        $response = Http::withToken($this->token())->acceptJson()->timeout(15)->post($url, $body);

        if ($response->failed()) {
            throw AnalyticsException::api((string) ($response->json('error.message') ?? 'HTTP '.$response->status()));
        }

        return (array) $response->json();
    }

    public function token(): string
    {
        $account = AnalyticsSettings::serviceAccount() ?? throw AnalyticsException::notConfigured();
        $cacheKey = 'cms.analytics.token.'.md5($account['private_key']);

        $cached = Cache::get($cacheKey);
        if (is_string($cached)) {
            return $cached;
        }

        $response = Http::asForm()->timeout(15)->post(self::TOKEN_URL, [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $this->signedAssertion($account),
        ]);

        if ($response->failed() || ! is_string($response->json('access_token'))) {
            throw AnalyticsException::api((string) ($response->json('error_description') ?? 'HTTP '.$response->status()));
        }

        $token = $response->json('access_token');
        Cache::put($cacheKey, $token, max(60, (int) $response->json('expires_in', 3600) - 60));

        return $token;
    }

    /**
     * @param  array{client_email: string, private_key: string}  $account
     */
    private function signedAssertion(array $account): string
    {
        $privateKey = openssl_pkey_get_private($account['private_key']);

        if ($privateKey === false) {
            throw AnalyticsException::api(__('cms::labels.analytics_key_invalid'));
        }

        $now = time();
        $input = self::base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR))
            .'.'.self::base64Url(json_encode([
                'iss' => $account['client_email'],
                'scope' => self::SCOPES,
                'aud' => self::TOKEN_URL,
                'iat' => $now,
                'exp' => $now + 3600,
            ], JSON_THROW_ON_ERROR));

        openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return $input.'.'.self::base64Url($signature);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
