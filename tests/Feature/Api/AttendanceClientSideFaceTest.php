<?php

/**
 * Tests for attendance with client-side face verification flow.
 *
 * Flow:
 * 1. Login returns face_embedding (stored locally on device)
 * 2. Client matches face locally before sending attendance request
 * 3. Server trusts client verification (no server-side face matching)
 * 4. Server only logs the verification for audit purposes
 */

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_gps_validation' => false,
        'enable_face_recognition' => true,
        'face_match_threshold' => 0.6,
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

describe('Client-Side Face Verification Flow', function () {
    describe('Clock In with face_verified flag (client verified)', function () {
        it('accepts clock in with face_verified=true without server matching', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Client sends face_verified=true (already matched locally)
            // No face_descriptors needed - client already verified
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_verified' => true,
                'face_confidence' => 0.85,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Clock in berhasil.');

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'face_verified' => true,
                'face_confidence' => 0.85,
            ]);
        });

        it('rejects clock in without face_verified when face recognition enabled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                // No face_verified flag
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah diperlukan untuk absensi.',
                ]);
        });

        it('rejects clock in when face_verified=false', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_verified' => false,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Verifikasi wajah gagal.',
                ]);
        });

        it('requires employee to have face enrolled before clock in', function () {
            // Delete face embedding
            $this->faceEmbedding->delete();

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_verified' => true,
            ]);

            $response->assertStatus(422)
                ->assertJson([
                    'success' => false,
                    'message' => 'Wajah belum terdaftar. Silakan daftarkan wajah Anda terlebih dahulu.',
                ]);
        });

        it('allows clock in without face verification when disabled', function () {
            $this->company->update(['enable_face_recognition' => false]);

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                // No face_verified needed
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            $this->assertDatabaseHas('attendances', [
                'employee_id' => $this->employee->id,
                'face_verified' => false,
            ]);
        });

        it('logs face verification for audit purposes', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_verified' => true,
                'face_confidence' => 0.92,
            ]);

            $response->assertOk();

            // Check audit log was created
            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_in',
                'is_successful' => true,
                'confidence_score' => 0.92,
            ]);
        });
    });

    describe('Clock Out with face_verified flag (client verified)', function () {
        beforeEach(function () {
            // Create attendance record (clocked in)
            $this->attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'clock_in' => now()->subHours(8),
                'status' => 'present',
                'face_verified' => true,
            ]);
        });

        it('accepts clock out with face_verified=true', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_verified' => true,
                'face_confidence' => 0.88,
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);

            // Check audit log for clock_out
            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_out',
                'is_successful' => true,
            ]);
        });

        it('rejects clock out without face_verified when enabled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422);
        });
    });

    describe('Backward Compatibility - Server-side verification still works', function () {
        it('still accepts face_descriptors for server-side verification', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Old flow: send face_descriptors for server matching
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_descriptors' => array_fill(0, 128, 0.5),
            ]);

            $response->assertOk()
                ->assertJsonPath('success', true);
        });

        it('prefers face_verified over face_descriptors when both provided', function () {
            Sanctum::actingAs($this->user, ['*']);

            // When both are provided, use face_verified (client-side)
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'face_verified' => true,
                'face_confidence' => 0.95,
                'face_descriptors' => array_fill(0, 128, -0.5), // Would fail server match
            ]);

            $response->assertOk(); // Passes because face_verified=true is used
        });
    });
});
