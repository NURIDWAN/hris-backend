<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Roles & Permissions', function () {

    beforeEach(function () {
        // Create super-admin as a global role (no team context)
        setPermissionsTeamId(null);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        // Create company-scoped roles using a shared test company
        $this->testCompany = Company::factory()->create();
        createCompanyRoles($this->testCompany->id, ['admin', 'hr-manager', 'payroll-manager', 'employee']);
    });

    it('has defined roles', function () {
        expect(Role::count())->toBe(5)
            ->and(Role::where('name', 'super-admin')->exists())->toBeTrue()
            ->and(Role::where('name', 'admin')->exists())->toBeTrue()
            ->and(Role::where('name', 'hr-manager')->exists())->toBeTrue()
            ->and(Role::where('name', 'payroll-manager')->exists())->toBeTrue()
            ->and(Role::where('name', 'employee')->exists())->toBeTrue();
    });

    it('can create permissions', function () {
        Permission::create(['name' => 'view employees']);
        Permission::create(['name' => 'create employees']);
        Permission::create(['name' => 'edit employees']);
        Permission::create(['name' => 'delete employees']);

        expect(Permission::count())->toBe(4);
    });

    it('can assign permissions to roles', function () {
        setPermissionsTeamId($this->testCompany->id);
        $hrManager = Role::findByName('hr-manager');

        Permission::create(['name' => 'view employees']);
        Permission::create(['name' => 'create employees']);
        Permission::create(['name' => 'edit employees']);

        $hrManager->givePermissionTo(['view employees', 'create employees', 'edit employees']);

        expect($hrManager->hasPermissionTo('view employees'))->toBeTrue()
            ->and($hrManager->hasPermissionTo('create employees'))->toBeTrue()
            ->and($hrManager->hasPermissionTo('edit employees'))->toBeTrue();
    });

    it('user inherits permissions from role', function () {
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);

        Permission::create(['name' => 'view payroll']);
        Permission::create(['name' => 'process payroll']);

        setPermissionsTeamId($this->testCompany->id);
        $payrollManager = Role::findByName('payroll-manager');
        $payrollManager->givePermissionTo(['view payroll', 'process payroll']);

        $user->assignRole('payroll-manager');

        expect($user->hasPermissionTo('view payroll'))->toBeTrue()
            ->and($user->hasPermissionTo('process payroll'))->toBeTrue();
    });

    it('super admin has all permissions', function () {
        setPermissionsTeamId(null);
        $user = User::factory()->create(['company_id' => null]);

        Permission::create(['name' => 'manage companies']);
        Permission::create(['name' => 'manage subscriptions']);

        $superAdmin = Role::findByName('super-admin');
        $superAdmin->givePermissionTo(Permission::all());

        $user->assignRole('super-admin');

        expect($user->hasPermissionTo('manage companies'))->toBeTrue()
            ->and($user->hasPermissionTo('manage subscriptions'))->toBeTrue();
    });

    it('can revoke permissions', function () {
        $user = User::factory()->create();

        Permission::create(['name' => 'edit settings']);
        $user->givePermissionTo('edit settings');

        expect($user->hasPermissionTo('edit settings'))->toBeTrue();

        $user->revokePermissionTo('edit settings');

        expect($user->fresh()->hasPermissionTo('edit settings'))->toBeFalse();
    });

    it('can check multiple roles', function () {
        setPermissionsTeamId($this->testCompany->id);
        $user = User::factory()->create(['company_id' => $this->testCompany->id]);

        $user->assignRole(['hr-manager', 'payroll-manager']);

        expect($user->hasRole('hr-manager'))->toBeTrue()
            ->and($user->hasRole('payroll-manager'))->toBeTrue()
            ->and($user->hasRole(['hr-manager', 'payroll-manager']))->toBeTrue()
            ->and($user->hasAllRoles(['hr-manager', 'payroll-manager']))->toBeTrue();
    });

});
