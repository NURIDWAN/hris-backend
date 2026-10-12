<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = Department::class;

    public function definition(): array
    {
        $departments = [
            ['name' => 'Human Resources', 'code' => 'HR'],
            ['name' => 'Finance', 'code' => 'FIN'],
            ['name' => 'Information Technology', 'code' => 'IT'],
            ['name' => 'Marketing', 'code' => 'MKT'],
            ['name' => 'Sales', 'code' => 'SLS'],
            ['name' => 'Operations', 'code' => 'OPS'],
            ['name' => 'Customer Service', 'code' => 'CS'],
            ['name' => 'Research & Development', 'code' => 'RND'],
        ];

        $dept = $this->randomElement($departments);

        return [
            'company_id' => Company::factory(),
            'parent_id' => null,
            'name' => $dept['name'],
            'code' => $dept['code'].$this->randomNumber(3),
            'description' => $this->randomSentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forCompany(Company $company): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $company->id,
        ]);
    }

    public function withParent(Department $parent): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $parent->company_id,
            'parent_id' => $parent->id,
        ]);
    }
}
