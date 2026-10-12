<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SalaryComponent;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalaryComponent>
 */
class SalaryComponentFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = SalaryComponent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->randomElement(['earning', 'deduction']);
        $categories = $type === 'earning'
            ? ['fixed', 'variable', 'benefit']
            : ['tax', 'insurance', 'loan', 'other'];

        $words = ['Tunjangan', 'Bonus', 'Premi', 'Potongan', 'Iuran', 'Pajak'];

        return [
            'company_id' => Company::factory(),
            'name' => $this->randomElement($words).' '.$this->randomElement(['Tetap', 'Harian', 'Bulanan']),
            'code' => strtoupper(substr($this->randomElement($words), 0, 3)).rand(100, 999),
            'type' => $type,
            'category' => $this->randomElement($categories),
            'calculation_type' => 'fixed',
            'default_amount' => $this->randomFloat(100000, 5000000, 2),
            'percentage' => null,
            'formula' => null,
            'description' => $this->randomBoolean() ? $this->randomSentence() : null,
            'is_taxable' => $type === 'earning' ? $this->randomBoolean(80) : false,
            'is_active' => true,
            'is_mandatory' => $this->randomBoolean(30),
            'sort_order' => rand(1, 100),
        ];
    }

    /**
     * Configure as earning type.
     */
    public function earning(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'earning',
            'category' => $this->randomElement(['fixed', 'variable', 'benefit']),
            'is_taxable' => true,
        ]);
    }

    /**
     * Configure as deduction type.
     */
    public function deduction(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'deduction',
            'category' => $this->randomElement(['tax', 'insurance', 'loan', 'other']),
            'is_taxable' => false,
        ]);
    }

    /**
     * Configure as percentage calculation.
     */
    public function percentage(float $percentage = null): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_type' => 'percentage',
            'percentage' => $percentage ?? $this->randomFloat(1, 15, 2),
            'default_amount' => null,
        ]);
    }

    /**
     * Configure as fixed calculation.
     */
    public function fixed(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'calculation_type' => 'fixed',
            'default_amount' => $amount ?? $this->randomFloat(100000, 5000000, 2),
            'percentage' => null,
        ]);
    }

    /**
     * Configure as mandatory.
     */
    public function mandatory(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_mandatory' => true,
        ]);
    }

    /**
     * Configure as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure as taxable.
     */
    public function taxable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_taxable' => true,
        ]);
    }

    /**
     * Configure as non-taxable.
     */
    public function nonTaxable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_taxable' => false,
        ]);
    }
}
