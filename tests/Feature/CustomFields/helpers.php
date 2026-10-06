<?php

declare(strict_types=1);

use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

/**
 * Custom-field test helpers shared across test files. Loaded with require_once
 * so each file works on its own (e.g. in a separate --parallel worker).
 */
function cfAdminUser(): User
{
    $role = Role::factory();
    foreach ([['page'], ['article'], ['article', 'section'], ['gallery', 'album'], ['gallery', 'image'], ['download', 'category'], ['download', 'file']] as $acl) {
        $role = $role->has(RolePermission::factory()->forModule('cms', ...$acl)->fullCrud());
    }
    $user = User::factory()->create();
    $user->roles()->attach($role->create(['level' => 5, 'can_access_backend' => true]));

    return $user;
}
