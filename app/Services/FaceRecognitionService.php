<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\FaceVerificationLog;
use App\Models\User;

class FaceRecognitionService
{
    /**
     * Enroll a new face for an employee.
     */
    public function enrollFace(
        Employee $employee,
        array $embeddingData,
        string $photoPath,
        ?User $enrolledBy = null,
        ?float $qualityScore = null
    ): EmployeeFaceEmbedding {
        // Force delete existing embedding if any (unique constraint on employee_id)
        $existingEmbedding = $employee->faceEmbedding;
        if ($existingEmbedding) {
            $existingEmbedding->forceDelete();
        }

        // Create new embedding
        $embedding = EmployeeFaceEmbedding::create([
            'employee_id' => $employee->id,
            'embedding_data' => $embeddingData,
            'enrollment_photo' => $photoPath,
            'enrolled_at' => now(),
            'enrolled_by' => $enrolledBy?->id,
            'quality_score' => $qualityScore,
            'is_active' => true,
        ]);

        // Log enrollment
        FaceVerificationLog::create([
            'employee_id' => $employee->id,
            'verification_type' => 'enrollment',
            'is_successful' => true,
            'confidence_score' => $qualityScore,
            'photo_path' => $photoPath,
        ]);

        return $embedding;
    }

    /**
     * Verify a face against stored embedding.
     *
     * @param  array  $liveDescriptors  The face descriptors from the live image
     * @param  float  $threshold  The minimum similarity threshold for a match
     * @param  string  $verificationType  The type of verification (clock_in, clock_out)
     * @return array{matched: bool, confidence: float|null, error: string|null}
     */
    public function verifyFace(
        Employee $employee,
        array $liveDescriptors,
        float $threshold = 0.6,
        string $verificationType = 'clock_in',
        ?string $photoPath = null
    ): array {
        $embedding = $employee->faceEmbedding()
            ->where('is_active', true)
            ->first();

        if (! $embedding) {
            $this->logVerification($employee, $verificationType, false, null, null, 'no_face_enrolled', $photoPath);

            return [
                'matched' => false,
                'confidence' => null,
                'error' => 'no_face_enrolled',
            ];
        }

        // Support both old 'descriptors' and new 'embedding' field names
        $storedDescriptors = $embedding->embedding_data['embedding']
            ?? $embedding->embedding_data['descriptors']
            ?? [];
        $similarity = $this->calculateSimilarity($storedDescriptors, $liveDescriptors);

        $matched = $similarity >= $threshold;

        $this->logVerification(
            $employee,
            $verificationType,
            $matched,
            $similarity,
            true, // liveness passed (would be validated client-side)
            $matched ? null : 'face_not_matched',
            $photoPath
        );

        return [
            'matched' => $matched,
            'confidence' => round($similarity, 4),
            'error' => $matched ? null : 'face_not_matched',
        ];
    }

    /**
     * Calculate cosine similarity between two embedding vectors.
     * Returns a value between -1 and 1, where 1 means identical.
     */
    public function calculateSimilarity(array $embedding1, array $embedding2): float
    {
        if (count($embedding1) !== count($embedding2) || empty($embedding1)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $magnitude1 = 0.0;
        $magnitude2 = 0.0;

        for ($i = 0; $i < count($embedding1); $i++) {
            $dotProduct += $embedding1[$i] * $embedding2[$i];
            $magnitude1 += $embedding1[$i] * $embedding1[$i];
            $magnitude2 += $embedding2[$i] * $embedding2[$i];
        }

        $magnitude1 = sqrt($magnitude1);
        $magnitude2 = sqrt($magnitude2);

        if ($magnitude1 === 0.0 || $magnitude2 === 0.0) {
            return 0.0;
        }

        return $dotProduct / ($magnitude1 * $magnitude2);
    }

    /**
     * Remove face enrollment for an employee.
     */
    public function removeEnrollment(Employee $employee): bool
    {
        $embedding = $employee->faceEmbedding;

        if (! $embedding) {
            return false;
        }

        $embedding->delete();

        return true;
    }

    /**
     * Get face enrollment status for an employee.
     *
     * @return array{enrolled: bool, enrolled_at: string|null, quality_score: float|null}
     */
    public function getEnrollmentStatus(Employee $employee): array
    {
        $embedding = $employee->faceEmbedding()
            ->where('is_active', true)
            ->first();

        if (! $embedding) {
            return [
                'enrolled' => false,
                'enrolled_at' => null,
                'quality_score' => null,
            ];
        }

        return [
            'enrolled' => true,
            'enrolled_at' => $embedding->enrolled_at?->toIso8601String(),
            'quality_score' => $embedding->quality_score,
        ];
    }

    /**
     * Log a face verification attempt.
     */
    protected function logVerification(
        Employee $employee,
        string $verificationType,
        bool $isSuccessful,
        ?float $confidenceScore,
        ?bool $livenessPassed = null,
        ?string $failureReason = null,
        ?string $photoPath = null
    ): void {
        FaceVerificationLog::create([
            'employee_id' => $employee->id,
            'verification_type' => $verificationType,
            'is_successful' => $isSuccessful,
            'confidence_score' => $confidenceScore,
            'liveness_passed' => $livenessPassed,
            'failure_reason' => $failureReason,
            'photo_path' => $photoPath,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
