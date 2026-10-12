<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by' => User::factory(),
            'title' => $this->faker->sentence(6),
            'content' => $this->faker->paragraphs(3, true),
            'priority' => $this->faker->randomElement([
                Announcement::PRIORITY_LOW,
                Announcement::PRIORITY_NORMAL,
                Announcement::PRIORITY_HIGH,
                Announcement::PRIORITY_URGENT,
            ]),
            'target_audience' => Announcement::TARGET_ALL,
            'target_ids' => null,
            'is_pinned' => false,
            'is_published' => false,
            'published_at' => null,
            'expires_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now(),
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function urgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Announcement::PRIORITY_URGENT,
        ]);
    }

    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => Announcement::PRIORITY_HIGH,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => true,
            'published_at' => now()->subWeek(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function forDepartment(int $departmentId): static
    {
        return $this->state(fn (array $attributes) => [
            'target_audience' => Announcement::TARGET_DEPARTMENT,
            'target_ids' => [$departmentId],
        ]);
    }

    public function forPosition(int $positionId): static
    {
        return $this->state(fn (array $attributes) => [
            'target_audience' => Announcement::TARGET_POSITION,
            'target_ids' => [$positionId],
        ]);
    }

    public function forEmployees(array $employeeIds): static
    {
        return $this->state(fn (array $attributes) => [
            'target_audience' => Announcement::TARGET_SPECIFIC,
            'target_ids' => $employeeIds,
        ]);
    }
}
