<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
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

    $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'Support']);
    $this->pos = Position::create(['company_id' => $this->company->id, 'name' => 'Agent']);
    $this->schedule = WorkSchedule::create([
        'company_id' => $this->company->id,
        'name' => 'Shift Normal',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'late_tolerance' => 15,
        'is_default' => true,
        'is_active' => true,
    ]);

    $this->employee = Employee::create([
        'company_id' => $this->company->id,
        'first_name' => 'Rian',
        'last_name' => 'Dwi',
        'department_id' => $this->dept->id,
        'position_id' => $this->pos->id,
        'work_schedule_id' => $this->schedule->id,
        'hire_date' => '2026-01-01',
        'employment_status' => 'permanent',
        'is_active' => true,
    ]);

    Sanctum::actingAs($this->adminUser);
});

describe('Admin Attendance Management API', function () {
    it('can record manual attendance and auto-calculate late minutes', function () {
        $response = $this->postJson('/api/v1/admin/attendances', [
            'employee_id' => $this->employee->id,
            'date' => '2026-03-10',
            'clock_in' => '08:25',
            'clock_out' => '17:05',
            'status' => 'late',
            'admin_notes' => 'Koreksi kehadiran manual oleh HR',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'late');

        $attId = $response->json('data.id');

        $this->assertDatabaseHas('attendances', [
            'id' => $attId,
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'is_manual_entry' => true,
        ]);

        $att = Attendance::find($attId);
        expect($att->late_minutes)->toBe(25);
    });

    it('can update attendance record', function () {
        $att = Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-03-11',
            'clock_in' => '2026-03-11 08:00:00',
            'status' => 'present',
        ]);

        $response = $this->putJson("/api/v1/admin/attendances/{$att->id}", [
            'clock_out' => '17:00',
            'admin_notes' => 'Clock out diinput manual',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $att->refresh();
        expect($att->clock_out)->not->toBeNull();
        expect($att->working_minutes)->toBeGreaterThan(0);
    });

    it('can retrieve attendance report summary', function () {
        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-03-01',
            'status' => 'present',
        ]);
        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-03-02',
            'status' => 'late',
        ]);

        $response = $this->getJson('/api/v1/admin/attendances/report?month=2026-03');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.present', 1)
            ->assertJsonPath('data.summary.late', 1);
    });

    it('can delete attendance record', function () {
        $att = Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-03-12',
            'status' => 'absent',
        ]);

        $response = $this->deleteJson("/api/v1/admin/attendances/{$att->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('attendances', ['id' => $att->id]);
    });
});
