<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Pph21Rate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pph21Rate>
 */
class Pph21RateFactory extends Factory
{
    protected $model = Pph21Rate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'min_amount' => 0,
            'max_amount' => 60000000,
            'rate' => 5,
            'year' => date('Y'),
            'is_active' => true,
        ];
    }

    /**
     * Second bracket (15%).
     */
    public function secondBracket(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_amount' => 60000000,
            'max_amount' => 250000000,
            'rate' => 15,
        ]);
    }

    /**
     * Third bracket (25%).
     */
    public function thirdBracket(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_amount' => 250000000,
            'max_amount' => 500000000,
            'rate' => 25,
        ]);
    }

    /**
     * Fourth bracket (30%).
     */
    public function fourthBracket(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_amount' => 500000000,
            'max_amount' => 5000000000,
            'rate' => 30,
        ]);
    }

    /**
     * Fifth bracket (35% - unlimited).
     */
    public function fifthBracket(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_amount' => 5000000000,
            'max_amount' => null,
            'rate' => 35,
        ]);
    }
}
