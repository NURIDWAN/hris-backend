<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeFaceEmbedding;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeFaceEmbedding>
 */
class EmployeeFaceEmbeddingFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = EmployeeFaceEmbedding::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'embedding_data' => [
                'model' => 'google_mlkit',
                'version' => '1.0',
                'embedding' => array_map(fn () => $this->randomFloat(-1, 1, 6), range(1, 128)),
            ],
            'enrollment_photo' => 'face-photos/'.\Illuminate\Support\Str::uuid()->toString().'.jpg',
            'enrolled_at' => now(),
            'enrolled_by' => null,
            'quality_score' => $this->randomFloat(0.7, 1.0, 4),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function enrolledBy(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'enrolled_by' => $user->id,
        ]);
    }

    public function withLowQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'quality_score' => $this->randomFloat(0.3, 0.5, 4),
        ]);
    }
}
