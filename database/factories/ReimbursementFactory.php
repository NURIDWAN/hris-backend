<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reimbursement>
 */
class ReimbursementFactory extends Factory
{
    protected $model = Reimbursement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'category_id' => ReimbursementCategory::factory(),
            'amount' => $this->faker->numberBetween(50000, 500000),
            'description' => $this->faker->sentence(),
            'expense_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'status' => Reimbursement::STATUS_PENDING,
        ];
    }

    /**
     * Pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reimbursement::STATUS_PENDING,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    /**
     * Approved status.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_at' => now(),
        ]);
    }

    /**
     * Rejected status.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reimbursement::STATUS_REJECTED,
            'rejection_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Paid status.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Reimbursement::STATUS_PAID,
            'approved_at' => now()->subDay(),
            'paid_at' => now(),
            'payment_method' => 'transfer',
        ]);
    }

    /**
     * With receipt.
     */
    public function withReceipt(): static
    {
        return $this->state(fn (array $attributes) => [
            'receipt_path' => 'receipts/'.$this->faker->uuid().'.jpg',
        ]);
    }
}
