<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OvertimeRequest>
 */
class OvertimeRequestFactory extends Factory
{
    protected $model = OvertimeRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_hours' => 3.0,
            'overtime_type' => $this->faker->randomElement(['weekday', 'weekend', 'holiday']),
            'overtime_amount' => $this->faker->numberBetween(100000, 500000),
            'reason' => $this->faker->sentence(),
            'status' => OvertimeRequest::STATUS_PENDING,
        ];
    }

    /**
     * Pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OvertimeRequest::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Approved status.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OvertimeRequest::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    /**
     * Rejected status.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OvertimeRequest::STATUS_REJECTED,
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Cancelled status.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OvertimeRequest::STATUS_CANCELLED,
        ]);
    }

    /**
     * Weekday overtime.
     */
    public function weekday(): static
    {
        return $this->state(fn (array $attributes) => [
            'overtime_type' => OvertimeRequest::TYPE_WEEKDAY,
        ]);
    }

    /**
     * Weekend overtime.
     */
    public function weekend(): static
    {
        return $this->state(fn (array $attributes) => [
            'overtime_type' => OvertimeRequest::TYPE_WEEKEND,
        ]);
    }

    /**
     * Holiday overtime.
     */
    public function holiday(): static
    {
        return $this->state(fn (array $attributes) => [
            'overtime_type' => OvertimeRequest::TYPE_HOLIDAY,
        ]);
    }
}
