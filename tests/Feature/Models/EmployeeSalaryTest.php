<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
});

describe('EmployeeSalary Model', function () {
    test('can create employee salary', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_salary' => 10000000,
        ]);

        expect($salary)->toBeInstanceOf(EmployeeSalary::class);
        expect($salary->basic_salary)->toBe('10000000.00');
    });

    test('belongs to company', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($salary->company)->toBeInstanceOf(Company::class);
        expect($salary->company->id)->toBe($this->company->id);
    });

    test('belongs to employee', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($salary->employee)->toBeInstanceOf(Employee::class);
        expect($salary->employee->id)->toBe($this->employee->id);
    });

    test('has many components', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        $component = SalaryComponent::factory()->create(['company_id' => $this->company->id]);

        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $component->id,
            'amount' => 500000,
        ]);

        expect($salary->components)->toHaveCount(1);
    });
});

describe('EmployeeSalary Scopes', function () {
    test('active scope filters active salaries', function () {
        EmployeeSalary::factory()->active()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeSalary::factory()->inactive()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $active = EmployeeSalary::where('company_id', $this->company->id)->active()->get();

        expect($active)->toHaveCount(2);
        expect($active->every(fn ($s) => $s->is_active))->toBeTrue();
    });

    test('effectiveOn scope filters by date', function () {
        $currentSalary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'effective_date' => now()->subMonth(),
            'end_date' => null,
        ]);
        $expiredSalary = EmployeeSalary::factory()->ended()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'effective_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);

        $effective = EmployeeSalary::where('company_id', $this->company->id)
            ->effectiveOn(now())
            ->get();

        expect($effective)->toHaveCount(1);
        expect($effective->first()->id)->toBe($currentSalary->id);
    });

    test('forEmployee scope filters by employee', function () {
        $employee2 = Employee::factory()->create(['company_id' => $this->company->id]);

        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee2->id,
        ]);

        $salaries = EmployeeSalary::forEmployee($this->employee->id)->get();

        expect($salaries)->toHaveCount(1);
        expect($salaries->first()->employee_id)->toBe($this->employee->id);
    });
});

describe('EmployeeSalary Helpers', function () {
    test('isEffective returns true for current salary', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'effective_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        expect($salary->isEffective())->toBeTrue();
    });

    test('isEffective returns false for expired salary', function () {
        $salary = EmployeeSalary::factory()->ended()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'effective_date' => now()->subYear(),
            'end_date' => now()->subMonth(),
        ]);

        expect($salary->isEffective())->toBeFalse();
    });

    test('getTotalEarnings calculates sum of earning components', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $earning1 = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);
        $earning2 = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);

        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $earning1->id,
            'amount' => 500000,
        ]);
        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $earning2->id,
            'amount' => 300000,
        ]);

        expect($salary->getTotalEarnings())->toBe(800000.0);
    });

    test('getTotalDeductions calculates sum of deduction components', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $deduction1 = SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);
        $deduction2 = SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);

        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $deduction1->id,
            'amount' => 100000,
        ]);
        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $deduction2->id,
            'amount' => 50000,
        ]);

        expect($salary->getTotalDeductions())->toBe(150000.0);
    });

    test('getGrossSalary returns basic plus earnings', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_salary' => 10000000,
        ]);

        $earning = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);
        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $earning->id,
            'amount' => 500000,
        ]);

        expect($salary->getGrossSalary())->toBe(10500000.0);
    });

    test('getNetSalary returns gross minus deductions', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_salary' => 10000000,
        ]);

        $earning = SalaryComponent::factory()->earning()->create(['company_id' => $this->company->id]);
        $deduction = SalaryComponent::factory()->deduction()->create(['company_id' => $this->company->id]);

        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $earning->id,
            'amount' => 500000,
        ]);
        EmployeeSalaryComponent::create([
            'employee_salary_id' => $salary->id,
            'salary_component_id' => $deduction->id,
            'amount' => 200000,
        ]);

        // Net = Gross - Deductions = (10000000 + 500000) - 200000 = 10300000
        expect($salary->getNetSalary())->toBe(10300000.0);
    });
});

describe('EmployeeSalary Accessors', function () {
    test('formatted_basic_salary returns formatted currency', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_salary' => 10500000,
        ]);

        expect($salary->formatted_basic_salary)->toBe('Rp 10.500.000');
    });

    test('payment_method_label returns correct label', function () {
        $transferSalary = EmployeeSalary::factory()->transfer()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);
        $cashSalary = EmployeeSalary::factory()->cash()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($transferSalary->payment_method_label)->toBe('Transfer Bank');
        expect($cashSalary->payment_method_label)->toBe('Tunai');
    });

    test('bank_info returns formatted bank info for transfer', function () {
        $salary = EmployeeSalary::factory()->transfer()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'John Doe',
        ]);

        expect($salary->bank_info)->toBe('BCA - 1234567890 (John Doe)');
    });

    test('bank_info returns null for cash payment', function () {
        $salary = EmployeeSalary::factory()->cash()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        expect($salary->bank_info)->toBeNull();
    });
});

describe('EmployeeSalary Soft Deletes', function () {
    test('can soft delete employee salary', function () {
        $salary = EmployeeSalary::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $salary->delete();

        $this->assertSoftDeleted('employee_salaries', ['id' => $salary->id]);
        expect(EmployeeSalary::withTrashed()->find($salary->id))->not->toBeNull();
    });
});
