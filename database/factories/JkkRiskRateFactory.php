<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JkkRiskRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JkkRiskRate>
 */
class JkkRiskRateFactory extends Factory
{
    protected $model = JkkRiskRate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'risk_level' => 'very_low',
            'description' => 'Perkantoran, jasa konsultasi',
            'rate' => 0.24,
            'year' => date('Y'),
            'is_active' => true,
        ];
    }

    /**
     * Low risk level.
     */
    public function low(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'low',
            'description' => 'Industri ringan, restoran, hotel',
            'rate' => 0.54,
        ]);
    }

    /**
     * Medium risk level.
     */
    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'medium',
            'description' => 'Manufaktur, pertanian, perikanan',
            'rate' => 0.89,
        ]);
    }

    /**
     * High risk level.
     */
    public function high(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'high',
            'description' => 'Konstruksi, transportasi, pergudangan',
            'rate' => 1.27,
        ]);
    }

    /**
     * Very high risk level.
     */
    public function veryHigh(): static
    {
        return $this->state(fn (array $attributes) => [
            'risk_level' => 'very_high',
            'description' => 'Pertambangan, pengeboran minyak',
            'rate' => 1.74,
        ]);
    }
}
