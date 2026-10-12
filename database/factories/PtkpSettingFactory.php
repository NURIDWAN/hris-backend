<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\PtkpSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PtkpSetting>
 */
class PtkpSettingFactory extends Factory
{
    protected $model = PtkpSetting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'status_code' => 'TK/0',
            'description' => 'Tidak Kawin, Tanpa Tanggungan',
            'annual_amount' => 54000000,
            'year' => date('Y'),
            'is_active' => true,
        ];
    }

    /**
     * Married status.
     */
    public function married(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_code' => 'K/0',
            'description' => 'Kawin, Tanpa Tanggungan',
            'annual_amount' => 58500000,
        ]);
    }

    /**
     * Married with dependents.
     */
    public function marriedWithDependents(int $dependents = 1): static
    {
        $amounts = [
            1 => 63000000,
            2 => 67500000,
            3 => 72000000,
        ];

        return $this->state(fn (array $attributes) => [
            'status_code' => "K/{$dependents}",
            'description' => "Kawin, {$dependents} Tanggungan",
            'annual_amount' => $amounts[$dependents] ?? 63000000,
        ]);
    }
}
