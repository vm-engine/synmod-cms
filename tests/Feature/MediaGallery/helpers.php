<?php

declare(strict_types=1);

use VmEngine\Fm\Models\FmFile;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

function mgImageFile(string $name = 'beach.jpg', string $mime = 'image/jpeg'): FmFile
{
    return FmFile::create([
        'disk' => 'public', 'folder_path' => 'fm/public', 'relative_path' => $name,
        'filename' => $name, 'original_name' => $name, 'extension' => pathinfo($name, PATHINFO_EXTENSION),
        'mime_type' => $mime, 'size' => 1024, 'has_thumbnail' => false, 'is_trashed' => false,
    ]);
}

function mgAdmin(): User
{
    $role = Role::factory();
    foreach ([['page'], ['article'], ['article', 'section'], ['download', 'category'], ['download', 'file']] as $acl) {
        $role = $role->has(RolePermission::factory()->forModule('cms', ...$acl)->fullCrud());
    }
    $user = User::factory()->create();
    $user->roles()->attach($role->create(['level' => 5, 'can_access_backend' => true]));

    return $user;
}

function mgDev(): User
{
    $user = User::factory()->create();
    $user->roles()->attach(Role::where('slug', 'dev')->first() ?? Role::factory()->dev()->create());

    return $user;
}
