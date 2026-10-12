<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BpjsKesSetting>
 */
class BpjsKesSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
            'effective_year' => date('Y'),
            'notes' => null,
            'is_active' => true,
        ];
    }
}
