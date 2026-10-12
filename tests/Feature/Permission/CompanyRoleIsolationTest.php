<?php

use App\Models\Company;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Reset permission cache
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Create test companies
    $this->companyA = Company::factory()->create(['name' => 'Company A']);
    $this->companyB = Company::factory()->create(['name' => 'Company B']);

    // Create permissions (global)
    Permission::firstOrCreate(['name' => 'view employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete employees', 'guard_name' => 'web']);
});

describe('Company Role Isolation', function () {
    it('can create role scoped to a specific company', function () {
        // Set team context for Company A
        setPermissionsTeamId($this->companyA->id);

        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        expect($role->company_id)->toBe($this->companyA->id);
    });

    it('can have same role name in different companies', function () {
        // Create admin role for Company A
        setPermissionsTeamId($this->companyA->id);
        $roleA = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create admin role for Company B
        setPermissionsTeamId($this->companyB->id);
        $roleB = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        expect($roleA->id)->not->toBe($roleB->id);
        expect($roleA->company_id)->toBe($this->companyA->id);
        expect($roleB->company_id)->toBe($this->companyB->id);
    });

    it('user can have role scoped to their company', function () {
        $user = User::factory()->create(['company_id' => $this->companyA->id]);

        // Set team context
        setPermissionsTeamId($this->companyA->id);

        // Create and assign role
        $role = Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $user->assignRole('manager');

        expect($user->hasRole('manager'))->toBeTrue();
    });

    it('user role check respects company context', function () {
        $userA = User::factory()->create(['company_id' => $this->companyA->id]);
        $userB = User::factory()->create(['company_id' => $this->companyB->id]);

        // Create manager role for Company A
        setPermissionsTeamId($this->companyA->id);
        Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $userA->assignRole('manager');

        // Create manager role for Company B
        setPermissionsTeamId($this->companyB->id);
        Role::create(['name' => 'manager', 'guard_name' => 'web']);
        $userB->assignRole('manager');

        // Check roles in Company A context
        setPermissionsTeamId($this->companyA->id);
        expect($userA->hasRole('manager'))->toBeTrue();

        // Check roles in Company B context
        setPermissionsTeamId($this->companyB->id);
        expect($userB->hasRole('manager'))->toBeTrue();
    });

    it('can assign permissions to role in company context', function () {
        setPermissionsTeamId($this->companyA->id);

        $role = Role::create(['name' => 'hr', 'guard_name' => 'web']);
        $role->givePermissionTo('view employees', 'edit employees');

        expect($role->hasPermissionTo('view employees'))->toBeTrue();
        expect($role->hasPermissionTo('edit employees'))->toBeTrue();
        expect($role->hasPermissionTo('delete employees'))->toBeFalse();
    });

    it('user can check permission through role in company context', function () {
        $user = User::factory()->create(['company_id' => $this->companyA->id]);

        setPermissionsTeamId($this->companyA->id);

        $role = Role::create(['name' => 'editor', 'guard_name' => 'web']);
        $role->givePermissionTo('view employees', 'edit employees');
        $user->assignRole('editor');

        expect($user->can('view employees'))->toBeTrue();
        expect($user->can('edit employees'))->toBeTrue();
        expect($user->can('delete employees'))->toBeFalse();
    });

    it('roles from one company are not visible in another company context using findByName', function () {
        // Create role in Company A
        setPermissionsTeamId($this->companyA->id);
        Role::create(['name' => 'special-role', 'guard_name' => 'web']);

        // Switch to Company B context
        setPermissionsTeamId($this->companyB->id);

        // Role should not exist in Company B context when using findByName
        // Note: Raw queries will still find it, but Spatie's methods respect team context
        expect(fn () => Role::findByName('special-role'))
            ->toThrow(\Spatie\Permission\Exceptions\RoleDoesNotExist::class);
    });

    it('can query roles scoped to specific company', function () {
        // Create role in Company A
        setPermissionsTeamId($this->companyA->id);
        Role::create(['name' => 'role-a', 'guard_name' => 'web']);

        // Create role in Company B
        setPermissionsTeamId($this->companyB->id);
        Role::create(['name' => 'role-b', 'guard_name' => 'web']);

        // Query roles for Company A - should only find role-a
        setPermissionsTeamId($this->companyA->id);
        $rolesA = Role::where('company_id', $this->companyA->id)->get();
        expect($rolesA)->toHaveCount(1);
        expect($rolesA->first()->name)->toBe('role-a');

        // Query roles for Company B - should only find role-b
        setPermissionsTeamId($this->companyB->id);
        $rolesB = Role::where('company_id', $this->companyB->id)->get();
        expect($rolesB)->toHaveCount(1);
        expect($rolesB->first()->name)->toBe('role-b');
    });
});

describe('Company Team Resolver', function () {
    it('resolves team id from tenant context', function () {
        app()->instance('tenant', $this->companyA);

        $resolver = new \App\Permission\CompanyTeamResolver;
        expect($resolver->getPermissionsTeamId())->toBe($this->companyA->id);
    });

    it('resolves team id from authenticated user when no tenant', function () {
        $user = User::factory()->create(['company_id' => $this->companyB->id]);
        $this->actingAs($user);

        // Ensure no tenant is bound
        app()->forgetInstance('tenant');

        $resolver = new \App\Permission\CompanyTeamResolver;
        expect($resolver->getPermissionsTeamId())->toBe($this->companyB->id);
    });

    it('returns null when no context available', function () {
        app()->forgetInstance('tenant');
        auth()->logout();

        $resolver = new \App\Permission\CompanyTeamResolver;
        expect($resolver->getPermissionsTeamId())->toBeNull();
    });

    it('can set team id explicitly', function () {
        $resolver = new \App\Permission\CompanyTeamResolver;
        $resolver->setPermissionsTeamId($this->companyA->id);

        expect($resolver->getPermissionsTeamId())->toBe($this->companyA->id);
    });
});

describe('Global Roles (Super Admin)', function () {
    it('can create global role with null company_id', function () {
        setPermissionsTeamId(null);

        $role = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);

        expect($role->company_id)->toBeNull();
    });

    it('global role is accessible regardless of company context', function () {
        // Create global super-admin role
        setPermissionsTeamId(null);
        $globalRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $globalRole->givePermissionTo(Permission::all());

        $superAdmin = User::factory()->create([
            'company_id' => null,
            'is_superadmin' => true,
        ]);

        // Assign without team context
        setPermissionsTeamId(null);
        $superAdmin->assignRole('super-admin');

        // Super admin should have all permissions
        expect($superAdmin->hasRole('super-admin'))->toBeTrue();
        expect($superAdmin->can('view employees'))->toBeTrue();
        expect($superAdmin->can('delete employees'))->toBeTrue();
    });
});
