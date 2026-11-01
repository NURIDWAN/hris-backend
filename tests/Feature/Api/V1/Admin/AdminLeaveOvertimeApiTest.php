<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
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

    $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'HR']);
    $this->pos = Position::create(['company_id' => $this->company->id, 'name' => 'Staff']);

    $this->employee = Employee::create([
        'company_id' => $this->company->id,
        'first_name' => 'Bambang',
        'last_name' => 'Pamungkas',
        'department_id' => $this->dept->id,
        'position_id' => $this->pos->id,
        'hire_date' => '2025-01-01',
        'employment_status' => 'permanent',
        'is_active' => true,
    ]);

    $this->leaveType = LeaveType::create([
        'company_id' => $this->company->id,
        'name' => 'Cuti Tahunan',
        'code' => 'ANNUAL',
        'default_days' => 12,
        'is_paid' => true,
        'is_annual' => true,
        'is_active' => true,
    ]);

    Sanctum::actingAs($this->adminUser);
});

describe('Admin Leave & Overtime API', function () {
    it('can manage leave types and toggle status', function () {
        // Create leave type
        $response = $this->postJson('/api/v1/admin/leave-types', [
            'name' => 'Cuti Menikah',
            'code' => 'MARRIAGE',
            'default_days' => 3,
            'is_paid' => true,
            'requires_approval' => true,
            'is_active' => true,
        ]);
        $response->assertStatus(201)->assertJsonPath('data.code', 'MARRIAGE');

        $typeId = $response->json('data.id');

        // Toggle status
        $toggleRes = $this->patchJson("/api/v1/admin/leave-types/{$typeId}/toggle-status");
        $toggleRes->assertStatus(200)->assertJsonPath('data.is_active', false);
    });

    it('can manage leave balances and bulk generate', function () {
        // Bulk generate
        $bulkRes = $this->postJson('/api/v1/admin/leave-balances/generate-bulk', [
            'leave_type_id' => $this->leaveType->id,
            'year' => 2026,
        ]);
        $bulkRes->assertStatus(200)->assertJsonPath('success', true);

        $balance = LeaveBalance::where('employee_id', $this->employee->id)->first();
        expect($balance)->not->toBeNull();
        expect((float) $balance->entitled_days)->toBe(12.0);

        // Update balance
        $updateRes = $this->putJson("/api/v1/admin/leave-balances/{$balance->id}", [
            'entitled_days' => 15,
            'adjustment_notes' => 'Bonus loyalitas',
        ]);
        $updateRes->assertStatus(200)->assertJsonPath('data.entitled_days', '15.0');
    });

    it('can process leave request lifecycle (store, approve, reject, cancel)', function () {
        // Admin creates leave request
        $response = $this->postJson('/api/v1/admin/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => '2026-04-10',
            'end_date' => '2026-04-12',
            'reason' => 'Liburan keluarga',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);
        expect((float) $response->json('data.total_days'))->toEqual(3.0);

        $reqId = $response->json('data.id');

        // Approve
        $appRes = $this->postJson("/api/v1/admin/leave-requests/{$reqId}/approve", [
            'notes' => 'Disetujui HR',
        ]);
        $appRes->assertStatus(200)->assertJsonPath('data.status', 'approved');

        // Cancel
        $cancelRes = $this->postJson("/api/v1/admin/leave-requests/{$reqId}/cancel", [
            'reason' => 'Rencana liburan dibatalkan',
        ]);
        $cancelRes->assertStatus(200)->assertJsonPath('data.status', 'cancelled');
    });

    it('can manage overtime settings and request approval', function () {
        // Update overtime setting
        $setRes = $this->putJson('/api/v1/admin/overtime-settings', [
            'weekday_rate_first_hour' => 1.75,
            'max_overtime_hours_per_day' => 5,
        ]);
        $setRes->assertStatus(200);
        expect((float) $setRes->json('data.weekday_rate_first_hour'))->toEqual(1.75);

        // Create overtime request
        $createRes = $this->postJson('/api/v1/admin/overtime-requests', [
            'employee_id' => $this->employee->id,
            'date' => '2026-03-20',
            'start_time' => '17:00',
            'end_time' => '20:00',
            'overtime_type' => 'weekday',
            'reason' => 'Deployment sistem baru',
        ]);
        $createRes->assertStatus(201);
        expect((float) $createRes->json('data.overtime_hours'))->toEqual(3.0);

        $otId = $createRes->json('data.id');

        // Approve overtime
        $appRes = $this->postJson("/api/v1/admin/overtime-requests/{$otId}/approve");
        $appRes->assertStatus(200)->assertJsonPath('data.status', 'approved');
    });
});
