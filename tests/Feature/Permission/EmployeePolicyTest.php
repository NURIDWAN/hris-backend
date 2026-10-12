<?php

use App\Models\Company;
use App\Models\Employee;
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

    // Create roles
    $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $this->hrRole = Role::create(['name' => 'hr-manager', 'guard_name' => 'web']);
    $this->employeeRole = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    // Create permissions for employees module
    Permission::firstOrCreate(['name' => 'view employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete employees', 'guard_name' => 'web']);

    // Assign permissions to roles
    $this->adminRole->givePermissionTo(['view employees', 'create employees', 'edit employees', 'delete employees']);
    $this->hrRole->givePermissionTo(['view employees', 'create employees', 'edit employees']);
    $this->employeeRole->givePermissionTo(['view employees']);

    // Create test employee
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('EmployeePolicy', function () {
    describe('viewAny', function () {
        it('allows admin to view employees list', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('viewAny', Employee::class))->toBeTrue();
        });

        it('allows hr-manager to view employees list', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('hr-manager');

            expect($user->can('viewAny', Employee::class))->toBeTrue();
        });

        it('allows employee to view employees list', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('employee');

            expect($user->can('viewAny', Employee::class))->toBeTrue();
        });

        it('denies user without view employees permission', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            // No role assigned

            expect($user->can('viewAny', Employee::class))->toBeFalse();
        });
    });

    describe('view', function () {
        it('allows admin to view employee details', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('view', $this->employee))->toBeTrue();
        });

        it('allows employee to view own profile', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('employee');

            $ownEmployee = Employee::factory()->create([
                'company_id' => $this->company->id,
                'user_id' => $user->id,
            ]);

            expect($user->can('view', $ownEmployee))->toBeTrue();
        });

        it('denies viewing employee from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('view', $otherEmployee))->toBeFalse();
        });
    });

    describe('create', function () {
        it('allows admin to create employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('create', Employee::class))->toBeTrue();
        });

        it('allows hr-manager to create employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('hr-manager');

            expect($user->can('create', Employee::class))->toBeTrue();
        });

        it('denies employee to create employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('employee');

            expect($user->can('create', Employee::class))->toBeFalse();
        });
    });

    describe('update', function () {
        it('allows admin to update employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('update', $this->employee))->toBeTrue();
        });

        it('allows hr-manager to update employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('hr-manager');

            expect($user->can('update', $this->employee))->toBeTrue();
        });

        it('denies employee to update other employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('employee');

            expect($user->can('update', $this->employee))->toBeFalse();
        });

        it('denies updating employee from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('update', $otherEmployee))->toBeFalse();
        });
    });

    describe('delete', function () {
        it('allows admin to delete employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('delete', $this->employee))->toBeTrue();
        });

        it('denies hr-manager to delete employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('hr-manager');

            expect($user->can('delete', $this->employee))->toBeFalse();
        });

        it('denies employee to delete employees', function () {
            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('employee');

            expect($user->can('delete', $this->employee))->toBeFalse();
        });

        it('denies deleting employee from another company', function () {
            $otherCompany = Company::factory()->create();
            $otherEmployee = Employee::factory()->create([
                'company_id' => $otherCompany->id,
            ]);

            $user = User::factory()->create(['company_id' => $this->company->id]);
            $user->assignRole('admin');

            expect($user->can('delete', $otherEmployee))->toBeFalse();
        });
    });
});

describe('Company Isolation in Policy', function () {
    it('ensures policy respects company boundaries', function () {
        $companyA = $this->company;
        $companyB = Company::factory()->create();

        // Create admin for Company A
        $adminA = User::factory()->create(['company_id' => $companyA->id]);
        setPermissionsTeamId($companyA->id);
        $adminA->assignRole('admin');

        // Create employee in Company B
        $employeeB = Employee::factory()->create([
            'company_id' => $companyB->id,
        ]);

        // Admin A should NOT be able to manage Employee B
        expect($adminA->can('view', $employeeB))->toBeFalse();
        expect($adminA->can('update', $employeeB))->toBeFalse();
        expect($adminA->can('delete', $employeeB))->toBeFalse();
    });

    it('admin can manage employees in own company', function () {
        $admin = User::factory()->create(['company_id' => $this->company->id]);
        $admin->assignRole('admin');

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        expect($admin->can('view', $employee))->toBeTrue();
        expect($admin->can('update', $employee))->toBeTrue();
        expect($admin->can('delete', $employee))->toBeTrue();
    });
});
