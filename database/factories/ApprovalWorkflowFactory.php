<?php

namespace Database\Factories;

use App\Models\ApprovalWorkflow;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class ApprovalWorkflowFactory extends Factory
{
    protected $model = ApprovalWorkflow::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'type' => $this->faker->randomElement(array_keys(ApprovalWorkflow::TYPES)),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function leaveRequest(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Alur Persetujuan Cuti',
        ]);
    }

    public function overtime(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ApprovalWorkflow::TYPE_OVERTIME,
            'name' => 'Alur Persetujuan Lembur',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
