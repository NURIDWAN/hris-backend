<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('EmployeeReportController', function () {
    describe('index', function () {
        it('displays employee report page', function () {
            $response = $this->get(route('reports.employees'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.employees.index');
        });

        it('shows all employees in the report', function () {
            Employee::factory()->count(10)->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('reports.employees'));

            $response->assertStatus(200);
            $response->assertViewHas('employees');
            expect($response->viewData('employees'))->toHaveCount(10);
        });

        it('can filter by department', function () {
            $department1 = Department::factory()->create(['company_id' => $this->company->id]);
            $department2 = Department::factory()->create(['company_id' => $this->company->id]);

            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'department_id' => $department1->id,
            ]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'department_id' => $department2->id,
            ]);

            $response = $this->get(route('reports.employees', ['department_id' => $department1->id]));

            $response->assertStatus(200);
            expect($response->viewData('employees'))->toHaveCount(5);
        });

        it('can filter by employment status', function () {
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employment_status' => 'permanent',
            ]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employment_status' => 'contract',
            ]);

            $response = $this->get(route('reports.employees', ['employment_status' => 'permanent']));

            $response->assertStatus(200);
            expect($response->viewData('employees'))->toHaveCount(5);
        });

        it('can filter by active status', function () {
            Employee::factory()->count(7)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'is_active' => false,
            ]);

            $response = $this->get(route('reports.employees', ['is_active' => '1']));

            $response->assertStatus(200);
            expect($response->viewData('employees'))->toHaveCount(7);
        });

        it('shows summary statistics', function () {
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employment_status' => 'permanent',
                'is_active' => true,
            ]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employment_status' => 'contract',
                'is_active' => true,
            ]);

            Employee::factory()->count(2)->create([
                'company_id' => $this->company->id,
                'employment_status' => 'probation',
                'is_active' => false,
            ]);

            $response = $this->get(route('reports.employees'));

            $response->assertStatus(200);
            $summary = $response->viewData('summary');
            expect($summary['total'])->toBe(10);
            expect($summary['active'])->toBe(8);
            expect($summary['inactive'])->toBe(2);
            expect($summary['permanent'])->toBe(5);
            expect($summary['contract'])->toBe(3);
            expect($summary['probation'])->toBe(2);
        });

        it('only shows employees from the same company', function () {
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
            ]);

            $otherCompany = Company::factory()->create();
            Employee::factory()->count(10)->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->get(route('reports.employees'));

            $response->assertStatus(200);
            expect($response->viewData('employees'))->toHaveCount(5);
        });
    });

    describe('export', function () {
        it('can export employees to excel', function () {
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('reports.employees.export', ['format' => 'excel']));

            $response->assertStatus(200);
            $response->assertDownload();
        });

        it('can export employees to pdf', function () {
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('reports.employees.export', ['format' => 'pdf']));

            $response->assertStatus(200);
        });

        it('applies filters when exporting', function () {
            $department = Department::factory()->create(['company_id' => $this->company->id]);

            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'department_id' => $department->id,
            ]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
            ]);

            $response = $this->get(route('reports.employees.export', [
                'format' => 'excel',
                'department_id' => $department->id,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('by department', function () {
        it('shows employee count by department', function () {
            $department1 = Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'IT',
            ]);
            $department2 = Department::factory()->create([
                'company_id' => $this->company->id,
                'name' => 'HR',
            ]);

            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'department_id' => $department1->id,
            ]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'department_id' => $department2->id,
            ]);

            $response = $this->get(route('reports.employees.by-department'));

            $response->assertStatus(200);
            $response->assertViewHas('departments');
            $departments = $response->viewData('departments');
            expect($departments)->toHaveCount(2);
        });
    });
});
