<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Payroll;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payroll>
 */
class PayrollFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = Payroll::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $month = rand(1, 12);
        $year = rand(2023, 2026);
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'company_id' => Company::factory(),
            'payroll_number' => null, // Will be auto-generated
            'name' => "Gaji Bulan {$month} {$year}",
            'period_month' => $month,
            'period_year' => $year,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'payment_date' => $endDate->copy()->addDays(5),
            'status' => 'draft',
            'total_employees' => 0,
            'total_earnings' => 0,
            'total_deductions' => 0,
            'total_net_salary' => 0,
            'created_by' => User::factory(),
            'approved_by' => null,
            'approved_at' => null,
            'paid_by' => null,
            'paid_at' => null,
            'notes' => $this->randomBoolean() ? $this->randomSentence() : null,
        ];
    }

    /**
     * Configure for specific period.
     */
    public function forPeriod(int $year, int $month): static
    {
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return $this->state(fn (array $attributes) => [
            'name' => "Gaji Bulan {$month} {$year}",
            'period_month' => $month,
            'period_year' => $year,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'payment_date' => $endDate->copy()->addDays(5),
        ]);
    }

    /**
     * Configure as draft status.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
            'paid_by' => null,
            'paid_at' => null,
        ]);
    }

    /**
     * Configure as processing status.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'processing',
        ]);
    }

    /**
     * Configure as calculated status.
     */
    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'calculated',
        ]);
    }

    /**
     * Configure as approved status.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    /**
     * Configure as paid status.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'approved_by' => User::factory(),
            'approved_at' => now()->subDay(),
            'paid_by' => User::factory(),
            'paid_at' => now(),
        ]);
    }

    /**
     * Configure as cancelled status.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    /**
     * Configure with totals.
     */
    public function withTotals(int $employees, float $earnings, float $deductions, float $netSalary): static
    {
        return $this->state(fn (array $attributes) => [
            'total_employees' => $employees,
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'total_net_salary' => $netSalary,
        ]);
    }
}
