<?php

declare(strict_types=1);

use VmEngine\Cms\Models\CmsBuilderPage;
use VmEngine\SynAuth\Models\Role;
use VmEngine\SynAuth\Models\RolePermission;
use VmEngine\SynAuth\Models\User;

beforeEach(function () {
    $this->page = CmsBuilderPage::factory()->create();

    $this->stranger = User::factory()->create();
    // A backend user whose only permission is for another CMS feature.
    $this->stranger->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'tag')->fullCrud()
    )->create(['slug' => 'stranger', 'level' => 5, 'can_access_backend' => true]));

    $this->editor = User::factory()->create();
    $this->editor->roles()->attach(Role::factory()->has(
        RolePermission::factory()->forModule('cms', 'builder')->fullCrud()
    )->create(['slug' => 'builder-editor', 'level' => 5, 'can_access_backend' => true]));
});

it('guards every builder route', function (string $route, bool $needsId) {
    $params = $needsId ? ['id' => $this->page->id] : [];

    // can-access redirects denied users to the backend dashboard instead of a 403.
    $this->actingAs($this->stranger)->get(backend_route($route, $params))
        ->assertRedirect(backend_route(config('synapps.auth.routes.after_login')));
    $this->actingAs($this->editor)->get(backend_route($route, $params))->assertOk();
})->with([
    'list' => ['cms.builder.index', false],
    'form' => ['cms.builder.form', false],
    'editor' => ['cms.builder.editor', true],
    'canvas' => ['cms.builder.canvas', true],
]);

it('shows Page Builder right after Pages in the nav', function () {
    $module = json_decode((string) file_get_contents(__DIR__.'/../../../module.json'), true);

    expect(array_slice(array_keys($module['nav']['backend']['sub']), 0, 2))->toBe(['page', 'builder']);
});
