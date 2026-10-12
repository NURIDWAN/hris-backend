<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeWeeklySchedule>
 */
class EmployeeWeeklyScheduleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'day_of_week' => fake()->numberBetween(1, 7),
            'work_schedule_id' => WorkSchedule::factory(),
        ];
    }

    public function forDay(int $day): static
    {
        return $this->state(fn () => [
            'day_of_week' => $day,
        ]);
    }
}
