<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeSalary>
 */
class EmployeeSalaryFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = EmployeeSalary::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethod = $this->randomElement(['transfer', 'cash']);

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'basic_salary' => $this->randomFloat(3000000, 50000000, 2),
            'effective_date' => now()->startOfMonth(),
            'end_date' => null,
            'payment_method' => $paymentMethod,
            'bank_name' => $paymentMethod === 'transfer' ? $this->randomBankName() : null,
            'bank_account_number' => $paymentMethod === 'transfer' ? $this->numerify('##########') : null,
            'bank_account_name' => $paymentMethod === 'transfer' ? $this->randomFullName() : null,
            'is_active' => true,
            'notes' => $this->randomBoolean() ? $this->randomSentence() : null,
        ];
    }

    /**
     * Configure as active salary.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
            'end_date' => null,
        ]);
    }

    /**
     * Configure as inactive salary.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Configure with bank transfer payment.
     */
    public function transfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'transfer',
            'bank_name' => $this->randomBankName(),
            'bank_account_number' => $this->numerify('##########'),
            'bank_account_name' => $this->randomFullName(),
        ]);
    }

    /**
     * Configure with cash payment.
     */
    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'cash',
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_name' => null,
        ]);
    }

    /**
     * Configure with specific salary amount.
     */
    public function withSalary(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'basic_salary' => $amount,
        ]);
    }

    /**
     * Configure with ended salary period.
     */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => now()->subDay(),
            'is_active' => false,
        ]);
    }

    /**
     * Configure effective from a specific date.
     */
    public function effectiveFrom(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'effective_date' => $date,
        ]);
    }
}
