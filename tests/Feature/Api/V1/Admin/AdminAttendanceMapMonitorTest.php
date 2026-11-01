<?php

use App\Models\Attendance;
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
        'enable_gps_validation' => true,
    ]);

    $this->adminUser = User::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'admin_map@testgajipro.com',
        'password' => Hash::make('password123'),
        'is_active' => true,
    ]);
    setPermissionsTeamId($this->company->id);
    $this->adminUser->assignRole('admin');

    $this->dept = Department::create(['company_id' => $this->company->id, 'name' => 'IT & Engineering']);
    $this->pos = Position::create(['company_id' => $this->company->id, 'name' => 'Developer']);
    $this->schedule = WorkSchedule::create([
        'company_id' => $this->company->id,
        'name' => 'Shift Normal',
        'start_time' => '08:00',
        'end_time' => '17:00',
        'late_tolerance' => 15,
        'is_default' => true,
        'is_active' => true,
    ]);

    // Create 2 office locations (Headquarters in Jakarta & Branch in Surabaya)
    $this->officeJakarta = OfficeLocation::create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Pusat Jakarta',
        'code' => 'JKT-HQ',
        'address' => 'Jl. Jendral Sudirman No. 1',
        'city' => 'Jakarta Pusat',
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
        'is_headquarters' => true,
        'is_active' => true,
    ]);

    $this->officeSurabaya = OfficeLocation::create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Cabang Surabaya',
        'code' => 'SBY-01',
        'address' => 'Jl. Pemuda No. 45',
        'city' => 'Surabaya',
        'latitude' => -7.2575,
        'longitude' => 112.7521,
        'radius' => 150,
        'is_headquarters' => false,
        'is_active' => true,
    ]);

    // Create 2 employees
    $this->empJakarta = Employee::create([
        'company_id' => $this->company->id,
        'first_name' => 'Andi',
        'last_name' => 'Pratama',
        'employee_id' => 'EMP-JKT-01',
        'department_id' => $this->dept->id,
        'position_id' => $this->pos->id,
        'work_schedule_id' => $this->schedule->id,
        'hire_date' => '2026-01-01',
        'employment_status' => 'permanent',
        'is_active' => true,
    ]);
    $this->officeJakarta->employees()->attach($this->empJakarta->id, ['is_primary' => true]);

    $this->empSurabaya = Employee::create([
        'company_id' => $this->company->id,
        'first_name' => 'Budi',
        'last_name' => 'Santoso',
        'employee_id' => 'EMP-SBY-01',
        'department_id' => $this->dept->id,
        'position_id' => $this->pos->id,
        'work_schedule_id' => $this->schedule->id,
        'hire_date' => '2026-01-01',
        'employment_status' => 'permanent',
        'is_active' => true,
    ]);
    $this->officeSurabaya->employees()->attach($this->empSurabaya->id, ['is_primary' => true]);

    Sanctum::actingAs($this->adminUser);
});

describe('Admin Attendance Map Monitor API', function () {
    it('returns all active office locations and multi-branch attendance map data', function () {
        $today = now()->format('Y-m-d');

        // Andi clocks in in Jakarta within radius
        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->empJakarta->id,
            'office_location_id' => $this->officeJakarta->id,
            'work_schedule_id' => $this->schedule->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 5, 0),
            'clock_in_latitude' => -6.20885, // Very close to Jakarta office (~6m)
            'clock_in_longitude' => 106.84565,
            'clock_in_status' => 'on_time',
            'status' => 'present',
            'late_minutes' => 0,
        ]);

        // Budi clocks in in Surabaya outside radius
        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->empSurabaya->id,
            'office_location_id' => $this->officeSurabaya->id,
            'work_schedule_id' => $this->schedule->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 25, 0),
            'clock_in_latitude' => -7.2650, // ~1km away from Surabaya office
            'clock_in_longitude' => 112.7550,
            'clock_in_status' => 'late',
            'status' => 'late',
            'late_minutes' => 10,
        ]);

        $response = $this->getJson(route('api.v1.admin.attendances.map-monitor', ['date' => $today]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.total_offices', 2)
            ->assertJsonPath('data.summary.total_present', 2)
            ->assertJsonPath('data.summary.total_on_time', 1)
            ->assertJsonPath('data.summary.total_late', 1)
            ->assertJsonPath('data.summary.total_outside_radius', 1);

        $data = $response->json('data');
        expect($data['offices'])->toHaveCount(2);
        expect($data['attendances'])->toHaveCount(2);

        // Verify Andi's record has distance and is_within_radius true
        $andiAtt = collect($data['attendances'])->firstWhere('employee_code', 'EMP-JKT-01');
        expect($andiAtt)->not->toBeNull();
        expect($andiAtt['is_within_radius'])->toBeTrue();
        expect($andiAtt['clock_in_status'])->toBe('on_time');

        // Verify Budi's record is marked outside radius
        $budiAtt = collect($data['attendances'])->firstWhere('employee_code', 'EMP-SBY-01');
        expect($budiAtt)->not->toBeNull();
        expect($budiAtt['is_within_radius'])->toBeFalse();
        expect($budiAtt['clock_in_status'])->toBe('late');
    });

    it('can filter attendances by specific branch office', function () {
        $today = now()->format('Y-m-d');

        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->empJakarta->id,
            'office_location_id' => $this->officeJakarta->id,
            'work_schedule_id' => $this->schedule->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 0, 0),
            'clock_in_latitude' => -6.2088,
            'clock_in_longitude' => 106.8456,
            'clock_in_status' => 'on_time',
            'status' => 'present',
        ]);

        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->empSurabaya->id,
            'office_location_id' => $this->officeSurabaya->id,
            'work_schedule_id' => $this->schedule->id,
            'date' => $today,
            'clock_in' => now()->setTime(8, 0, 0),
            'clock_in_latitude' => -7.2575,
            'clock_in_longitude' => 112.7521,
            'clock_in_status' => 'on_time',
            'status' => 'present',
        ]);

        $response = $this->getJson(route('api.v1.admin.attendances.map-monitor', [
            'date' => $today,
            'office_location_id' => $this->officeSurabaya->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('success', true);

        $attendances = $response->json('data.attendances');
        expect($attendances)->toHaveCount(1);
        expect($attendances[0]['employee_code'])->toBe('EMP-SBY-01');
    });
});
