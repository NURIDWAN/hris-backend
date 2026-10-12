<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('PayrollReportController', function () {
    describe('index', function () {
        it('displays payroll report page', function () {
            $response = $this->get(route('reports.payroll'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.payroll.index');
        });

        it('shows payroll summary statistics', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'paid',
            ]);

            $employees = Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
            ]);

            foreach ($employees as $employee) {
                PayrollItem::factory()->create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                ]);
            }

            $response = $this->get(route('reports.payroll'));

            $response->assertStatus(200);
            $response->assertViewHas('summary');
        });

        it('can filter by period', function () {
            Payroll::factory()->create([
                'company_id' => $this->company->id,
                'period_year' => 2024,
                'period_month' => 6,
            ]);

            Payroll::factory()->create([
                'company_id' => $this->company->id,
                'period_year' => 2024,
                'period_month' => 7,
            ]);

            $response = $this->get(route('reports.payroll', [
                'year' => 2024,
                'month' => 6,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('by department', function () {
        it('shows payroll report by department', function () {
            $department1 = Department::factory()->create(['company_id' => $this->company->id]);
            $department2 = Department::factory()->create(['company_id' => $this->company->id]);

            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'paid',
            ]);

            $employee1 = Employee::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $department1->id,
            ]);

            $employee2 = Employee::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $department2->id,
            ]);

            PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee1->id,
                'net_salary' => 5000000,
            ]);

            PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee2->id,
                'net_salary' => 6000000,
            ]);

            $response = $this->get(route('reports.payroll.by-department'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.payroll.by-department');
            $response->assertViewHas('departments');
        });
    });

    describe('tax summary', function () {
        it('shows tax summary report', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'status' => 'paid',
            ]);

            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
                'total_deductions' => 500000,
            ]);

            $response = $this->get(route('reports.payroll.tax-summary'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.payroll.tax-summary');
        });
    });

    describe('export', function () {
        it('can export payroll report to excel', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('reports.payroll.export', ['format' => 'excel']));

            $response->assertStatus(200);
            $response->assertDownload();
        });

        it('can export payroll report to pdf', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            PayrollItem::factory()->create([
                'payroll_id' => $payroll->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('reports.payroll.export', ['format' => 'pdf']));

            $response->assertStatus(200);
        });
    });
});
