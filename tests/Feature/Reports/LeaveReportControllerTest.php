<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('LeaveReportController', function () {
    describe('index', function () {
        it('displays leave report page', function () {
            $response = $this->get(route('reports.leave'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.leave.index');
        });

        it('shows leave summary statistics', function () {
            $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            LeaveRequest::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'status' => 'approved',
            ]);

            LeaveRequest::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'status' => 'pending',
            ]);

            $response = $this->get(route('reports.leave'));

            $response->assertStatus(200);
            $response->assertViewHas('summary');
        });

        it('can filter by date range', function () {
            $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            LeaveRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => now()->subDays(5),
                'end_date' => now()->subDays(3),
            ]);

            $response = $this->get(route('reports.leave', [
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]));

            $response->assertStatus(200);
        });

        it('can filter by leave type', function () {
            $leaveType1 = LeaveType::factory()->create(['company_id' => $this->company->id, 'name' => 'Cuti Tahunan']);
            $leaveType2 = LeaveType::factory()->create(['company_id' => $this->company->id, 'name' => 'Cuti Sakit']);
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            LeaveRequest::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType1->id,
            ]);

            LeaveRequest::factory()->count(2)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType2->id,
            ]);

            $response = $this->get(route('reports.leave', ['leave_type_id' => $leaveType1->id]));

            $response->assertStatus(200);
        });
    });

    describe('balance', function () {
        it('shows leave balance report', function () {
            $leaveType = LeaveType::factory()->create([
                'company_id' => $this->company->id,
                'default_days' => 12,
            ]);

            $employees = Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            foreach ($employees as $employee) {
                LeaveRequest::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'status' => 'approved',
                    'total_days' => 2,
                ]);
            }

            $response = $this->get(route('reports.leave.balance'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.leave.balance');
            $response->assertViewHas('employees');
        });

        it('can filter by department', function () {
            $department = Department::factory()->create(['company_id' => $this->company->id]);
            $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'department_id' => $department->id,
            ]);

            $response = $this->get(route('reports.leave.balance', ['department_id' => $department->id]));

            $response->assertStatus(200);
        });
    });

    describe('by type', function () {
        it('shows leave usage by type', function () {
            $leaveType1 = LeaveType::factory()->create(['company_id' => $this->company->id, 'name' => 'Cuti Tahunan']);
            $leaveType2 = LeaveType::factory()->create(['company_id' => $this->company->id, 'name' => 'Cuti Sakit']);
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            LeaveRequest::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType1->id,
                'status' => 'approved',
            ]);

            LeaveRequest::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType2->id,
                'status' => 'approved',
            ]);

            $response = $this->get(route('reports.leave.by-type'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.leave.by-type');
            $response->assertViewHas('leaveTypes');
        });
    });

    describe('export', function () {
        it('can export leave report to excel', function () {
            $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            LeaveRequest::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
            ]);

            $response = $this->get(route('reports.leave.export', ['format' => 'excel']));

            $response->assertStatus(200);
            $response->assertDownload();
        });

        it('can export leave report to pdf', function () {
            $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
            $employee = Employee::factory()->create(['company_id' => $this->company->id]);

            LeaveRequest::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
            ]);

            $response = $this->get(route('reports.leave.export', ['format' => 'pdf']));

            $response->assertStatus(200);
        });
    });
});
