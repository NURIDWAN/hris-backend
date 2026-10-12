<?php

namespace Database\Factories;

use App\Models\BpjsTkSetting;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BpjsTkSetting>
 */
class BpjsTkSettingFactory extends Factory
{
    protected $model = BpjsTkSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jkk_rate' => 0.24,
            'jkk_risk_level' => 'very_low',
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => date('Y'),
            'is_active' => true,
        ];
    }

    /**
     * High risk JKK setting.
     */
    public function highRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'jkk_rate' => 1.27,
            'jkk_risk_level' => 'high',
        ]);
    }

    /**
     * Very high risk JKK setting.
     */
    public function veryHighRisk(): static
    {
        return $this->state(fn (array $attributes) => [
            'jkk_rate' => 1.74,
            'jkk_risk_level' => 'very_high',
        ]);
    }

    /**
     * All programs disabled.
     */
    public function allDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'jht_enabled' => false,
            'jkk_enabled' => false,
            'jkm_enabled' => false,
            'jp_enabled' => false,
        ]);
    }
}
