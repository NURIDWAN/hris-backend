<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_gps_validation' => true,
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);

    // Create multiple office locations
    $this->headquarters = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Headquarters',
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'radius' => 100,
        'is_active' => true,
        'is_headquarters' => true,
    ]);

    $this->branchOffice = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Branch Office',
        'latitude' => -6.9175,
        'longitude' => 107.6191,
        'radius' => 150,
        'is_active' => true,
        'is_headquarters' => false,
    ]);

    // Assign employee to both offices
    $this->employee->officeLocations()->attach([
        $this->headquarters->id => ['is_primary' => true],
        $this->branchOffice->id => ['is_primary' => false],
    ]);
});

describe('Attendance API - Multi Office Location GPS Validation', function () {
    describe('Clock In with Multi-location GPS', function () {
        it('allows clock in from headquarters', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Location near headquarters (within 100m)
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Clock in berhasil.');

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->headquarters->id,
            ]);
        });

        it('allows clock in from branch office', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Location near branch office (within 150m)
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.9175,
                'longitude' => 107.6191,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->branchOffice->id,
            ]);
        });

        it('rejects clock in when outside all assigned office radii', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Random location far from any office
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -7.5000,
                'longitude' => 110.0000,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Lokasi Anda terlalu jauh dari kantor.');
        });

        it('rejects clock in when no office locations assigned', function () {
            // Remove all office assignments
            $this->employee->officeLocations()->detach();

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Tidak ada lokasi kantor yang ditugaskan.');
        });

        it('rejects clock in when all assigned offices are inactive', function () {
            // Deactivate all offices
            $this->headquarters->update(['is_active' => false]);
            $this->branchOffice->update(['is_active' => false]);

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Tidak ada lokasi kantor aktif yang ditugaskan.');
        });

        it('picks the closest office when within multiple radii', function () {
            // Create overlapping offices
            $nearbyOffice = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'Nearby Office',
                'latitude' => -6.2090, // Very close to HQ
                'longitude' => 106.8458,
                'radius' => 200, // Larger radius
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($nearbyOffice->id, ['is_primary' => false]);

            Sanctum::actingAs($this->user, ['*']);

            // Location exactly at headquarters
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertOk();

            // Should pick the closest one (headquarters)
            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->headquarters->id,
            ]);
        });

        it('skips GPS validation when company has it disabled', function () {
            $this->company->update(['enable_gps_validation' => false]);

            Sanctum::actingAs($this->user, ['*']);

            // Very far location
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => 0.0,
                'longitude' => 0.0,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);
        });

        it('returns matched office info in response', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'date',
                        'clock_in',
                        'status',
                        'office_location' => [
                            'id',
                            'name',
                        ],
                    ],
                ]);
        });
    });

    describe('Clock Out with Multi-location GPS', function () {
        beforeEach(function () {
            // Create existing attendance record (clocked in only, no clock out)
            $this->attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->headquarters->id,
                'date' => today(),
                'clock_in' => now()->subHours(8),
                'status' => 'present',
            ]);
        });

        it('allows clock out from any assigned office', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Clock out from branch office (different from clock in)
            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.9175,
                'longitude' => 107.6191,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('attendances', [
                'id' => $this->attendance->id,
                'clock_out_office_location_id' => $this->branchOffice->id,
            ]);
        });

        it('rejects clock out when outside all office radii', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -7.5000,
                'longitude' => 110.0000,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false)
                ->assertJsonPath('message', 'Lokasi Anda terlalu jauh dari kantor.');
        });
    });

    describe('Today endpoint with office location', function () {
        it('includes office location info in today response', function () {
            Sanctum::actingAs($this->user, ['*']);

            Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->headquarters->id,
                'date' => today(),
                'clock_in' => now()->subHours(2),
                'status' => 'present',
            ]);

            $response = $this->getJson('/api/v1/attendance/today');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'office_location' => [
                            'id',
                            'name',
                        ],
                    ],
                ]);
        });
    });
});
