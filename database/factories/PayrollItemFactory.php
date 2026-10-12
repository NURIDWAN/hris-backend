<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\PayrollItem;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PayrollItem>
 */
class PayrollItemFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = PayrollItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $workingDays = 22;
        $presentDays = rand(18, 22);
        $absentDays = $workingDays - $presentDays;
        $lateDays = rand(0, 3);
        $leaveDays = rand(0, 2);
        $overtimeHours = rand(0, 20);

        $basicSalary = $this->randomFloat(3000000, 20000000, 2);
        $totalEarnings = $this->randomFloat(500000, 3000000, 2);
        $totalDeductions = $this->randomFloat(100000, 1000000, 2);
        $grossSalary = $basicSalary + $totalEarnings;
        $taxAmount = $grossSalary * 0.05;
        $netSalary = $grossSalary - $totalDeductions - $taxAmount;

        $paymentMethod = $this->randomElement(['transfer', 'cash']);

        return [
            'payroll_id' => Payroll::factory(),
            'employee_id' => Employee::factory(),
            'employee_salary_id' => EmployeeSalary::factory(),
            'employee_name' => $this->randomFullName(),
            'employee_number' => 'EMP'.str_pad((string) rand(1, 999), 3, '0', STR_PAD_LEFT),
            'department_name' => $this->randomElement(['IT', 'Finance', 'HR', 'Marketing', 'Operations']),
            'position_name' => $this->randomElement(['Staff', 'Supervisor', 'Manager', 'Director']),
            'working_days' => $workingDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'leave_days' => $leaveDays,
            'overtime_hours' => $overtimeHours,
            'basic_salary' => $basicSalary,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'gross_salary' => $grossSalary,
            'tax_amount' => $taxAmount,
            'net_salary' => $netSalary,
            'payment_method' => $paymentMethod,
            'bank_name' => $paymentMethod === 'transfer' ? $this->randomBankName() : null,
            'bank_account_number' => $paymentMethod === 'transfer' ? $this->numerify('##########') : null,
            'bank_account_name' => $paymentMethod === 'transfer' ? $this->randomFullName() : null,
            'status' => 'pending',
            'paid_at' => null,
            'notes' => $this->randomBoolean() ? $this->randomSentence() : null,
        ];
    }

    /**
     * Configure with pending status.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
        ]);
    }

    /**
     * Configure with calculated status.
     */
    public function calculated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'calculated',
        ]);
    }

    /**
     * Configure with approved status.
     */
    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
        ]);
    }

    /**
     * Configure with paid status.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Configure with specific salary.
     */
    public function withSalary(float $basicSalary, float $earnings = 0, float $deductions = 0): static
    {
        $grossSalary = $basicSalary + $earnings;
        $taxAmount = $grossSalary * 0.05;
        $netSalary = $grossSalary - $deductions - $taxAmount;

        return $this->state(fn (array $attributes) => [
            'basic_salary' => $basicSalary,
            'total_earnings' => $earnings,
            'total_deductions' => $deductions,
            'gross_salary' => $grossSalary,
            'tax_amount' => $taxAmount,
            'net_salary' => $netSalary,
        ]);
    }

    /**
     * Configure with perfect attendance.
     */
    public function perfectAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'working_days' => 22,
            'present_days' => 22,
            'absent_days' => 0,
            'late_days' => 0,
            'leave_days' => 0,
        ]);
    }

    /**
     * Configure with transfer payment.
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
}
