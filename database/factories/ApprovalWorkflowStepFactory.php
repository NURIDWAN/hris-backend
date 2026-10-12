<?php

namespace Database\Factories;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalWorkflowStepFactory extends Factory
{
    protected $model = ApprovalWorkflowStep::class;

    public function definition(): array
    {
        return [
            'approval_workflow_id' => ApprovalWorkflow::factory(),
            'step_order' => 1,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
            'approver_user_id' => null,
            'can_skip' => false,
            'is_active' => true,
        ];
    }

    public function roleApprover(string $role = 'hr-manager'): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => $role,
            'approver_user_id' => null,
        ]);
    }

    public function specificUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER,
            'approver_role' => null,
            'approver_user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function departmentHead(): static
    {
        return $this->state(fn (array $attributes) => [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
            'approver_role' => null,
            'approver_user_id' => null,
        ]);
    }

    public function skippable(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_skip' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
