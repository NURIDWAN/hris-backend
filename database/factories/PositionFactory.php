<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Position>
 */
class PositionFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = Position::class;

    public function definition(): array
    {
        $positions = [
            ['name' => 'Staff', 'level' => 1, 'salary' => 5000000],
            ['name' => 'Senior Staff', 'level' => 2, 'salary' => 7000000],
            ['name' => 'Supervisor', 'level' => 3, 'salary' => 10000000],
            ['name' => 'Assistant Manager', 'level' => 4, 'salary' => 12000000],
            ['name' => 'Manager', 'level' => 5, 'salary' => 15000000],
            ['name' => 'Senior Manager', 'level' => 6, 'salary' => 20000000],
            ['name' => 'Director', 'level' => 7, 'salary' => 30000000],
        ];

        $pos = $this->randomElement($positions);

        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'name' => $pos['name'],
            'code' => strtoupper(substr($pos['name'], 0, 3)).$this->randomNumber(3),
            'description' => $this->randomSentence(),
            'level' => $pos['level'],
            'base_salary' => $pos['salary'],
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

    public function forDepartment(Department $department): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $department->company_id,
            'department_id' => $department->id,
        ]);
    }
}
