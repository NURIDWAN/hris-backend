<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_face_recognition' => true,
    ]);

    // Set team context for role creation
    setPermissionsTeamId($this->company->id);

    // Create employee role
    Role::create(['name' => 'employee', 'guard_name' => 'web']);

    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Assign employee role
    $this->user->assignRole('employee');

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);

    $this->officeLocation = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->employee->officeLocations()->attach($this->officeLocation->id, ['is_primary' => true]);
});

describe('Face Verification in Portal Attendance', function () {
    it('shows face verification section when face recognition is enabled', function () {
        // Employee has face enrolled
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('portal.attendance.index'));

        $response->assertOk();
        $response->assertSee('Verifikasi Wajah');
    });

    it('shows enrollment prompt when face not enrolled but required', function () {
        $response = $this->actingAs($this->user)
            ->get(route('portal.attendance.index'));

        $response->assertOk();
        $response->assertSee('Wajah Belum Terdaftar');
    });

    it('hides face verification when feature is disabled', function () {
        $this->company->update([
            'enable_face_recognition' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('portal.attendance.index'));

        $response->assertOk();
        $response->assertDontSee('Verifikasi Wajah');
    });
});

describe('Face Enrollment Status API for Portal', function () {
    it('returns enrollment status for authenticated employee', function () {
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
            'enrolled_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('portal.face-recognition.status'));

        $response->assertOk();
        $response->assertJson([
            'enrolled' => true,
        ]);
    });

    it('returns not enrolled for employee without face data', function () {
        $response = $this->actingAs($this->user)
            ->getJson(route('portal.face-recognition.status'));

        $response->assertOk();
        $response->assertJson([
            'enrolled' => false,
        ]);
    });
});
