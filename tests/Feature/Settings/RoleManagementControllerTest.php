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

describe('RoleManagementController', function () {
    describe('index', function () {
        it('displays list of roles for current company', function () {
            // Create additional roles
            Role::create(['name' => 'hr-manager', 'guard_name' => 'web']);
            Role::create(['name' => 'employee', 'guard_name' => 'web']);

            $response = $this->get(route('settings.roles.index'));

            $response->assertOk();
            $response->assertViewIs('settings.roles.index');
            $response->assertViewHas('roles');
            $response->assertSee('admin');
            $response->assertSee('hr-manager');
            $response->assertSee('employee');
        });

        it('only shows roles belonging to current company', function () {
            // Create role for another company
            $otherCompany = Company::factory()->create();
            setPermissionsTeamId($otherCompany->id);
            Role::create(['name' => 'other-role', 'guard_name' => 'web']);

            // Switch back to our company
            setPermissionsTeamId($this->company->id);

            $response = $this->get(route('settings.roles.index'));

            $response->assertOk();
            $response->assertDontSee('other-role');
        });

        it('denies access without manage roles permission', function () {
            // Create HR user without 'manage roles' permission
            $hrRole = Role::create(['name' => 'hr', 'guard_name' => 'web']);
            $hrRole->givePermissionTo('view employees'); // Give some permission but not 'manage roles'

            $hrUser = User::factory()->create(['company_id' => $this->company->id]);
            $hrUser->assignRole('hr');

            $this->actingAs($hrUser);

            $response = $this->get(route('settings.roles.index'));

            $response->assertForbidden();
        });
    });

    describe('create', function () {
        it('displays create role form', function () {
            $response = $this->get(route('settings.roles.create'));

            $response->assertOk();
            $response->assertViewIs('settings.roles.create');
            $response->assertViewHas('permissions');
        });
    });

    describe('store', function () {
        it('creates new role with permissions', function () {
            $response = $this->post(route('settings.roles.store'), [
                'name' => 'custom-role',
                'permissions' => ['view employees', 'create employees'],
            ]);

            $response->assertRedirect(route('settings.roles.index'));
            $response->assertSessionHas('success');

            $role = Role::where('name', 'custom-role')
                ->where('company_id', $this->company->id)
                ->first();

            expect($role)->not->toBeNull();
            expect($role->hasPermissionTo('view employees'))->toBeTrue();
            expect($role->hasPermissionTo('create employees'))->toBeTrue();
        });

        it('validates required name', function () {
            $response = $this->post(route('settings.roles.store'), [
                'name' => '',
                'permissions' => [],
            ]);

            $response->assertSessionHasErrors(['name']);
        });

        it('prevents duplicate role names in same company', function () {
            Role::create(['name' => 'existing-role', 'guard_name' => 'web']);

            $response = $this->post(route('settings.roles.store'), [
                'name' => 'existing-role',
                'permissions' => [],
            ]);

            $response->assertSessionHasErrors(['name']);
        });

        it('allows same role name in different company', function () {
            // Create role in another company
            $otherCompany = Company::factory()->create();
            setPermissionsTeamId($otherCompany->id);
            Role::create(['name' => 'shared-name', 'guard_name' => 'web']);

            // Switch back and create same name in our company
            setPermissionsTeamId($this->company->id);

            $response = $this->post(route('settings.roles.store'), [
                'name' => 'shared-name',
                'permissions' => [],
            ]);

            $response->assertRedirect(route('settings.roles.index'));
            $response->assertSessionHas('success');
        });
    });

    describe('edit', function () {
        it('displays edit role form', function () {
            $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
            $role->givePermissionTo('view employees');

            $response = $this->get(route('settings.roles.edit', $role));

            $response->assertOk();
            $response->assertViewIs('settings.roles.edit');
            $response->assertViewHas('role');
            $response->assertViewHas('permissions');
        });

        it('cannot edit role from another company', function () {
            $otherCompany = Company::factory()->create();
            setPermissionsTeamId($otherCompany->id);
            $otherRole = Role::create(['name' => 'other-role', 'guard_name' => 'web']);

            setPermissionsTeamId($this->company->id);

            $response = $this->get(route('settings.roles.edit', $otherRole));

            $response->assertNotFound();
        });
    });

    describe('update', function () {
        it('updates role name and permissions', function () {
            $role = Role::create(['name' => 'old-name', 'guard_name' => 'web']);
            $role->givePermissionTo('view employees');

            $response = $this->put(route('settings.roles.update', $role), [
                'name' => 'new-name',
                'permissions' => ['view employees', 'edit employees'],
            ]);

            $response->assertRedirect(route('settings.roles.index'));
            $response->assertSessionHas('success');

            $role->refresh();
            expect($role->name)->toBe('new-name');
            expect($role->hasPermissionTo('view employees'))->toBeTrue();
            expect($role->hasPermissionTo('edit employees'))->toBeTrue();
        });

        it('cannot update role from another company', function () {
            $otherCompany = Company::factory()->create();
            setPermissionsTeamId($otherCompany->id);
            $otherRole = Role::create(['name' => 'other-role', 'guard_name' => 'web']);

            setPermissionsTeamId($this->company->id);

            $response = $this->put(route('settings.roles.update', $otherRole), [
                'name' => 'hacked',
                'permissions' => [],
            ]);

            $response->assertNotFound();
        });
    });

    describe('destroy', function () {
        it('deletes role', function () {
            $role = Role::create(['name' => 'to-delete', 'guard_name' => 'web']);

            $response = $this->delete(route('settings.roles.destroy', $role));

            $response->assertRedirect(route('settings.roles.index'));
            $response->assertSessionHas('success');

            expect(Role::where('name', 'to-delete')->first())->toBeNull();
        });

        it('cannot delete role from another company', function () {
            $otherCompany = Company::factory()->create();
            setPermissionsTeamId($otherCompany->id);
            $otherRole = Role::create(['name' => 'other-role', 'guard_name' => 'web']);

            setPermissionsTeamId($this->company->id);

            $response = $this->delete(route('settings.roles.destroy', $otherRole));

            $response->assertNotFound();

            // Role should still exist
            setPermissionsTeamId($otherCompany->id);
            expect(Role::where('name', 'other-role')->first())->not->toBeNull();
        });

        it('cannot delete role that has users assigned', function () {
            $role = Role::create(['name' => 'in-use', 'guard_name' => 'web']);
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('in-use');

            $response = $this->delete(route('settings.roles.destroy', $role));

            $response->assertRedirect();
            $response->assertSessionHas('error');

            expect(Role::where('name', 'in-use')->first())->not->toBeNull();
        });
    });
});
