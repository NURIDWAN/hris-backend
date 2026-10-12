<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('EmployeeSalary Index', function () {
    test('can view employee salaries list', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $salaries = EmployeeSalary::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->get(route('employee-salaries.index'));

        $response->assertStatus(200);
        $response->assertViewIs('employee-salaries.index');
        $response->assertViewHas('employeeSalaries');
    });

    test('only shows employee salaries from same company', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $ownSalary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);
        $otherSalary = EmployeeSalary::factory()->create();

        $response = $this->get(route('employee-salaries.index'));

        $response->assertStatus(200);
        $response->assertViewHas('employeeSalaries', function ($salaries) use ($ownSalary, $otherSalary) {
            return $salaries->contains($ownSalary) && ! $salaries->contains($otherSalary);
        });
    });

    test('can search employee salaries by employee name', function () {
        $employee1 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $employee2 = Employee::factory()->create([
            'company_id' => $this->company->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);
        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee1->id,
        ]);
        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee2->id,
        ]);

        $response = $this->get(route('employee-salaries.index', ['search' => 'John']));

        $response->assertStatus(200);
        $response->assertViewHas('employeeSalaries', function ($salaries) {
            return $salaries->count() === 1;
        });
    });

    test('can filter by active status', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        EmployeeSalary::factory()->active()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);
        EmployeeSalary::factory()->inactive()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->get(route('employee-salaries.index', ['status' => 'active']));

        $response->assertStatus(200);
        $response->assertViewHas('employeeSalaries', function ($salaries) {
            return $salaries->every(fn ($s) => $s->is_active === true);
        });
    });
});

describe('EmployeeSalary Create', function () {
    test('can view create form', function () {
        $response = $this->get(route('employee-salaries.create'));

        $response->assertStatus(200);
        $response->assertViewIs('employee-salaries.create');
        $response->assertViewHas('employees');
        $response->assertViewHas('salaryComponents');
    });

    test('can create employee salary', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);

        $data = [
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'John Doe',
            'is_active' => true,
        ];

        $response = $this->post(route('employee-salaries.store'), $data);

        $response->assertRedirect(route('employee-salaries.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employee_salaries', [
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
        ]);
    });

    test('can create employee salary with components', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $component = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);

        $data = [
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'is_active' => true,
            'components' => [
                $component->id => ['amount' => 500000],
            ],
        ];

        $response = $this->post(route('employee-salaries.store'), $data);

        $response->assertRedirect(route('employee-salaries.index'));

        $salary = EmployeeSalary::where('employee_id', $employee->id)->first();
        expect($salary->components)->toHaveCount(1);
        expect((float) $salary->components->first()->amount)->toBe(500000.0);
    });

    test('auto creates BASIC component from basic_salary field', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $basicComponent = SalaryComponent::factory()->earning()->create([
            'company_id' => $this->company->id,
            'code' => 'BASIC',
            'name' => 'Gaji Pokok',
        ]);

        $data = [
            'employee_id' => $employee->id,
            'basic_salary' => 8000000,
            'effective_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'is_active' => true,
        ];

        $this->post(route('employee-salaries.store'), $data);

        $salary = EmployeeSalary::where('employee_id', $employee->id)->first();
        $basic = $salary->components->where('salary_component_id', $basicComponent->id)->first();
        expect($basic)->not->toBeNull();
        expect((float) $basic->amount)->toBe(8000000.0);
    });

    test('employee_id is required', function () {
        $data = EmployeeSalary::factory()->make(['employee_id' => null])->toArray();

        $response = $this->post(route('employee-salaries.store'), $data);

        $response->assertSessionHasErrors('employee_id');
    });

    test('basic_salary is required', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $data = [
            'employee_id' => $employee->id,
            'basic_salary' => null,
            'effective_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'is_active' => true,
        ];

        $response = $this->post(route('employee-salaries.store'), $data);

        $response->assertSessionHasErrors('basic_salary');
    });

    test('bank info required when payment_method is transfer', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $data = [
            'employee_id' => $employee->id,
            'basic_salary' => 10000000,
            'effective_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'bank_name' => null,
            'bank_account_number' => null,
            'is_active' => true,
        ];

        $response = $this->post(route('employee-salaries.store'), $data);

        $response->assertSessionHasErrors(['bank_name', 'bank_account_number']);
    });
});

describe('EmployeeSalary Show', function () {
    test('can view employee salary details', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->get(route('employee-salaries.show', $salary));

        $response->assertStatus(200);
        $response->assertViewIs('employee-salaries.show');
        $response->assertViewHas('employeeSalary', $salary);
    });

    test('cannot view employee salary from other company', function () {
        $otherSalary = EmployeeSalary::factory()->create();

        $response = $this->get(route('employee-salaries.show', $otherSalary));

        $response->assertStatus(404);
    });
});

describe('EmployeeSalary Edit', function () {
    test('can view edit form', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->get(route('employee-salaries.edit', $salary));

        $response->assertStatus(200);
        $response->assertViewIs('employee-salaries.edit');
        $response->assertViewHas('employeeSalary', $salary);
    });

    test('can update employee salary', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $data = [
            'employee_id' => $employee->id,
            'basic_salary' => 15000000,
            'effective_date' => now()->toDateString(),
            'payment_method' => 'transfer',
            'bank_name' => 'Mandiri',
            'bank_account_number' => '9876543210',
            'bank_account_name' => 'John Updated',
            'is_active' => true,
        ];

        $response = $this->put(route('employee-salaries.update', $salary), $data);

        $response->assertRedirect(route('employee-salaries.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('employee_salaries', [
            'id' => $salary->id,
            'basic_salary' => 15000000,
            'bank_name' => 'Mandiri',
        ]);
    });

    test('cannot update employee salary from other company', function () {
        $otherSalary = EmployeeSalary::factory()->create();

        $response = $this->put(route('employee-salaries.update', $otherSalary), [
            'basic_salary' => 99999999,
        ]);

        $response->assertStatus(404);
    });
});

describe('EmployeeSalary Delete', function () {
    test('can delete employee salary', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
        ]);

        $response = $this->delete(route('employee-salaries.destroy', $salary));

        $response->assertRedirect(route('employee-salaries.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('employee_salaries', ['id' => $salary->id]);
    });

    test('cannot delete employee salary from other company', function () {
        $otherSalary = EmployeeSalary::factory()->create();

        $response = $this->delete(route('employee-salaries.destroy', $otherSalary));

        $response->assertStatus(404);
    });
});
