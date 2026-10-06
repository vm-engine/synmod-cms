<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use VmEngine\Cms\Exceptions\AnalyticsException;
use VmEngine\Cms\Services\Analytics\GoogleApiClient;
use VmEngine\Cms\Support\AnalyticsSettings;

uses(RefreshDatabase::class);

function base64UrlDecode(string $value): string
{
    return base64_decode(strtr($value, '-_', '+/'));
}

it('exchanges a signed JWT for an access token', function () {
    configureAnalytics();
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3599])]);

    expect(app(GoogleApiClient::class)->token())->toBe('tok-1');

    Http::assertSent(function (Request $request) {
        [$header, $claims, $signature] = explode('.', $request['assertion']);
        $payload = json_decode(base64UrlDecode($claims), true);

        return $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer'
            && $payload['iss'] === 'reporter@demo.iam.gserviceaccount.com'
            && $payload['aud'] === 'https://oauth2.googleapis.com/token'
            && str_contains($payload['scope'], 'analytics.readonly')
            && str_contains($payload['scope'], 'webmasters.readonly')
            && openssl_verify("{$header}.{$claims}", base64UrlDecode($signature), analyticsServiceAccountFixture()['public_key'], OPENSSL_ALGO_SHA256) === 1;
    });
});

it('caches the access token', function () {
    configureAnalytics();
    Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3599])]);

    $client = app(GoogleApiClient::class);
    $client->token();
    $client->token();

    Http::assertSentCount(1);
});

it('throws not-configured without a service account', function () {
    configureAnalytics(withKey: false);

    expect(fn () => app(GoogleApiClient::class)->token())
        ->toThrow(fn (AnalyticsException $e) => expect($e->notConfigured)->toBeTrue());
});

it('throws on an unusable private key', function () {
    configureAnalytics(withKey: false);
    AnalyticsSettings::saveServiceAccount(json_encode(['type' => 'service_account', 'client_email' => 'a@b', 'private_key' => 'garbage']));

    expect(fn () => app(GoogleApiClient::class)->token())->toThrow(AnalyticsException::class);
});

it('posts with the bearer token and surfaces google errors', function () {
    configureAnalytics();
    Http::fake([
        'oauth2.googleapis.com/token' => Http::response(['access_token' => 'tok-1', 'expires_in' => 3599]),
        'api.test/ok' => Http::response(['rows' => [1]]),
        'api.test/denied' => Http::response(['error' => ['message' => 'User does not have sufficient permissions']], 403),
    ]);
    $client = app(GoogleApiClient::class);

    expect($client->post('https://api.test/ok', ['a' => 1]))->toBe(['rows' => [1]]);
    Http::assertSent(fn (Request $request) => $request->url() === 'https://api.test/ok' && $request->hasHeader('Authorization', 'Bearer tok-1'));

    expect(fn () => $client->post('https://api.test/denied', []))
        ->toThrow(AnalyticsException::class, 'User does not have sufficient permissions');
});
