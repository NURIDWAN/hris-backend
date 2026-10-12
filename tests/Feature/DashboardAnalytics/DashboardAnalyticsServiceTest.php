<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Position;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\DashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->position = Position::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
    ]);

    $this->service = new DashboardAnalyticsService;
});

describe('DashboardAnalytics - Attendance Chart Data', function () {
    it('returns attendance data for last 30 days', function () {
        // Create employees
        $employees = Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'is_active' => true,
        ]);

        // Create attendances for last 7 days
        foreach ($employees as $employee) {
            for ($i = 0; $i < 7; $i++) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => now()->subDays($i),
                    'clock_in' => now()->subDays($i)->setTime(8, 0),
                    'clock_in_status' => $i % 3 === 0 ? 'late' : 'on_time',
                ]);
            }
        }

        $data = $this->service->getAttendanceChartData($this->company->id, 30);

        expect($data)->toBeArray();
        expect($data)->toHaveKeys(['labels', 'datasets']);
        expect($data['labels'])->toBeArray();
        expect($data['labels'])->toHaveCount(30);
        expect($data['datasets'])->toHaveKeys(['present', 'late', 'absent']);
    });

    it('returns zero values for days with no attendance', function () {
        $data = $this->service->getAttendanceChartData($this->company->id, 7);

        expect($data['datasets']['present'])->each->toBe(0);
        expect($data['datasets']['late'])->each->toBe(0);
    });
});

describe('DashboardAnalytics - Employee by Department', function () {
    it('returns employee count per department', function () {
        $dept1 = Department::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'IT',
        ]);
        $dept2 = Department::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'HR',
        ]);

        Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $dept1->id,
            'is_active' => true,
        ]);

        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $dept2->id,
            'is_active' => true,
        ]);

        $data = $this->service->getEmployeeByDepartmentData($this->company->id);

        expect($data)->toBeArray();
        expect($data)->toHaveKeys(['labels', 'data', 'colors']);
        expect($data['labels'])->toContain('IT', 'HR');
        expect(array_sum($data['data']))->toBe(8);
    });

    it('excludes inactive employees', function () {
        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'is_active' => false,
        ]);

        $data = $this->service->getEmployeeByDepartmentData($this->company->id);

        expect(array_sum($data['data']))->toBe(3);
    });
});

describe('DashboardAnalytics - Payroll Trend', function () {
    it('returns payroll data for last 6 months', function () {
        // Create payrolls for last 6 months
        for ($i = 0; $i < 6; $i++) {
            $date = now()->subMonths($i);
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'period_month' => $date->month,
                'period_year' => $date->year,
                'status' => 'paid',
            ]);

            PayrollItem::factory()->count(3)->create([
                'payroll_id' => $payroll->id,
                'net_salary' => 5000000,
            ]);
        }

        $data = $this->service->getPayrollTrendData($this->company->id, 6);

        expect($data)->toBeArray();
        expect($data)->toHaveKeys(['labels', 'data']);
        expect($data['labels'])->toHaveCount(6);
        expect($data['data'])->toHaveCount(6);
    });

    it('returns zero for months with no payroll', function () {
        $data = $this->service->getPayrollTrendData($this->company->id, 6);

        foreach ($data['data'] as $value) {
            expect($value)->toBe(0.0);
        }
    });
});

describe('DashboardAnalytics - Employee Status Distribution', function () {
    it('returns employee count by employment status', function () {
        Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'employment_status' => 'contract',
            'is_active' => true,
        ]);

        Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'employment_status' => 'probation',
            'is_active' => true,
        ]);

        $data = $this->service->getEmployeeStatusDistribution($this->company->id);

        expect($data)->toBeArray();
        expect($data)->toHaveKeys(['labels', 'data', 'colors']);
        expect(array_sum($data['data']))->toBe(10);
    });
});

describe('DashboardAnalytics - Leave Statistics', function () {
    it('returns leave statistics by type', function () {
        $leaveType1 = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Tahunan',
        ]);
        $leaveType2 = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Sakit',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        LeaveRequest::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType1->id,
            'status' => 'approved',
            'start_date' => now()->subDays(10),
            'end_date' => now()->subDays(9),
        ]);

        LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType2->id,
            'status' => 'approved',
            'start_date' => now()->subDays(5),
            'end_date' => now()->subDays(4),
        ]);

        $data = $this->service->getLeaveStatistics($this->company->id);

        expect($data)->toBeArray();
        expect($data)->toHaveKeys(['labels', 'data', 'colors']);
        expect($data['labels'])->toContain('Cuti Tahunan', 'Cuti Sakit');
    });

    it('only counts approved leaves from current year', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        // Approved leave this year
        LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->startOfYear()->addDay(),
        ]);

        // Pending leave (should not count)
        LeaveRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
            'start_date' => now(),
            'end_date' => now()->addDay(),
        ]);

        $data = $this->service->getLeaveStatistics($this->company->id);

        expect(array_sum($data['data']))->toBe(3);
    });
});

describe('DashboardAnalytics - Monthly Summary', function () {
    it('returns monthly summary statistics', function () {
        // Create employees
        Employee::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'is_active' => true,
            'hire_date' => now()->subMonths(2),
        ]);

        // New employees this month
        Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'is_active' => true,
            'hire_date' => now(),
        ]);

        // Resigned this month
        Employee::factory()->count(1)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'is_active' => false,
            'resignation_date' => now(),
        ]);

        $data = $this->service->getMonthlySummary($this->company->id);

        expect($data)->toBeArray();
        expect($data)->toHaveKeys([
            'total_employees',
            'new_employees',
            'resigned_employees',
            'turnover_rate',
        ]);
        expect($data['total_employees'])->toBe(12);
        expect($data['new_employees'])->toBe(2);
        expect($data['resigned_employees'])->toBe(1);
    });
});

describe('DashboardAnalytics - Tenant Isolation', function () {
    it('only returns data for specified company', function () {
        $otherCompany = Company::factory()->create();
        $otherWorkSchedule = WorkSchedule::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Create employees for current company
        Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        // Create employees for other company
        $otherDept = Department::factory()->create(['company_id' => $otherCompany->id]);
        Employee::factory()->count(10)->create([
            'company_id' => $otherCompany->id,
            'work_schedule_id' => $otherWorkSchedule->id,
            'department_id' => $otherDept->id,
            'is_active' => true,
        ]);

        $data = $this->service->getEmployeeByDepartmentData($this->company->id);

        expect(array_sum($data['data']))->toBe(5);
    });
});
