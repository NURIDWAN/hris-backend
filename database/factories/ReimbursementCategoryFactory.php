<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ReimbursementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReimbursementCategory>
 */
class ReimbursementCategoryFactory extends Factory
{
    protected $model = ReimbursementCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->unique()->randomElement([
                'Transportasi',
                'Makan',
                'Akomodasi',
                'Kesehatan',
                'Pendidikan',
                'Operasional',
            ]),
            'description' => $this->faker->sentence(),
            'max_amount' => $this->faker->randomElement([500000, 1000000, 2000000, null]),
            'requires_receipt' => $this->faker->boolean(80),
            'is_active' => true,
        ];
    }

    /**
     * Active category.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Inactive category.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Requires receipt.
     */
    public function requiresReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_receipt' => true,
        ]);
    }

    /**
     * No receipt required.
     */
    public function noReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_receipt' => false,
        ]);
    }
}
