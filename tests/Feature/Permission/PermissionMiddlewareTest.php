<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    // Reset permission cache
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->company = Company::factory()->create();

    // Set team context
    setPermissionsTeamId($this->company->id);

    // Create role with permissions
    $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $this->hrRole = Role::create(['name' => 'hr-manager', 'guard_name' => 'web']);
    $this->employeeRole = Role::create(['name' => 'employee', 'guard_name' => 'web']);

    // Create permissions
    Permission::firstOrCreate(['name' => 'view employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'create employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'edit employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'delete employees', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'view payroll', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'manage payroll', 'guard_name' => 'web']);

    // Admin has all permissions
    $this->adminRole->givePermissionTo(Permission::all());

    // HR has employee and limited payroll permissions
    $this->hrRole->givePermissionTo([
        'view employees',
        'create employees',
        'edit employees',
        'view payroll',
    ]);

    // Employee has only view permissions
    $this->employeeRole->givePermissionTo(['view employees']);
});

describe('Spatie Permission Middleware', function () {
    it('allows user with required permission to access route', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('admin');

        $this->actingAs($user);

        expect($user->can('view employees'))->toBeTrue();
        expect($user->can('delete employees'))->toBeTrue();
        expect($user->can('manage payroll'))->toBeTrue();
    });

    it('denies user without required permission', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('employee');

        $this->actingAs($user);

        expect($user->can('view employees'))->toBeTrue();
        expect($user->can('delete employees'))->toBeFalse();
        expect($user->can('manage payroll'))->toBeFalse();
    });

    it('hr-manager has limited permissions', function () {
        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('hr-manager');

        $this->actingAs($user);

        expect($user->can('view employees'))->toBeTrue();
        expect($user->can('create employees'))->toBeTrue();
        expect($user->can('edit employees'))->toBeTrue();
        expect($user->can('delete employees'))->toBeFalse(); // HR cannot delete
        expect($user->can('view payroll'))->toBeTrue();
        expect($user->can('manage payroll'))->toBeFalse(); // HR cannot manage payroll
    });
});

describe('Role-based Permission Isolation', function () {
    it('user with role in Company A cannot access Company B resources', function () {
        $companyB = Company::factory()->create();

        // Create same role in Company B but with different permissions
        setPermissionsTeamId($companyB->id);
        $roleB = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $roleB->givePermissionTo(['view employees']); // Only view permission

        // User from Company A
        setPermissionsTeamId($this->company->id);
        $userA = User::factory()->create(['company_id' => $this->company->id]);
        $userA->assignRole('admin');

        // User from Company B
        setPermissionsTeamId($companyB->id);
        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $userB->assignRole('admin');

        // Check permissions in their respective company contexts
        setPermissionsTeamId($this->company->id);
        expect($userA->can('delete employees'))->toBeTrue();

        setPermissionsTeamId($companyB->id);
        expect($userB->can('delete employees'))->toBeFalse(); // Company B admin doesn't have delete
    });

    it('permissions are isolated per company', function () {
        $companyB = Company::factory()->create();

        // Company A has full permissions for admin
        setPermissionsTeamId($this->company->id);
        $userA = User::factory()->create(['company_id' => $this->company->id]);
        $userA->assignRole('admin');

        // Create minimal role in Company B
        setPermissionsTeamId($companyB->id);
        $minimalRole = Role::create(['name' => 'viewer', 'guard_name' => 'web']);
        $minimalRole->givePermissionTo(['view employees']);

        $userB = User::factory()->create(['company_id' => $companyB->id]);
        $userB->assignRole('viewer');

        // Verify isolation
        setPermissionsTeamId($this->company->id);
        expect($userA->getAllPermissions()->count())->toBeGreaterThan(1);

        setPermissionsTeamId($companyB->id);
        expect($userB->getAllPermissions()->count())->toBe(1);
    });
});

describe('Permission Middleware on Routes', function () {
    it('can check permission using middleware', function () {
        // Define a test route with permission middleware
        Route::middleware(['web', 'auth', 'permission:view employees'])
            ->get('/test-permission', fn () => response('OK'));

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/test-permission');
        $response->assertOk();
    });

    it('returns 403 for user without permission', function () {
        Route::middleware(['web', 'auth', 'permission:delete employees'])
            ->get('/test-delete-permission', fn () => response('OK'));

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get('/test-delete-permission');
        $response->assertStatus(403);
    });

    it('can check role using middleware', function () {
        Route::middleware(['web', 'auth', 'role:admin'])
            ->get('/test-role', fn () => response('OK'));

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/test-role');
        $response->assertOk();
    });

    it('returns 403 for user without required role', function () {
        Route::middleware(['web', 'auth', 'role:admin'])
            ->get('/test-admin-only', fn () => response('OK'));

        $user = User::factory()->create(['company_id' => $this->company->id]);
        $user->assignRole('employee');

        $response = $this->actingAs($user)->get('/test-admin-only');
        $response->assertStatus(403);
    });

    it('can check multiple roles with OR logic', function () {
        Route::middleware(['web', 'auth', 'role:admin|hr-manager'])
            ->get('/test-multi-role', fn () => response('OK'));

        $hrUser = User::factory()->create(['company_id' => $this->company->id]);
        $hrUser->assignRole('hr-manager');

        $response = $this->actingAs($hrUser)->get('/test-multi-role');
        $response->assertOk();
    });
});
