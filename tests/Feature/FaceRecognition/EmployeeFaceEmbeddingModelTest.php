<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\FaceVerificationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('EmployeeFaceEmbedding Model', function () {
    it('can be created with factory', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        expect($embedding)->toBeInstanceOf(EmployeeFaceEmbedding::class);
        expect($embedding->employee_id)->toBe($this->employee->id);
    });

    it('belongs to an employee', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        expect($embedding->employee)->toBeInstanceOf(Employee::class);
        expect($embedding->employee->id)->toBe($this->employee->id);
    });

    it('stores embedding data as json', function () {
        $embeddingData = [
            'model' => 'google_mlkit',
            'version' => '1.0',
            'embedding' => array_fill(0, 128, 0.5),
        ];

        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'embedding_data' => $embeddingData,
        ]);

        expect($embedding->embedding_data)->toBeArray();
        expect($embedding->embedding_data['version'])->toBe('1.0');
        expect($embedding->embedding_data['embedding'])->toHaveCount(128);
    });

    it('casts enrolled_at to datetime', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'enrolled_at' => now(),
        ]);

        expect($embedding->enrolled_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('casts is_active to boolean', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        expect($embedding->is_active)->toBeTrue();
    });

    it('enforces one embedding per employee', function () {
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        // Creating another should fail (unique constraint)
        expect(fn () => EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('can track who enrolled the face', function () {
        $admin = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'enrolled_by' => $admin->id,
        ]);

        expect($embedding->enrolledBy)->toBeInstanceOf(User::class);
        expect($embedding->enrolledBy->id)->toBe($admin->id);
    });

    it('can store quality score', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'quality_score' => 0.95,
        ]);

        expect($embedding->quality_score)->toBe(0.95);
    });

    it('can be soft deleted', function () {
        $embedding = EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $embedding->delete();

        expect($embedding->trashed())->toBeTrue();
        expect(EmployeeFaceEmbedding::withTrashed()->find($embedding->id))->not->toBeNull();
    });

    it('has scope for active embeddings', function () {
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $employee2->id,
            'is_active' => false,
        ]);

        $activeEmbeddings = EmployeeFaceEmbedding::active()->get();

        expect($activeEmbeddings)->toHaveCount(1);
    });
});

describe('Employee hasFaceEnrolled', function () {
    it('returns true when employee has active face embedding', function () {
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => true,
        ]);

        expect($this->employee->hasFaceEnrolled())->toBeTrue();
    });

    it('returns false when employee has no face embedding', function () {
        expect($this->employee->hasFaceEnrolled())->toBeFalse();
    });

    it('returns false when employee face embedding is inactive', function () {
        EmployeeFaceEmbedding::factory()->create([
            'employee_id' => $this->employee->id,
            'is_active' => false,
        ]);

        expect($this->employee->hasFaceEnrolled())->toBeFalse();
    });
});

describe('FaceVerificationLog Model', function () {
    it('can be created with factory', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        expect($log)->toBeInstanceOf(FaceVerificationLog::class);
    });

    it('belongs to an employee', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        expect($log->employee)->toBeInstanceOf(Employee::class);
    });

    it('stores verification type as enum', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'verification_type' => 'clock_in',
        ]);

        expect($log->verification_type)->toBe('clock_in');
    });

    it('casts is_successful to boolean', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'is_successful' => true,
        ]);

        expect($log->is_successful)->toBeTrue();
    });

    it('casts liveness_passed to boolean', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'liveness_passed' => true,
        ]);

        expect($log->liveness_passed)->toBeTrue();
    });

    it('stores metadata as json', function () {
        $metadata = [
            'device' => 'iPhone 14',
            'os' => 'iOS 17',
            'app_version' => '1.0.0',
        ];

        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'metadata' => $metadata,
        ]);

        expect($log->metadata)->toBeArray();
        expect($log->metadata['device'])->toBe('iPhone 14');
    });

    it('tracks confidence score', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'confidence_score' => 0.89,
        ]);

        expect($log->confidence_score)->toBe(0.89);
    });

    it('tracks failure reason when verification fails', function () {
        $log = FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'is_successful' => false,
            'failure_reason' => 'face_not_matched',
        ]);

        expect($log->failure_reason)->toBe('face_not_matched');
    });

    it('has scope for successful verifications', function () {
        FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'is_successful' => true,
        ]);

        FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'is_successful' => false,
        ]);

        $successfulLogs = FaceVerificationLog::successful()->get();

        expect($successfulLogs)->toHaveCount(1);
    });

    it('has scope for failed verifications', function () {
        FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'is_successful' => true,
        ]);

        FaceVerificationLog::factory()->create([
            'employee_id' => $this->employee->id,
            'is_successful' => false,
        ]);

        $failedLogs = FaceVerificationLog::failed()->get();

        expect($failedLogs)->toHaveCount(1);
    });
});

describe('Company Face Recognition Settings', function () {
    it('has enable_face_recognition setting', function () {
        $this->company->update(['enable_face_recognition' => true]);

        expect($this->company->fresh()->enable_face_recognition)->toBeTrue();
    });

    it('has face_match_threshold setting', function () {
        $this->company->update(['face_match_threshold' => 0.7]);

        expect($this->company->fresh()->face_match_threshold)->toBe(0.7);
    });

    it('has require_liveness_detection setting', function () {
        $this->company->update(['require_liveness_detection' => true]);

        expect($this->company->fresh()->require_liveness_detection)->toBeTrue();
    });
});
