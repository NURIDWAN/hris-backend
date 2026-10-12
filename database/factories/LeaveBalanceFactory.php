<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
{
    protected $model = LeaveBalance::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'year' => now()->year,
            'entitled_days' => 12,
            'used_days' => 0,
            'pending_days' => 0,
            'carried_forward_days' => 0,
            'adjustment_days' => 0,
            'adjustment_notes' => null,
        ];
    }

    public function forYear(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'year' => $year,
        ]);
    }

    public function withUsedDays(float $days): static
    {
        return $this->state(fn (array $attributes) => [
            'used_days' => $days,
        ]);
    }

    public function withPendingDays(float $days): static
    {
        return $this->state(fn (array $attributes) => [
            'pending_days' => $days,
        ]);
    }

    public function withCarriedForward(float $days): static
    {
        return $this->state(fn (array $attributes) => [
            'carried_forward_days' => $days,
        ]);
    }

    public function withAdjustment(float $days, ?string $notes = null): static
    {
        return $this->state(fn (array $attributes) => [
            'adjustment_days' => $days,
            'adjustment_notes' => $notes,
        ]);
    }

    public function fullyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'used_days' => $attributes['entitled_days'] ?? 12,
        ]);
    }

    public function partiallyUsed(): static
    {
        return $this->state(fn (array $attributes) => [
            'entitled_days' => 12,
            'used_days' => 5,
            'pending_days' => 2,
        ]);
    }

    public function withEntitlement(float $days): static
    {
        return $this->state(fn (array $attributes) => [
            'entitled_days' => $days,
        ]);
    }
}
