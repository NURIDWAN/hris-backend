<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->company = Company::factory()->create([
        'enable_face_recognition' => true,
        'face_match_threshold' => 0.6,
        'require_liveness_detection' => true,
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);
});

describe('Face Recognition API', function () {
    describe('GET /api/v1/face-recognition/status', function () {
        it('returns enrollment status for authenticated user', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->getJson('/api/v1/face-recognition/status');

            $response->assertOk()
                ->assertJsonStructure([
                    'data' => [
                        'enrolled',
                        'enrolled_at',
                        'quality_score',
                        'company_settings' => [
                            'face_recognition_enabled',
                            'liveness_required',
                            'match_threshold',
                        ],
                    ],
                ]);
        });

        it('returns enrolled true when face is enrolled', function () {
            Sanctum::actingAs($this->user, ['*']);

            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'is_active' => true,
                'enrolled_at' => now()->subDays(5),
                'quality_score' => 0.92,
            ]);

            $response = $this->getJson('/api/v1/face-recognition/status');

            $response->assertOk()
                ->assertJsonPath('data.enrolled', true)
                ->assertJsonPath('data.quality_score', 0.92);
        });

        it('returns enrolled false when no face enrolled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->getJson('/api/v1/face-recognition/status');

            $response->assertOk()
                ->assertJsonPath('data.enrolled', false);
        });

        it('returns 404 when user has no employee', function () {
            $userWithoutEmployee = User::factory()->create([
                'company_id' => $this->company->id,
            ]);
            Sanctum::actingAs($userWithoutEmployee, ['*']);

            $response = $this->getJson('/api/v1/face-recognition/status');

            $response->assertNotFound();
        });

        it('returns 401 when not authenticated', function () {
            $response = $this->getJson('/api/v1/face-recognition/status');

            $response->assertUnauthorized();
        });
    });

    describe('POST /api/v1/face-recognition/enroll', function () {
        it('enrolls face with embedding data', function () {
            Sanctum::actingAs($this->user, ['*']);

            $embeddingData = [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ];

            $response = $this->postJson('/api/v1/face-recognition/enroll', [
                'embedding_data' => $embeddingData,
                'photo' => UploadedFile::fake()->image('face.jpg', 640, 480),
                'quality_score' => 0.95,
            ]);

            $response->assertCreated()
                ->assertJsonPath('data.enrolled', true)
                ->assertJsonPath('message', 'Face enrolled successfully.');

            $this->assertDatabaseHas('employee_face_embeddings', [
                'employee_id' => $this->employee->id,
                'is_active' => true,
            ]);
        });

        it('validates required fields', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/enroll', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['embedding_data']);
        });

        it('validates embedding data structure', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/enroll', [
                'embedding_data' => ['invalid' => 'data'],
            ]);

            // When no embedding or descriptors provided, it falls back to descriptors validation
            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['embedding_data.descriptors']);
        });

        it('validates embedding array length', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/enroll', [
                'embedding_data' => [
                    'model' => 'google_mlkit',
                    'version' => '1.0',
                    'embedding' => array_fill(0, 10, 0.5), // Too short
                ],
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['embedding_data.embedding']);
        });

        it('replaces existing enrollment', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Create existing enrollment
            $oldEmbedding = EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'is_active' => true,
            ]);

            $embeddingData = [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.6),
            ];

            $response = $this->postJson('/api/v1/face-recognition/enroll', [
                'embedding_data' => $embeddingData,
            ]);

            $response->assertCreated();

            // Old embedding should be deleted
            expect(EmployeeFaceEmbedding::find($oldEmbedding->id))->toBeNull();

            // New embedding should exist
            expect($this->employee->fresh()->faceEmbedding)->not->toBeNull();
        });

        it('returns 403 when face recognition is disabled', function () {
            $this->company->update(['enable_face_recognition' => false]);
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/enroll', [
                'embedding_data' => [
                    'model' => 'google_mlkit',
                    'version' => '1.0',
                    'embedding' => array_fill(0, 128, 0.5),
                ],
            ]);

            $response->assertForbidden();
        });
    });

    describe('POST /api/v1/face-recognition/verify', function () {
        it('verifies face successfully when matched', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Create stored embedding
            $storedDescriptors = array_fill(0, 128, 0.5);
            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'embedding_data' => [
                    'model' => 'google_mlkit',
                    'version' => '1.0',
                    'embedding' => $storedDescriptors,
                ],
                'is_active' => true,
            ]);

            // Verify with similar embedding (server-side verify still uses descriptors param)
            $response = $this->postJson('/api/v1/face-recognition/verify', [
                'descriptors' => $storedDescriptors,
                'verification_type' => 'clock_in',
            ]);

            $response->assertOk()
                ->assertJsonPath('data.matched', true)
                ->assertJsonStructure([
                    'data' => [
                        'matched',
                        'confidence',
                    ],
                ]);
        });

        it('returns not matched when face is different', function () {
            Sanctum::actingAs($this->user, ['*']);

            // Create stored embedding
            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'embedding_data' => [
                    'model' => 'google_mlkit',
                    'version' => '1.0',
                    'embedding' => array_fill(0, 128, 0.5),
                ],
                'is_active' => true,
            ]);

            // Verify with different embedding
            $response = $this->postJson('/api/v1/face-recognition/verify', [
                'descriptors' => array_fill(0, 128, -0.5),
                'verification_type' => 'clock_in',
            ]);

            $response->assertOk()
                ->assertJsonPath('data.matched', false);
        });

        it('returns error when no face enrolled', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/verify', [
                'descriptors' => array_fill(0, 128, 0.5),
                'verification_type' => 'clock_in',
            ]);

            $response->assertOk()
                ->assertJsonPath('data.matched', false)
                ->assertJsonPath('data.error', 'no_face_enrolled');
        });

        it('validates required fields', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/verify', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['descriptors']);
        });

        it('validates verification type', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/face-recognition/verify', [
                'descriptors' => array_fill(0, 128, 0.5),
                'verification_type' => 'invalid_type',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['verification_type']);
        });

        it('creates verification log', function () {
            Sanctum::actingAs($this->user, ['*']);

            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'embedding_data' => [
                    'model' => 'google_mlkit',
                    'version' => '1.0',
                    'embedding' => array_fill(0, 128, 0.5),
                ],
                'is_active' => true,
            ]);

            $this->postJson('/api/v1/face-recognition/verify', [
                'descriptors' => array_fill(0, 128, 0.5),
                'verification_type' => 'clock_in',
            ]);

            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_in',
            ]);
        });
    });

    describe('DELETE /api/v1/face-recognition/enrollment', function () {
        it('is not available via API - employee must contact HR to reset face', function () {
            Sanctum::actingAs($this->user, ['*']);

            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'is_active' => true,
            ]);

            $response = $this->deleteJson('/api/v1/face-recognition/enrollment');

            $response->assertNotFound();

            expect($this->employee->fresh()->hasFaceEnrolled())->toBeTrue();
        });
    });
});
