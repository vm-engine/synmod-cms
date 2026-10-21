<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use VmEngine\Cms\Models\CmsDownload;
use VmEngine\Cms\Models\CmsDownloadFile;
use VmEngine\Fm\Models\FmFile;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

uses(RefreshDatabase::class);

beforeEach(function () {
    $categoryPerm = RolePermission::factory()->forModule('cms', 'download', 'category')->fullCrud();
    $filePerm = RolePermission::factory()->forModule('cms', 'download', 'file')->fullCrud();
    $this->adminRole = Role::factory()
        ->has($categoryPerm)
        ->has($filePerm)
        ->create(['name' => 'Administrator', 'slug' => 'admin', 'level' => 5, 'can_access_backend' => true]);
    $this->user = User::factory()->create();
    $this->user->roles()->attach($this->adminRole);

    $this->download = CmsDownload::factory()->create([
        'title' => 'Test Download',
        'slug' => 'test-download',
    ]);
});

describe('Model Factory', function () {
    it('can create a download file using factory', function () {
        $file = CmsDownloadFile::factory()->create([
            'download_id' => $this->download->id,
            'title' => 'Manual PDF',
            'description' => 'The user manual',
        ]);

        expect($file->title)->toBe('Manual PDF')
            ->and($file->download_id)->toBe($this->download->id);

        $this->assertDatabaseHas('cms_download_files', ['title' => 'Manual PDF']);
    });

    it('has a delete token', function () {
        $file = CmsDownloadFile::factory()->create(['download_id' => $this->download->id]);

        expect($file->delete_token)->toBeString()->not->toBeEmpty();
    });
});

describe('Create Download File', function () {
    it('can add a file to a download via Livewire component', function () {
        $fmFile = FmFile::create([
            'disk' => 'public',
            'folder_path' => 'fm/public',
            'relative_path' => 'manual.pdf',
            'filename' => 'manual.pdf',
            'original_name' => 'manual.pdf',
            'extension' => 'pdf',
            'mime_type' => 'application/pdf',
            'size' => 2048,
            'has_thumbnail' => false,
            'is_trashed' => false,
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.download-file-manager', ['downloadId' => $this->download->id])
            ->set('newFileUrl', '/storage/fm/public/manual.pdf')
            ->set('newFileId', $fmFile->id)
            ->set('newFileTitle', 'User Manual')
            ->set('newFileDescription', 'PDF manual')
            ->call('addFile')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('cms_download_files', [
            'download_id' => $this->download->id,
            'file_id' => $fmFile->id,
            'title' => 'User Manual',
        ]);
    });
});

describe('Delete Download File', function () {
    it('can delete a download file via Livewire component', function () {
        $file = CmsDownloadFile::factory()->create([
            'download_id' => $this->download->id,
            'title' => 'To Delete',
        ]);

        Livewire::actingAs($this->user)
            ->test('cms::backend.download-file-manager', ['downloadId' => $this->download->id])
            ->call('deleteFile', $file->delete_token)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('cms_download_files', ['id' => $file->id]);
    });
});

describe('ACL', function () {
    it('denies access to download file manager without permission', function () {
        $noPermRole = Role::factory()->create(['name' => 'No Perm', 'slug' => 'no-perm', 'level' => 9]);
        $noPermUser = User::factory()->create();
        $noPermUser->roles()->attach($noPermRole);

        $this->actingAs($noPermUser)
            ->get(backend_route('cms.download.files', ['downloadId' => $this->download->id]))
            ->assertStatus(302);
    });
});
