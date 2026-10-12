<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\ThrPayment;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ThrPayment>
 */
class ThrPaymentFactory extends Factory
{
    use GeneratesRandomData;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'year' => now()->year,
            'religious_holiday' => 'idul_fitri',
            'base_salary' => rand(3000000, 10000000),
            'allowances' => rand(0, 2000000),
            'amount' => rand(3000000, 12000000),
            'service_months' => rand(1, 60),
            'calculation_method' => 'one_month_salary',
            'status' => ThrPayment::STATUS_PENDING,
            'payment_date' => now()->addDays(7),
            'paid_at' => null,
            'notes' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThrPayment::STATUS_PENDING,
            'paid_at' => null,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThrPayment::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ThrPayment::STATUS_CANCELLED,
            'paid_at' => null,
        ]);
    }
}
