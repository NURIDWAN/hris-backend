<?php

namespace Database\Factories;

use App\Models\Company;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WorkSchedule>
 */
class WorkScheduleFactory extends Factory
{
    use GeneratesRandomData;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->randomElement(['Regular Office', 'Shift Pagi', 'Shift Siang', 'Shift Malam', 'Flexible']),
            'code' => strtoupper('SCH-'.$this->randomElement(['REG', 'PAG', 'SIA', 'MAL', 'FLX']).rand(100, 999)),
            'start_time' => '08:00',
            'end_time' => '17:00',
            'break_start' => '12:00',
            'break_end' => '13:00',
            'break_duration' => 60,
            'working_hours' => 8,
            'working_days' => [1, 2, 3, 4, 5], // Mon-Fri
            'late_tolerance' => 15,
            'early_leave_tolerance' => 15,
            'is_flexible' => false,
            'is_default' => false,
            'is_active' => true,
            'description' => $this->randomBoolean() ? $this->randomSentence() : null,
        ];
    }

    public function morning(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Pagi',
            'start_time' => '06:00',
            'end_time' => '14:00',
            'break_start' => '10:00',
            'break_end' => '10:30',
            'break_duration' => 30,
        ]);
    }

    public function afternoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Siang',
            'start_time' => '14:00',
            'end_time' => '22:00',
            'break_start' => '18:00',
            'break_end' => '18:30',
            'break_duration' => 30,
        ]);
    }

    public function night(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Shift Malam',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_start' => '02:00',
            'break_end' => '02:30',
            'break_duration' => 30,
        ]);
    }

    public function flexible(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Flexible',
            'is_flexible' => true,
            'late_tolerance' => 60,
            'early_leave_tolerance' => 60,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
