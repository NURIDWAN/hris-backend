<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Employee Model', function () {

    it('can create an employee', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        $employee = Employee::create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employee_id' => 'EMP001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@company.com',
            'phone' => '08123456789',
            'date_of_birth' => '1990-01-15',
            'gender' => 'male',
            'marital_status' => 'single',
            'address' => 'Jl. Sudirman No. 123',
            'city' => 'Jakarta',
            'hire_date' => '2023-01-01',
            'employment_status' => 'permanent',
            'base_salary' => 10000000,
        ]);

        expect($employee)->toBeInstanceOf(Employee::class)
            ->and($employee->employee_id)->toBe('EMP001')
            ->and($employee->first_name)->toBe('John')
            ->and($employee->last_name)->toBe('Doe')
            ->and($employee->full_name)->toBe('John Doe');
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($employee->company)->toBeInstanceOf(Company::class);
    });

    it('belongs to a department', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($employee->department)->toBeInstanceOf(Department::class);
    });

    it('belongs to a position', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($employee->position)->toBeInstanceOf(Position::class);
    });

    it('can be linked to a user account', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $user->id,
        ]);

        expect($employee->user)->toBeInstanceOf(User::class)
            ->and($employee->user->id)->toBe($user->id);
    });

    it('generates unique employee id', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        $emp1 = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $emp2 = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($emp1->employee_id)->not->toBe($emp2->employee_id);
    });

    it('can be soft deleted', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        $employee->delete();

        expect($employee->trashed())->toBeTrue()
            ->and(Employee::count())->toBe(0)
            ->and(Employee::withTrashed()->count())->toBe(1);
    });

    it('calculates years of service', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'hire_date' => now()->subYears(3)->subMonths(6),
        ]);

        expect($employee->years_of_service)->toBe(3);
    });

    it('has full name attribute', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
        ]);

        expect($employee->full_name)->toBe('Jane Smith');
    });

    it('can calculate age', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_of_birth' => now()->subYears(30)->subMonths(2),
        ]);

        expect($employee->age)->toBe(30);
    });

    it('has employment status', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        $permanent = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'permanent',
        ]);
        $contract = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'contract',
        ]);
        $probation = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'probation',
        ]);

        expect($permanent->employment_status)->toBe('permanent')
            ->and($contract->employment_status)->toBe('contract')
            ->and($probation->employment_status)->toBe('probation');
    });

    it('can have contract end date for contract employees', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'contract',
            'contract_end_date' => now()->addYear(),
        ]);

        expect($employee->contract_end_date)->not->toBeNull()
            ->and($employee->isContractExpiring())->toBeFalse();
    });

    it('can check if contract is expiring soon', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        $expiringEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'contract',
            'contract_end_date' => now()->addDays(20),
        ]);

        $safeEmployee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'employment_status' => 'contract',
            'contract_end_date' => now()->addMonths(3),
        ]);

        expect($expiringEmployee->isContractExpiring())->toBeTrue()
            ->and($safeEmployee->isContractExpiring())->toBeFalse();
    });

    it('can have bank account info', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'John Doe',
        ]);

        expect($employee->bank_name)->toBe('BCA')
            ->and($employee->bank_account_number)->toBe('1234567890')
            ->and($employee->bank_account_name)->toBe('John Doe');
    });

    it('can have tax info (NPWP)', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'npwp' => '12.345.678.9-012.345',
            'tax_status' => 'TK/0',
        ]);

        expect($employee->npwp)->toBe('12.345.678.9-012.345')
            ->and($employee->tax_status)->toBe('TK/0');
    });

    it('can have BPJS info', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'bpjs_kesehatan' => '0001234567890',
            'bpjs_ketenagakerjaan' => '0009876543210',
        ]);

        expect($employee->bpjs_kesehatan)->toBe('0001234567890')
            ->and($employee->bpjs_ketenagakerjaan)->toBe('0009876543210');
    });

    it('is active by default', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($employee->is_active)->toBeTrue();
    });

    it('can be deactivated', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);
        $employee = Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $employee->update(['is_active' => false]);

        expect($employee->fresh()->is_active)->toBeFalse();
    });

    it('can scope active employees', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        Employee::factory()->count(3)->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_active' => true,
        ]);
        Employee::factory()->count(2)->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'is_active' => false,
        ]);

        expect(Employee::active()->count())->toBe(3);
    });

});
