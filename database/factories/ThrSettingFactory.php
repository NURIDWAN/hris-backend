<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThrSetting>
 */
class ThrSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'prorata_formula' => 'months_worked_per_12',
            'include_allowances' => true,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
            'is_active' => true,
        ];
    }

    public function prorata(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_method' => 'prorata',
        ]);
    }

    public function oneMonthSalary(): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_method' => 'one_month_salary',
        ]);
    }

    public function withoutAllowances(): static
    {
        return $this->state(fn (array $attributes) => [
            'include_allowances' => false,
        ]);
    }
}
