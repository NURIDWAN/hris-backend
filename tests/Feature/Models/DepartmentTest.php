<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Department Model', function () {

    it('can create a department', function () {
        $company = Company::factory()->create();

        $department = Department::create([
            'company_id' => $company->id,
            'name' => 'Human Resources',
            'code' => 'HR',
            'description' => 'Handles employee management',
        ]);

        expect($department)->toBeInstanceOf(Department::class)
            ->and($department->name)->toBe('Human Resources')
            ->and($department->code)->toBe('HR')
            ->and($department->company_id)->toBe($company->id);
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);

        expect($department->company)->toBeInstanceOf(Company::class)
            ->and($department->company->id)->toBe($company->id);
    });

    it('has many employees', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        Employee::factory()->count(3)->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($department->employees)->toHaveCount(3);
    });

    it('can have a parent department', function () {
        $company = Company::factory()->create();
        $parentDepartment = Department::factory()->create([
            'company_id' => $company->id,
            'name' => 'Operations',
        ]);

        $childDepartment = Department::factory()->create([
            'company_id' => $company->id,
            'parent_id' => $parentDepartment->id,
            'name' => 'IT Operations',
        ]);

        expect($childDepartment->parent)->toBeInstanceOf(Department::class)
            ->and($childDepartment->parent->id)->toBe($parentDepartment->id);
    });

    it('can have child departments', function () {
        $company = Company::factory()->create();
        $parentDepartment = Department::factory()->create([
            'company_id' => $company->id,
        ]);

        Department::factory()->count(2)->create([
            'company_id' => $company->id,
            'parent_id' => $parentDepartment->id,
        ]);

        expect($parentDepartment->children)->toHaveCount(2);
    });

    it('can be soft deleted', function () {
        $department = Department::factory()->create();

        $department->delete();

        expect($department->trashed())->toBeTrue()
            ->and(Department::count())->toBe(0)
            ->and(Department::withTrashed()->count())->toBe(1);
    });

    it('has unique code per company', function () {
        $company = Company::factory()->create();

        Department::factory()->create([
            'company_id' => $company->id,
            'code' => 'HR',
        ]);

        // Same code different company should work
        $company2 = Company::factory()->create();
        $dept2 = Department::factory()->create([
            'company_id' => $company2->id,
            'code' => 'HR',
        ]);

        expect($dept2->code)->toBe('HR');
    });

    it('can check if has employees', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        expect($department->hasEmployees())->toBeFalse();

        Employee::factory()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        expect($department->fresh()->hasEmployees())->toBeTrue();
    });

    it('can count employees', function () {
        $company = Company::factory()->create();
        $department = Department::factory()->create(['company_id' => $company->id]);
        $position = Position::factory()->create(['company_id' => $company->id]);

        Employee::factory()->count(5)->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);

        // Refresh to get the count
        $department = Department::withCount('employees')->find($department->id);
        expect($department->employees_count)->toBe(5);
    });

});
