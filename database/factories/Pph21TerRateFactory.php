<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Pph21TerRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pph21TerRate>
 */
class Pph21TerRateFactory extends Factory
{
    protected $model = Pph21TerRate::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'category' => fake()->randomElement(['A', 'B', 'C']),
            'ptkp_status' => 'TK/0',
            'min_income' => 0,
            'max_income' => 5400000,
            'rate' => 0,
            'year' => date('Y'),
            'is_active' => true,
        ];
    }
}
