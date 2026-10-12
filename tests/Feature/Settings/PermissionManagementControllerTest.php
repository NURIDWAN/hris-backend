<?php

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Reset permission cache
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->company = Company::factory()->create();

    // Set team context
    setPermissionsTeamId($this->company->id);

    // Create admin role with manage roles permission
    $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage roles', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete employees', 'guard_name' => 'web']);

    $this->adminRole->givePermissionTo(Permission::all());

    // Create admin user
    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');

    $this->actingAs($this->admin);
});

describe('PermissionManagementController', function () {
    describe('index', function () {
        it('displays list of all permissions', function () {
            $response = $this->get(route('settings.permissions.index'));

            $response->assertOk();
            $response->assertViewIs('settings.permissions.index');
            $response->assertViewHas('permissions');
            $response->assertSee('view employees');
            $response->assertSee('manage roles');
        });

        it('groups permissions by resource', function () {
            $response = $this->get(route('settings.permissions.index'));

            $response->assertOk();
            // Should see grouped permissions
            $response->assertSee('employees');
            $response->assertSee('roles');
        });

        it('denies access without manage roles permission', function () {
            // Create HR user without 'manage roles' permission
            $hrRole = Role::create(['name' => 'hr', 'guard_name' => 'web']);
            $hrRole->givePermissionTo('view employees');

            $hrUser = User::factory()->create(['company_id' => $this->company->id]);
            $hrUser->assignRole('hr');

            $this->actingAs($hrUser);

            $response = $this->get(route('settings.permissions.index'));

            $response->assertForbidden();
        });
    });

    describe('create', function () {
        it('displays create permission form', function () {
            $response = $this->get(route('settings.permissions.create'));

            $response->assertOk();
            $response->assertViewIs('settings.permissions.create');
        });
    });

    describe('store', function () {
        it('creates new permission', function () {
            $response = $this->post(route('settings.permissions.store'), [
                'name' => 'approve leaves',
            ]);

            $response->assertRedirect(route('settings.permissions.index'));
            $response->assertSessionHas('success');

            expect(Permission::where('name', 'approve leaves')->exists())->toBeTrue();
        });

        it('validates required name', function () {
            $response = $this->post(route('settings.permissions.store'), [
                'name' => '',
            ]);

            $response->assertSessionHasErrors(['name']);
        });

        it('prevents duplicate permission names', function () {
            $response = $this->post(route('settings.permissions.store'), [
                'name' => 'view employees',
            ]);

            $response->assertSessionHasErrors(['name']);
        });
    });

    describe('edit', function () {
        it('displays edit permission form', function () {
            $permission = Permission::where('name', 'view employees')->first();

            $response = $this->get(route('settings.permissions.edit', $permission));

            $response->assertOk();
            $response->assertViewIs('settings.permissions.edit');
            $response->assertViewHas('permission');
        });
    });

    describe('update', function () {
        it('updates permission name', function () {
            $permission = Permission::create(['name' => 'old-permission', 'guard_name' => 'web']);

            $response = $this->put(route('settings.permissions.update', $permission), [
                'name' => 'new-permission',
            ]);

            $response->assertRedirect(route('settings.permissions.index'));
            $response->assertSessionHas('success');

            $permission->refresh();
            expect($permission->name)->toBe('new-permission');
        });

        it('validates unique permission name on update', function () {
            $permission = Permission::create(['name' => 'test-permission', 'guard_name' => 'web']);

            $response = $this->put(route('settings.permissions.update', $permission), [
                'name' => 'view employees',
            ]);

            $response->assertSessionHasErrors(['name']);
        });
    });

    describe('destroy', function () {
        it('deletes permission', function () {
            $permission = Permission::create(['name' => 'to-delete', 'guard_name' => 'web']);

            $response = $this->delete(route('settings.permissions.destroy', $permission));

            $response->assertRedirect(route('settings.permissions.index'));
            $response->assertSessionHas('success');

            expect(Permission::where('name', 'to-delete')->first())->toBeNull();
        });

        it('cannot delete permission that is assigned to roles', function () {
            $permission = Permission::where('name', 'view employees')->first();

            // Admin role already has this permission
            $response = $this->delete(route('settings.permissions.destroy', $permission));

            $response->assertRedirect();
            $response->assertSessionHas('error');

            // Permission should still exist
            expect(Permission::where('name', 'view employees')->first())->not->toBeNull();
        });
    });
});
