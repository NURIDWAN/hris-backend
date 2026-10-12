<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Pph21Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pph21Setting>
 */
class Pph21SettingFactory extends Factory
{
    protected $model = Pph21Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => date('Y'),
        ];
    }

    /**
     * Gross up setting.
     */
    public function grossUp(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_gross_up' => true,
        ]);
    }

    /**
     * Use progressive rate instead of TER.
     */
    public function progressive(): static
    {
        return $this->state(fn (array $attributes) => [
            'use_ter' => false,
        ]);
    }
}
