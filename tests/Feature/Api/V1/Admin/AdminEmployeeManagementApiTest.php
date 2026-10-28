<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);

    $this->company = Company::factory()->create([
        'enable_face_recognition' => false,
        'enable_gps_validation' => false,
    ]);

    $this->adminUser = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'admin@testgajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    setPermissionsTeamId($this->company->id);
    $this->adminUser->assignRole('admin');

    $this->department = Department::create([
        'company_id' => $this->company->id,
        'name' => 'Technology',
        'code' => 'TECH',
    ]);

    $this->position = Position::create([
        'company_id' => $this->company->id,
        'name' => 'Backend Engineer',
        'code' => 'BE',
        'level' => 2,
    ]);

    $this->schedule = WorkSchedule::create([
        'company_id' => $this->company->id,
        'name' => 'Reguler 08:00 - 17:00',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->office = OfficeLocation::create([
        'company_id' => $this->company->id,
        'name' => 'HQ Jakarta',
        'code' => 'HQ-JKT',
        'address' => 'Sudirman Central',
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 100,
        'is_headquarters' => true,
        'is_active' => true,
    ]);

    Sanctum::actingAs($this->adminUser);
});

describe('Admin Employee Management API', function () {
    it('can create a new employee with user account and office location', function () {
        $response = $this->postJson('/api/v1/admin/employees', [
            'first_name' => 'Sarah',
            'last_name' => 'Connor',
            'email' => 'sarah.connor@testgajipro.com',
            'phone' => '081234567890',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-01-15',
            'employment_status' => 'permanent',
            'work_schedule_id' => $this->schedule->id,
            'gender' => 'female',
            'base_salary' => 12000000,
            'password' => 'secretPassword123',
            'office_location_ids' => [$this->office->id],
            'primary_office_id' => $this->office->id,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Sarah')
            ->assertJsonPath('data.email', 'sarah.connor@testgajipro.com');

        $empId = $response->json('data.id');

        $this->assertDatabaseHas('employees', [
            'id' => $empId,
            'company_id' => $this->company->id,
            'first_name' => 'Sarah',
        ]);

        $this->assertDatabaseHas('users', [
            'company_id' => $this->company->id,
            'email' => 'sarah.connor@testgajipro.com',
        ]);

        $createdUser = User::where('email', 'sarah.connor@testgajipro.com')->first();
        setPermissionsTeamId($this->company->id);
        expect($createdUser->hasRole('employee'))->toBeTrue();
    });

    it('can update employee details', function () {
        $emp = Employee::create([
            'company_id' => $this->company->id,
            'first_name' => 'Alex',
            'last_name' => 'Murphy',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-02-01',
            'employment_status' => 'contract',
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/v1/admin/employees/{$emp->id}", [
            'first_name' => 'Alexander',
            'employment_status' => 'permanent',
            'base_salary' => 14000000,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.first_name', 'Alexander')
            ->assertJsonPath('data.employment_status', 'permanent');

        $this->assertDatabaseHas('employees', [
            'id' => $emp->id,
            'first_name' => 'Alexander',
            'employment_status' => 'permanent',
        ]);
    });

    it('can reset password for employee with user account', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('oldPassword123'),
        ]);

        $emp = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
            'first_name' => 'Kyle',
            'last_name' => 'Reese',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-02-01',
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/v1/admin/employees/{$emp->id}/reset-password", [
            'password' => 'newPassword456!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $user->refresh();
        expect(Hash::check('newPassword456!', $user->password))->toBeTrue();
    });

    it('can soft-delete employee', function () {
        $emp = Employee::create([
            'company_id' => $this->company->id,
            'first_name' => 'Test',
            'last_name' => 'Delete',
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'hire_date' => '2026-02-01',
            'employment_status' => 'contract',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/v1/admin/employees/{$emp->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('employees', ['id' => $emp->id]);
    });
});
