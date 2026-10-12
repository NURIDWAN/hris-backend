<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\OfficeLocation;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_gps_validation' => false, // Disable GPS for face recognition tests
        'enable_face_recognition' => true,
        'face_match_threshold' => 0.6,
        'require_liveness_detection' => true,
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

    // Create face embedding for employee
    $this->faceEmbedding = EmployeeFaceEmbedding::factory()->create([
        'employee_id' => $this->employee->id,
        'embedding_data' => [
            'model' => 'google_mlkit',
            'version' => '1.0',
            'embedding' => array_fill(0, 128, 0.5),
        ],
        'is_active' => true,
    ]);
});

describe('Attendance API - Face Recognition Integration', function () {
    describe('Clock In with Face Recognition', function () {
        it('requires face descriptors when face recognition is enabled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah diperlukan untuk absensi.',
                ]);
        });

        it('allows clock in with valid face verification', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Use the same descriptors as stored embedding (should match)
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, 0.5),
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Clock in berhasil.');

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'face_verified' => true,
            ]);

            // Check verification log was created
            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_in',
                'is_successful' => true,
            ]);
        });

        it('rejects clock in when face does not match', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Use different descriptors (should not match)
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, -0.5), // Different face
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal. Wajah tidak cocok.',
                ]);

            // Check failure log was created
            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_in',
                'is_successful' => false,
            ]);
        });

        it('rejects clock in when employee has no face enrolled', function () {
            // Delete face embedding
            $this->faceEmbedding->delete();

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, 0.5),
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah Anda terlebih dahulu.',
                ]);
        });

        it('skips face verification when company has it disabled', function () {
            $this->company->update(['enable_face_recognition' => false]);

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                // No face_descriptors needed
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'face_verified' => false,
            ]);
        });

        it('validates face descriptors array length', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 64, 0.5), // Wrong length
            ]);

            // Wrong length returns validation error (since face_descriptors is now optional with validation rules)
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['face_descriptors']);
        });

        it('includes face verification info in response', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, 0.5),
            ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'date',
                        'clock_in',
                        'status',
                        'face_verified',
                    ],
                ]);
        });
    });

    describe('Clock Out with Face Recognition', function () {
        beforeEach(function () {
            // Create attendance record (clocked in, face verified)
            $this->attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'clock_in' => now()->subHours(8),
                'status' => 'present',
                'face_verified' => true,
            ]);
        });

        it('requires face descriptors when face recognition is enabled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah diperlukan untuk absensi.',
                ]);
        });

        it('allows clock out with valid face verification', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, 0.5),
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            // Check verification log was created for clock_out
            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_out',
                'is_successful' => true,
            ]);
        });

        it('rejects clock out when face does not match', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, -0.5), // Different face
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal. Wajah tidak cocok.',
                ]);
        });
    });

    describe('Combined GPS and Face Recognition', function () {
        beforeEach(function () {
            $this->company->update([
                'enable_gps_validation' => true,
                'enable_face_recognition' => true,
            ]);

            // Create office location
            $this->officeLocation = OfficeLocation::factory()->create([
                'company_id' => $this->company->id,
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100,
                'is_active' => true,
            ]);

            $this->employee->officeLocations()->attach($this->officeLocation->id, ['is_primary' => true]);
        });

        it('validates both GPS and face when both are enabled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, 0.5),
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'office_location_id' => $this->officeLocation->id,
                'face_verified' => true,
            ]);
        });

        it('fails when GPS is valid but face does not match', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088, // Valid location
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, -0.5), // Invalid face
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal. Wajah tidak cocok.',
                ]);
        });

        it('fails when face is valid but GPS is outside radius', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -7.0000, // Far location
                'longitude' => 110.0000,
                'face_descriptors' => array_fill(0, 128, 0.5), // Valid face
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Lokasi Anda terlalu jauh dari kantor.',
                ]);
        });
    });
});
