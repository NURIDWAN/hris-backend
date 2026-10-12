<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveType;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = LeaveType::class;

    public function definition(): array
    {
        $leaveTypes = ['Cuti Tahunan', 'Cuti Sakit', 'Cuti Melahirkan', 'Cuti Menikah', 'Izin Pribadi'];
        $codes = ['CT', 'CS', 'CM', 'CK', 'IP'];
        $index = array_rand($leaveTypes);

        return [
            'company_id' => Company::factory(),
            'name' => $leaveTypes[$index],
            'code' => $codes[$index].rand(100, 999),
            'description' => $this->randomBoolean() ? $this->randomSentence() : null,
            'default_days' => rand(1, 12),
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => false,
            'max_consecutive_days' => $this->randomBoolean() ? rand(3, 14) : null,
            'min_notice_days' => rand(0, 7),
            'is_carry_forward' => false,
            'max_carry_forward_days' => null,
            'is_active' => true,
            'color' => $this->randomElement(['primary', 'success', 'warning', 'danger', 'info']),
            'is_annual' => false,
        ];
    }

    public function annual(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cuti Tahunan',
            'code' => 'CT',
            'default_days' => 12,
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => false,
            'is_carry_forward' => true,
            'max_carry_forward_days' => 6,
            'color' => 'primary',
            'is_annual' => true,
        ]);
    }

    public function sick(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cuti Sakit',
            'code' => 'CS',
            'default_days' => 14,
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => true,
            'min_notice_days' => 0,
            'is_carry_forward' => false,
            'color' => 'danger',
        ]);
    }

    public function maternity(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cuti Melahirkan',
            'code' => 'CM',
            'default_days' => 90,
            'is_paid' => true,
            'requires_approval' => true,
            'requires_attachment' => true,
            'max_consecutive_days' => 90,
            'min_notice_days' => 14,
            'is_carry_forward' => false,
            'color' => 'info',
        ]);
    }

    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Cuti Tanpa Gaji',
            'code' => 'CTG',
            'default_days' => 30,
            'is_paid' => false,
            'requires_approval' => true,
            'requires_attachment' => false,
            'color' => 'secondary',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function withCarryForward(int $maxDays = 6): static
    {
        return $this->state(fn (array $attributes) => [
            'is_carry_forward' => true,
            'max_carry_forward_days' => $maxDays,
        ]);
    }

    public function requiresAttachment(): static
    {
        return $this->state(fn (array $attributes) => [
            'requires_attachment' => true,
        ]);
    }
}
