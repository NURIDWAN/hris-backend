<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Position;
use App\Models\User;
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

    Sanctum::actingAs($this->adminUser);
});

describe('Admin Department API', function () {
    it('can list departments with pagination', function () {
        Department::create([
            'company_id' => $this->company->id,
            'name' => 'Human Resources',
            'code' => 'HR',
        ]);
        Department::create([
            'company_id' => $this->company->id,
            'name' => 'Finance',
            'code' => 'FIN',
        ]);

        $response = $this->getJson('/api/v1/admin/departments');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'employees_count'],
                ],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    it('can create a new department', function () {
        $response = $this->postJson('/api/v1/admin/departments', [
            'name' => 'Operations',
            'code' => 'OPS',
            'description' => 'Operations Team',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Operations')
            ->assertJsonPath('data.code', 'OPS');

        $this->assertDatabaseHas('departments', [
            'company_id' => $this->company->id,
            'code' => 'OPS',
        ]);
    });

    it('can show department details', function () {
        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
        ]);

        $response = $this->getJson("/api/v1/admin/departments/{$dept->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Marketing');
    });

    it('can update department', function () {
        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Marketing',
            'code' => 'MKT',
        ]);

        $response = $this->putJson("/api/v1/admin/departments/{$dept->id}", [
            'name' => 'Marketing & Growth',
            'code' => 'MKTG',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Marketing & Growth')
            ->assertJsonPath('data.code', 'MKTG');
    });

    it('can delete department without employees', function () {
        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Empty Dept',
            'code' => 'EMPTY',
        ]);

        $response = $this->deleteJson("/api/v1/admin/departments/{$dept->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('departments', ['id' => $dept->id]);
    });

    it('cannot delete department with active employees', function () {
        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Legal',
            'code' => 'LGL',
        ]);
        $pos = Position::create([
            'company_id' => $this->company->id,
            'name' => 'Legal Staff',
            'code' => 'LGL-STF',
        ]);
        Employee::create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'hire_date' => now()->toDateString(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/v1/admin/departments/{$dept->id}");

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    });
});

describe('Admin Position API', function () {
    it('can list, create, update, and delete positions', function () {
        // Create
        $response = $this->postJson('/api/v1/admin/positions', [
            'name' => 'Product Manager',
            'code' => 'PM',
            'level' => 3,
            'base_salary' => 15000000,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Product Manager');

        $posId = $response->json('data.id');

        // Show
        $showRes = $this->getJson("/api/v1/admin/positions/{$posId}");
        $showRes->assertStatus(200)->assertJsonPath('data.code', 'PM');

        // Update
        $updateRes = $this->putJson("/api/v1/admin/positions/{$posId}", [
            'name' => 'Senior Product Manager',
            'level' => 4,
        ]);
        $updateRes->assertStatus(200)->assertJsonPath('data.name', 'Senior Product Manager');

        // Delete
        $delRes = $this->deleteJson("/api/v1/admin/positions/{$posId}");
        $delRes->assertStatus(200)->assertJsonPath('success', true);
    });
});

describe('Admin Office Location API', function () {
    it('can manage office locations and assign employees', function () {
        // Create
        $response = $this->postJson('/api/v1/admin/office-locations', [
            'name' => 'Kantor Pusat Jakarta',
            'code' => 'HQ-JKT',
            'address' => 'Jl. Jenderal Sudirman No. 1',
            'city' => 'Jakarta Selatan',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 150,
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.name', 'Kantor Pusat Jakarta')
            ->assertJsonPath('data.is_headquarters', true);

        $officeId = $response->json('data.id');

        // Toggle status
        $toggleRes = $this->patchJson("/api/v1/admin/office-locations/{$officeId}/toggle-status");
        $toggleRes->assertStatus(200)->assertJsonPath('data.is_active', false);

        // Create an employee and assign
        $dept = Department::create(['company_id' => $this->company->id, 'name' => 'Tech']);
        $pos = Position::create(['company_id' => $this->company->id, 'name' => 'Dev']);
        $emp = Employee::create([
            'company_id' => $this->company->id,
            'first_name' => 'Budi',
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'hire_date' => now()->toDateString(),
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $assignRes = $this->postJson("/api/v1/admin/office-locations/{$officeId}/assign-employees", [
            'employee_ids' => [$emp->id],
            'primary_employee_id' => $emp->id,
        ]);
        $assignRes->assertStatus(200)->assertJsonPath('success', true);

        // Remove employee
        $removeRes = $this->deleteJson("/api/v1/admin/office-locations/{$officeId}/remove-employee/{$emp->id}");
        $removeRes->assertStatus(200)->assertJsonPath('success', true);
    });
});
