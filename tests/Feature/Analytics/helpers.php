<?php

declare(strict_types=1);

use VmEngine\Cms\Support\AnalyticsSettings;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

/**
 * A throwaway RSA service-account key, generated once per process.
 *
 * @return array{json: string, public_key: string}
 */
function analyticsServiceAccountFixture(): array
{
    static $fixture = null;

    if ($fixture !== null) {
        return $fixture;
    }

    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($key, $privatePem);

    $fixture = [
        'json' => json_encode([
            'type' => 'service_account',
            'client_email' => 'reporter@demo.iam.gserviceaccount.com',
            'private_key' => $privatePem,
        ], JSON_THROW_ON_ERROR),
        'public_key' => openssl_pkey_get_details($key)['key'],
    ];

    return $fixture;
}

/**
 * @param  array<string, string|bool>  $overrides
 */
function configureAnalytics(array $overrides = [], bool $withKey = true): void
{
    AnalyticsSettings::save(array_merge([
        'measurement_id' => 'G-TEST123',
        'gsc_verification' => 'verify-token',
        'require_consent' => true,
        'property_id' => '123456',
        'gsc_site' => 'sc-domain:example.com',
    ], $overrides));

    if ($withKey) {
        AnalyticsSettings::saveServiceAccount(analyticsServiceAccountFixture()['json']);
    }
}

/**
 * @param  string|list<string>  $aclKeys  CMS ACL feature keys, all granted read (+ update when $canUpdate)
 */
function makeAnalyticsUser(string|array $aclKeys, bool $canUpdate): User
{
    $role = Role::factory()->create(['can_access_backend' => true]);
    foreach ((array) $aclKeys as $aclKey) {
        RolePermission::factory()->forRole($role)->forModule('cms', $aclKey)->create([
            'can_create' => false,
            'can_read' => true,
            'can_update' => $canUpdate,
            'can_delete' => false,
        ]);
    }
    $user = User::factory()->create();
    $user->roles()->attach($role);

    return $user;
}
