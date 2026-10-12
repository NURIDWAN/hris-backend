<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\EmployeeExit>
 */
class EmployeeExitFactory extends Factory
{
    use GeneratesRandomData;

    public function definition(): array
    {
        $requestDate = $this->randomDateBetween('-30 days', 'now');
        $lastWorkingDay = $this->randomDateBetween($requestDate, '+60 days');
        $effectiveDate = $this->randomDateBetween($lastWorkingDay, '+90 days');

        return [
            'company_id' => Company::factory(),
            'employee_id' => Employee::factory(),
            'exit_type' => $this->randomElement(['resignation', 'termination', 'retirement', 'contract_end', 'mutual_agreement']),
            'request_date' => $requestDate,
            'last_working_day' => $lastWorkingDay,
            'effective_date' => $effectiveDate,
            'reason' => $this->randomSentence(),
            'notes' => $this->randomBoolean() ? $this->randomParagraph() : null,
            'status' => 'pending',
            'assets_returned' => false,
            'knowledge_transfer_done' => false,
            'final_settlement_done' => false,
            'exit_interview_done' => false,
            'is_rehire_eligible' => true,
        ];
    }

    public function resignation(): static
    {
        return $this->state(fn (array $attributes) => [
            'exit_type' => 'resignation',
        ]);
    }

    public function termination(): static
    {
        return $this->state(fn (array $attributes) => [
            'exit_type' => 'termination',
            'is_rehire_eligible' => false,
        ]);
    }

    public function retirement(): static
    {
        return $this->state(fn (array $attributes) => [
            'exit_type' => 'retirement',
        ]);
    }

    public function contractEnd(): static
    {
        return $this->state(fn (array $attributes) => [
            'exit_type' => 'contract_end',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'approved_by' => User::factory(),
            'approved_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'assets_returned' => true,
            'knowledge_transfer_done' => true,
            'final_settlement_done' => true,
            'exit_interview_done' => true,
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'approved_by' => User::factory(),
            'approved_at' => now(),
            'approval_notes' => $this->randomSentence(),
        ]);
    }

    public function withChecklist(): static
    {
        return $this->state(fn (array $attributes) => [
            'assets_returned' => true,
            'knowledge_transfer_done' => true,
            'final_settlement_done' => true,
            'exit_interview_done' => true,
        ]);
    }
}
