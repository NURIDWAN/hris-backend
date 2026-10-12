<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\OvertimeSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OvertimeSetting>
 */
class OvertimeSettingFactory extends Factory
{
    protected $model = OvertimeSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'weekday_rate_first_hour' => 1.5,
            'weekday_rate_next_hours' => 2.0,
            'weekend_rate' => 2.0,
            'holiday_rate' => 3.0,
            'max_overtime_hours_per_day' => 4,
            'max_overtime_hours_per_week' => 14,
            'min_overtime_minutes' => 30,
            'working_hours_per_month' => 173,
            'requires_approval' => true,
            'auto_calculate_from_attendance' => false,
            'is_active' => true,
        ];
    }

    /**
     * Auto calculate from attendance.
     */
    public function autoCalculate(): static
    {
        return $this->state(fn (array $attributes) => [
            'auto_calculate_from_attendance' => true,
        ]);
    }

    /**
     * No approval required.
     */
    public function noApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_approval' => false,
        ]);
    }
}
