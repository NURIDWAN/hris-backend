<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->company = Company::factory()->create([
        'enable_face_recognition' => true,
        'face_match_threshold' => 0.6,
        'require_liveness_detection' => true,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->service = new FaceRecognitionService;
});

describe('FaceRecognitionService', function () {
    describe('enrollFace', function () {
        it('creates face embedding for employee', function () {
            $embeddingData = [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ];

            $result = $this->service->enrollFace(
                $this->employee,
                $embeddingData,
                'photos/test.jpg',
                $this->admin
            );

            expect($result)->toBeInstanceOf(EmployeeFaceEmbedding::class);
            expect($result->employee_id)->toBe($this->employee->id);
            expect($result->is_active)->toBeTrue();
            expect($result->enrolled_by)->toBe($this->admin->id);
        });

        it('replaces existing embedding when re-enrolling', function () {
            // First enrollment
            $oldEmbedding = EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'is_active' => true,
            ]);
            $oldId = $oldEmbedding->id;

            $newEmbeddingData = [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.6),
            ];

            // Re-enroll
            $result = $this->service->enrollFace(
                $this->employee,
                $newEmbeddingData,
                'photos/new.jpg',
                $this->admin
            );

            // Old embedding should be force deleted (due to unique constraint)
            expect(EmployeeFaceEmbedding::withTrashed()->find($oldId))->toBeNull();
            // New embedding should be active
            expect($result->is_active)->toBeTrue();
        });

        it('stores quality score if provided', function () {
            $embeddingData = [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ];

            $result = $this->service->enrollFace(
                $this->employee,
                $embeddingData,
                'photos/test.jpg',
                $this->admin,
                0.95
            );

            expect($result->quality_score)->toBe(0.95);
        });

        it('creates verification log for enrollment', function () {
            $embeddingData = [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_fill(0, 128, 0.5),
            ];

            $this->service->enrollFace(
                $this->employee,
                $embeddingData,
                'photos/test.jpg',
                $this->admin
            );

            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'enrollment',
                'is_successful' => true,
            ]);
        });
    });

    describe('verifyFace', function () {
        it('returns match when embeddings are similar', function () {
            // Create stored embedding
            $storedDescriptors = array_fill(0, 128, 0.5);
            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'embedding_data' => [
                    'version' => '1.0',
                    'model' => 'face-api-js',
                    'descriptors' => $storedDescriptors,
                ],
                'is_active' => true,
            ]);

            // Verify with similar embedding
            $liveDescriptors = array_fill(0, 128, 0.5);
            $result = $this->service->verifyFace(
                $this->employee,
                $liveDescriptors,
                0.6 // threshold
            );

            expect($result['matched'])->toBeTrue();
            expect($result['confidence'])->toBeGreaterThanOrEqual(0.6);
        });

        it('returns no match when embeddings are different', function () {
            // Create stored embedding
            $storedDescriptors = array_fill(0, 128, 0.5);
            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'embedding_data' => [
                    'version' => '1.0',
                    'model' => 'face-api-js',
                    'descriptors' => $storedDescriptors,
                ],
                'is_active' => true,
            ]);

            // Verify with very different embedding
            $liveDescriptors = array_fill(0, 128, -0.5);
            $result = $this->service->verifyFace(
                $this->employee,
                $liveDescriptors,
                0.6
            );

            expect($result['matched'])->toBeFalse();
            expect($result['confidence'])->toBeLessThan(0.6);
        });

        it('returns error when employee has no face enrolled', function () {
            $liveDescriptors = array_fill(0, 128, 0.5);
            $result = $this->service->verifyFace(
                $this->employee,
                $liveDescriptors,
                0.6
            );

            expect($result['matched'])->toBeFalse();
            expect($result['error'])->toBe('no_face_enrolled');
        });

        it('creates verification log', function () {
            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'embedding_data' => [
                    'model' => 'google_mlkit',
                    'version' => '1.0',
                    'embedding' => array_fill(0, 128, 0.5),
                ],
                'is_active' => true,
            ]);

            $liveDescriptors = array_fill(0, 128, 0.5);
            $this->service->verifyFace(
                $this->employee,
                $liveDescriptors,
                0.6,
                'clock_in'
            );

            $this->assertDatabaseHas('face_verification_logs', [
                'employee_id' => $this->employee->id,
                'verification_type' => 'clock_in',
            ]);
        });
    });

    describe('calculateSimilarity', function () {
        it('returns approximately 1.0 for identical embeddings', function () {
            $embedding1 = array_fill(0, 128, 0.5);
            $embedding2 = array_fill(0, 128, 0.5);

            $similarity = $this->service->calculateSimilarity($embedding1, $embedding2);

            // Use floating point tolerance
            expect($similarity)->toBeGreaterThan(0.9999);
            expect($similarity)->toBeLessThanOrEqual(1.0);
        });

        it('returns 0.0 for completely opposite embeddings', function () {
            $embedding1 = array_fill(0, 128, 1.0);
            $embedding2 = array_fill(0, 128, -1.0);

            $similarity = $this->service->calculateSimilarity($embedding1, $embedding2);

            expect($similarity)->toBeLessThanOrEqual(0.0);
        });

        it('returns value between 0 and 1 for different embeddings', function () {
            $embedding1 = array_map(fn () => rand(-100, 100) / 100, range(1, 128));
            $embedding2 = array_map(fn () => rand(-100, 100) / 100, range(1, 128));

            $similarity = $this->service->calculateSimilarity($embedding1, $embedding2);

            expect($similarity)->toBeGreaterThanOrEqual(-1.0);
            expect($similarity)->toBeLessThanOrEqual(1.0);
        });
    });

    describe('removeEnrollment', function () {
        it('soft deletes face embedding', function () {
            $embedding = EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'is_active' => true,
            ]);

            $result = $this->service->removeEnrollment($this->employee);

            expect($result)->toBeTrue();
            expect($embedding->fresh()->trashed())->toBeTrue();
        });

        it('returns false when no embedding exists', function () {
            $result = $this->service->removeEnrollment($this->employee);

            expect($result)->toBeFalse();
        });
    });

    describe('getEnrollmentStatus', function () {
        it('returns enrolled status when face is enrolled', function () {
            EmployeeFaceEmbedding::factory()->create([
                'employee_id' => $this->employee->id,
                'is_active' => true,
                'enrolled_at' => now()->subDays(5),
                'quality_score' => 0.92,
            ]);

            $status = $this->service->getEnrollmentStatus($this->employee);

            expect($status['enrolled'])->toBeTrue();
            expect($status['enrolled_at'])->not->toBeNull();
            expect($status['quality_score'])->toBe(0.92);
        });

        it('returns not enrolled status when no face enrolled', function () {
            $status = $this->service->getEnrollmentStatus($this->employee);

            expect($status['enrolled'])->toBeFalse();
            expect($status['enrolled_at'])->toBeNull();
        });
    });
});
