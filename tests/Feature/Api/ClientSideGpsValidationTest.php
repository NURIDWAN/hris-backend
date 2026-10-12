<?php

/**
 * Tests for Client-Side GPS Validation Flow.
 *
 * Flow:
 * 1. Login/profile returns assigned_offices with lat/lng/radius
 * 2. Mobile app validates GPS locally using assigned_offices
 * 3. Clock in/out sends gps_verified=true + office_location_id
 * 4. Server trusts client validation and stores office_location_id
 */

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_gps_validation' => true,
        'enable_face_recognition' => false,
    ]);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);

    $this->officeA = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Pusat',
        'code' => 'HQ-001',
        'latitude' => -6.200000,
        'longitude' => 106.816666,
        'radius' => 100,
        'is_active' => true,
    ]);

    $this->officeB = OfficeLocation::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Kantor Cabang',
        'code' => 'BR-001',
        'latitude' => -6.917464,
        'longitude' => 107.619123,
        'radius' => 150,
        'is_active' => true,
    ]);

    // Assign offices to employee
    $this->employee->officeLocations()->attach([
        $this->officeA->id => ['is_primary' => true],
        $this->officeB->id => ['is_primary' => false],
    ]);
});

describe('Login Response with Assigned Offices', function () {
    it('returns assigned_offices in login response', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'employee' => [
                        'assigned_offices' => [
                            '*' => [
                                'id',
                                'name',
                                'code',
                                'latitude',
                                'longitude',
                                'radius',
                                'is_primary',
                            ],
                        ],
                    ],
                    'company' => [
                        'enable_gps_validation',
                    ],
                ],
            ]);

        $offices = $response->json('data.employee.assigned_offices');
        expect($offices)->toHaveCount(2);

        // Check primary office is first or marked correctly
        $primaryOffice = collect($offices)->firstWhere('is_primary', true);
        expect($primaryOffice)->not->toBeNull();
        expect($primaryOffice['id'])->toBe($this->officeA->id);
        expect($primaryOffice['latitude'])->toBe(-6.200000);
        expect($primaryOffice['longitude'])->toBe(106.816666);
        expect($primaryOffice['radius'])->toBe(100);
    });

    it('returns enable_gps_validation in company settings', function () {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.company.enable_gps_validation', true);
    });

    it('returns empty assigned_offices when employee has none', function () {
        $this->employee->officeLocations()->detach();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.employee.assigned_offices', []);
    });
});

describe('Profile Response with Assigned Offices', function () {
    it('returns assigned_offices in profile response', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->getJson('/api/v1/auth/profile');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'employee' => [
                        'assigned_offices' => [
                            '*' => [
                                'id',
                                'name',
                                'code',
                                'latitude',
                                'longitude',
                                'radius',
                                'is_primary',
                            ],
                        ],
                    ],
                    'company' => [
                        'enable_gps_validation',
                    ],
                ],
            ]);

        $offices = $response->json('data.employee.assigned_offices');
        expect($offices)->toHaveCount(2);
    });
});

describe('Clock In with Client-Side GPS Validation', function () {
    it('accepts gps_verified=true with office_location_id', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => true,
            'office_location_id' => $this->officeA->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Clock in berhasil.')
            ->assertJsonPath('data.office_location.id', $this->officeA->id)
            ->assertJsonPath('data.office_location.name', 'Kantor Pusat');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'office_location_id' => $this->officeA->id,
        ]);
    });

    it('accepts clock in at secondary office', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'gps_verified' => true,
            'office_location_id' => $this->officeB->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.office_location.id', $this->officeB->id);
    });

    it('rejects clock in when gps_verified is false', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => false,
            'office_location_id' => $this->officeA->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validasi GPS gagal.');
    });

    it('rejects clock in when office_location_id is missing with gps_verified', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => true,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Office location ID diperlukan untuk validasi GPS client-side.');
    });

    it('rejects clock in when office not assigned to employee', function () {
        Sanctum::actingAs($this->user, ['*']);

        $otherOffice = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => true,
            'office_location_id' => $otherOffice->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Office location tidak di-assign ke karyawan ini.');
    });

    it('falls back to server-side validation when gps_verified not provided', function () {
        Sanctum::actingAs($this->user, ['*']);

        // Clock in at valid office location (within radius)
        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => $this->officeA->latitude,
            'longitude' => $this->officeA->longitude,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.office_location.id', $this->officeA->id);
    });
});

describe('Clock Out with Client-Side GPS Validation', function () {
    beforeEach(function () {
        // Create clock in record first
        Attendance::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'office_location_id' => $this->officeA->id,
            'date' => today(),
            'clock_in' => now()->subHours(8),
            'clock_in_latitude' => -6.200000,
            'clock_in_longitude' => 106.816666,
            'status' => 'present',
        ]);
    });

    it('accepts gps_verified=true with office_location_id for clock out', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => true,
            'office_location_id' => $this->officeA->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Clock out berhasil.');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'clock_out_office_location_id' => $this->officeA->id,
        ]);
    });

    it('allows clock out at different office than clock in', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.917464,
            'longitude' => 107.619123,
            'gps_verified' => true,
            'office_location_id' => $this->officeB->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'clock_out_office_location_id' => $this->officeB->id,
        ]);
    });

    it('rejects clock out when gps_verified is false', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => false,
            'office_location_id' => $this->officeA->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validasi GPS gagal.');
    });

    it('rejects clock out when office not assigned to employee', function () {
        Sanctum::actingAs($this->user, ['*']);

        $otherOffice = OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => true,
            'office_location_id' => $otherOffice->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Office location tidak di-assign ke karyawan ini.');
    });
});

describe('Combined Client-Side Face and GPS Validation', function () {
    beforeEach(function () {
        $this->company->update([
            'enable_face_recognition' => true,
            'face_match_threshold' => 0.6,
        ]);

        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);
    });

    it('accepts both face_verified and gps_verified for clock in', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'gps_verified' => true,
            'office_location_id' => $this->officeA->id,
            'face_verified' => true,
            'face_confidence' => 0.92,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.face_verified', true)
            ->assertJsonPath('data.office_location.id', $this->officeA->id);
    });
});

describe('GPS Validation Disabled', function () {
    beforeEach(function () {
        $this->company->update(['enable_gps_validation' => false]);
    });

    it('skips GPS validation when disabled', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => 0,
            'longitude' => 0,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    });

    it('does not require office_location_id when GPS validation disabled', function () {
        Sanctum::actingAs($this->user, ['*']);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $this->employee->id,
            'office_location_id' => null,
        ]);
    });
});
