<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Database\Factories\Concerns\GeneratesRandomData;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    use GeneratesRandomData;

    protected $model = Employee::class;

    public function definition(): array
    {
        $gender = $this->randomElement(['male', 'female']);
        $firstName = $this->randomFirstName();
        $lastName = $this->randomLastName();
        $hireDate = $this->randomDateBetween('-5 years', 'now');

        return [
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'manager_id' => null,
            'user_id' => null,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $this->uniqueEmail(strtolower($firstName)),
            'phone' => $this->randomPhone(),
            'date_of_birth' => $this->randomDateBetween('-50 years', '-20 years'),
            'gender' => $gender,
            'marital_status' => $this->randomElement(['single', 'married', 'divorced', 'widowed']),
            'religion' => $this->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu']),
            'blood_type' => $this->randomElement(['A', 'B', 'AB', 'O']),
            'identity_number' => $this->randomNik(),
            'address' => $this->randomAddress(),
            'city' => $this->randomCity(),
            'province' => $this->randomElement(['DKI Jakarta', 'Jawa Barat', 'Jawa Tengah', 'Jawa Timur', 'Banten']),
            'postal_code' => (string) rand(10000, 99999),
            'hire_date' => $hireDate,
            'employment_status' => $this->randomElement(['permanent', 'contract', 'probation']),
            'base_salary' => $this->randomElement([5000000, 7000000, 10000000, 12000000, 15000000]),
            'bank_name' => $this->randomBankName(),
            'bank_account_number' => $this->randomBankAccount(),
            'bank_account_name' => $firstName.' '.$lastName,
            'npwp' => $this->randomNpwp(),
            'tax_status' => $this->randomElement(['TK/0', 'TK/1', 'K/0', 'K/1', 'K/2', 'K/3']),
            'bpjs_kesehatan' => $this->numerify('#############'),
            'bpjs_ketenagakerjaan' => $this->numerify('#############'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'permanent',
            'contract_start_date' => null,
            'contract_end_date' => null,
        ]);
    }

    public function contract(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'contract',
            'contract_start_date' => now()->subMonths(6),
            'contract_end_date' => now()->addMonths(6),
        ]);
    }

    public function probation(): static
    {
        return $this->state(fn (array $attributes) => [
            'employment_status' => 'probation',
            'hire_date' => now()->subMonths(2),
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

    public function forPosition(Position $position): static
    {
        return $this->state(fn (array $attributes) => [
            'company_id' => $position->company_id,
            'position_id' => $position->id,
            'base_salary' => $position->base_salary,
        ]);
    }

    public function withManager(Employee $manager): static
    {
        return $this->state(fn (array $attributes) => [
            'manager_id' => $manager->id,
        ]);
    }
}
