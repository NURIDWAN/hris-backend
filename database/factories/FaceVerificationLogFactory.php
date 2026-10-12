<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\FaceVerificationLog;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FaceVerificationLog>
 */
class FaceVerificationLogFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = FaceVerificationLog::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'attendance_id' => null,
            'verification_type' => $this->randomElement(['clock_in', 'clock_out', 'enrollment']),
            'is_successful' => true,
            'confidence_score' => $this->randomFloat(0.7, 1.0, 4),
            'liveness_passed' => true,
            'failure_reason' => null,
            'photo_path' => 'verification-photos/'.\Illuminate\Support\Str::uuid()->toString().'.jpg',
            'metadata' => [
                'device' => $this->randomElement(['iPhone 14', 'Samsung Galaxy S23', 'Pixel 7']),
                'os' => $this->randomElement(['iOS 17', 'Android 14']),
                'app_version' => '1.0.0',
            ],
            'ip_address' => rand(1, 254).'.'.rand(1, 254).'.'.rand(1, 254).'.'.rand(1, 254),
            'user_agent' => 'Mozilla/5.0 (Mobile; Android) AppleWebKit/537.36',
            'created_at' => now(),
        ];
    }

    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_successful' => true,
            'failure_reason' => null,
        ]);
    }

    public function failed(string $reason = 'face_not_matched'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_successful' => false,
            'failure_reason' => $reason,
            'confidence_score' => $this->randomFloat(0.1, 0.5, 4),
        ]);
    }

    public function livenesssFailed(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_successful' => false,
            'liveness_passed' => false,
            'failure_reason' => 'liveness_failed',
        ]);
    }

    public function forClockIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_type' => 'clock_in',
        ]);
    }

    public function forClockOut(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_type' => 'clock_out',
        ]);
    }

    public function forEnrollment(): static
    {
        return $this->state(fn (array $attributes) => [
            'verification_type' => 'enrollment',
        ]);
    }

    public function withAttendance(Attendance $attendance): static
    {
        return $this->state(fn (array $attributes) => [
            'attendance_id' => $attendance->id,
        ]);
    }
}
