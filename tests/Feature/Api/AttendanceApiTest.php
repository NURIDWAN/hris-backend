<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->company = Company::factory()->create();
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Regular',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);
});

describe('GET /api/v1/attendance/today', function () {
    it('returns today attendance when exists', function () {
        Sanctum::actingAs($this->user);

        // Use company timezone for clock_in time to ensure it's before schedule start (08:00)
        $clockInTime = $this->company->now()->setTime(7, 55, 0); // 07:55 - before 08:00 schedule

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => $this->company->today(),
            'clock_in' => $this->company->toUtc($clockInTime),
            'status' => 'present',
            'late_minutes' => 0,
        ]);

        $response = $this->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'date',
                    'clock_in',
                    'clock_out',
                    'status',
                    'status_label',
                    'late_minutes',
                    'working_minutes',
                    'schedule' => [
                        'start_time',
                        'end_time',
                    ],
                ],
            ]);

        expect($response->json('data.status'))->toBe('present');
    });

    it('returns null when no attendance today', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => null,
            ]);
    });

    it('returns schedule info even when no attendance', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'schedule' => [
                    'name',
                    'start_time',
                    'end_time',
                ],
            ]);
    });

    it('recalculates late_minutes when attendance has 0 but clock_in is after schedule', function () {
        Sanctum::actingAs($this->user);

        // Create attendance with clock_in at 10:30 (2.5 hours late) but late_minutes = 0
        $clockInTime = $this->company->now()->setTime(10, 30, 0);

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => $this->company->today(),
            'clock_in' => $this->company->toUtc($clockInTime),
            'status' => 'present', // Wrong status
            'late_minutes' => 0, // Wrong value - should be recalculated
        ]);

        $response = $this->getJson('/api/v1/attendance/today');

        $response->assertOk();

        // Should recalculate late_minutes (10:30 - 08:00 = 150 minutes)
        expect($response->json('data.late_minutes'))->toBe(150);
        expect($response->json('data.status'))->toBe('late');

        // Verify database was updated
        $attendance->refresh();
        expect($attendance->late_minutes)->toBe(150);
        expect($attendance->status)->toBe('late');
    });
});

describe('POST /api/v1/attendance/clock-in', function () {
    it('can clock in with GPS and photo', function () {
        Sanctum::actingAs($this->user);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'photo' => $photo,
            'notes' => 'Clock in from office',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'date',
                    'clock_in',
                    'status',
                    'late_minutes',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();

        // Verify attendance created
        $attendance = Attendance::where('employee_id', $this->employee->id)
            ->whereDate('date', today())
            ->first();

        expect($attendance)->not->toBeNull();
        expect((float) $attendance->clock_in_latitude)->toBe(-6.2);
        expect((float) $attendance->clock_in_longitude)->toBe(106.816666);
    });

    it('returns error when already clocked in', function () {
        Sanctum::actingAs($this->user);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'clock_in' => now()->setTime(8, 0),
        ]);

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Anda sudah melakukan clock in hari ini.',
            ]);
    });

    it('validates required GPS coordinates', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/attendance/clock-in', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude']);
    });

    it('detects fake GPS based on company settings', function () {
        Sanctum::actingAs($this->user);

        // Enable GPS validation
        $this->company->update([
            'enable_gps_validation' => true,
        ]);

        // Create office location and assign to employee
        $officeLocation = \App\Models\OfficeLocation::factory()->create([
            'company_id' => $this->company->id,
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'radius' => 100, // 100 meters
            'is_active' => true,
        ]);

        $this->employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

        // Try to clock in from far away location
        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.300000, // Far from office
            'longitude' => 106.900000,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Lokasi Anda terlalu jauh dari kantor.',
            ]);
    });

    it('calculates late status correctly', function () {
        Sanctum::actingAs($this->user);

        // Disable face recognition for this test
        $this->company->update(['enable_face_recognition' => false]);

        // Set time to 9:30 AM (1.5 hours after schedule start at 8:00 AM)
        $this->travelTo(today()->setTime(9, 30));

        $response = $this->postJson('/api/v1/attendance/clock-in', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertOk();
        expect($response->json('data.status'))->toBe('late');
        expect($response->json('data.late_minutes'))->toBeGreaterThan(0);
    });
});

describe('POST /api/v1/attendance/clock-out', function () {
    it('can clock out with GPS and photo', function () {
        Sanctum::actingAs($this->user);

        // Create clock in first (without clock out)
        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'clock_in' => now()->subHours(8),
            'status' => 'present',
        ]);

        $photo = UploadedFile::fake()->image('selfie.jpg');

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
            'photo' => $photo,
            'notes' => 'Clock out from office',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'date',
                    'clock_in',
                    'clock_out',
                    'working_minutes',
                    'overtime_minutes',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();

        // Verify attendance updated
        $attendance->refresh();
        expect((float) $attendance->clock_out_latitude)->toBe(-6.2);
        expect((float) $attendance->clock_out_longitude)->toBe(106.816666);
    });

    it('returns error when not clocked in', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Anda belum melakukan clock in hari ini.',
            ]);
    });

    it('returns error when already clocked out', function () {
        Sanctum::actingAs($this->user);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'status' => 'present',
        ]);

        $response = $this->postJson('/api/v1/attendance/clock-out', [
            'latitude' => -6.200000,
            'longitude' => 106.816666,
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Anda sudah melakukan clock out hari ini.',
            ]);
    });
});

describe('GET /api/v1/attendance/history', function () {
    it('returns attendance history with pagination', function () {
        Sanctum::actingAs($this->user);

        // Create multiple attendance records
        Attendance::factory()->count(15)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/attendance/history');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'clock_in',
                        'clock_out',
                        'status',
                        'status_label',
                        'working_hours',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    });

    it('can filter by date range', function () {
        Sanctum::actingAs($this->user);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subDays(5),
        ]);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subDays(15),
        ]);

        $response = $this->getJson('/api/v1/attendance/history?'.http_build_query([
            'start_date' => today()->subDays(10)->toDateString(),
            'end_date' => today()->toDateString(),
        ]));

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('can filter by month', function () {
        Sanctum::actingAs($this->user);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subMonth(),
        ]);

        $response = $this->getJson('/api/v1/attendance/history?'.http_build_query([
            'month' => today()->month,
            'year' => today()->year,
        ]));

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });
});

describe('GET /api/v1/attendance/summary', function () {
    it('returns monthly attendance summary', function () {
        Sanctum::actingAs($this->user);

        // Create various attendance records
        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'status' => 'present',
            'working_minutes' => 480,
        ]);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subDay(),
            'status' => 'late',
            'late_minutes' => 30,
            'working_minutes' => 450,
        ]);

        $response = $this->getJson('/api/v1/attendance/summary?'.http_build_query([
            'month' => today()->month,
            'year' => today()->year,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_working_days',
                    'total_present',
                    'total_absent',
                    'total_late',
                    'total_leave',
                    'total_working_hours',
                    'total_overtime_hours',
                    'total_late_minutes',
                ],
            ]);

        expect($response->json('data.total_present'))->toBeGreaterThanOrEqual(1);
    });
});
