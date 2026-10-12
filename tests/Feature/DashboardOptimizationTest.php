<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\DashboardAnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'admin', 'guard_name' => 'web']);

    $this->company = Company::factory()->create();
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->user->assignRole('admin');

    $this->analyticsService = new DashboardAnalyticsService;
});

describe('DashboardAnalyticsService Query Optimization', function () {
    it('getAttendanceChartData uses optimized single query', function () {
        // Create multiple employees and attendance records
        $employees = Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);

        // Create attendance for last 14 days
        foreach ($employees as $employee) {
            for ($i = 0; $i < 14; $i++) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => now()->subDays($i),
                    'clock_in_status' => $i % 3 === 0 ? 'late' : 'on_time',
                ]);
            }
        }

        // Enable query logging
        DB::enableQueryLog();

        $result = $this->analyticsService->getAttendanceChartData(
            $this->company->id,
            14,
            $this->company
        );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have exactly 2 queries: 1 for employee count, 1 for attendance data
        expect(count($queries))->toBeLessThanOrEqual(2);

        // Verify result structure
        expect($result)->toHaveKeys(['labels', 'datasets']);
        expect($result['datasets'])->toHaveKeys(['present', 'late', 'absent']);
        expect(count($result['labels']))->toBe(14);
    });

    it('getPayrollTrendData uses optimized single query', function () {
        // Create payroll data for 6 months
        for ($i = 0; $i < 6; $i++) {
            $date = now()->subMonths($i);
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'period_month' => $date->month,
                'period_year' => $date->year,
            ]);

            // Create payroll items
            PayrollItem::factory()->count(3)->create([
                'payroll_id' => $payroll->id,
                'net_salary' => 5000000,
            ]);
        }

        DB::enableQueryLog();

        $result = $this->analyticsService->getPayrollTrendData(
            $this->company->id,
            6,
            $this->company
        );

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should have exactly 1 query for payroll data (optimized with join)
        expect(count($queries))->toBeLessThanOrEqual(1);

        // Verify result structure
        expect($result)->toHaveKeys(['labels', 'data']);
        expect(count($result['labels']))->toBe(6);
        expect(count($result['data']))->toBe(6);
    });

    it('getEmployeeByDepartmentData works correctly', function () {
        // Create departments with employees
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
            'department_id' => $dept1->id,
            'is_active' => true,
        ]);

        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'department_id' => $dept2->id,
            'is_active' => true,
        ]);

        $result = $this->analyticsService->getEmployeeByDepartmentData($this->company->id);

        expect($result)->toHaveKeys(['labels', 'data', 'colors']);
        expect(count($result['labels']))->toBe(2);

        // IT should be first (sorted by employee count desc)
        expect($result['labels'][0])->toBe('IT');
        expect($result['data'][0])->toBe(5);
    });

    it('getLeaveStatistics works correctly', function () {
        $leaveType = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Annual Leave',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'approved',
            'start_date' => now()->startOfYear()->addDays(10),
            'end_date' => now()->startOfYear()->addDays(12),
        ]);

        $result = $this->analyticsService->getLeaveStatistics($this->company->id, $this->company);

        expect($result)->toHaveKeys(['labels', 'data', 'colors']);
    });

    it('getMonthlySummary returns correct data', function () {
        // Create employees
        Employee::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'hire_date' => now()->subMonths(3),
        ]);

        // New employees this month
        Employee::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'hire_date' => now(),
        ]);

        $result = $this->analyticsService->getMonthlySummary($this->company->id, $this->company);

        expect($result)->toHaveKeys(['total_employees', 'new_employees', 'resigned_employees', 'turnover_rate']);
        expect($result['total_employees'])->toBe(12);
        expect($result['new_employees'])->toBe(2);
    });
});

describe('DashboardController Query Optimization', function () {
    beforeEach(function () {
        $this->actingAs($this->user);
    });

    it('dashboard page loads successfully', function () {
        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
    });

    it('dashboard loads with optimized queries', function () {
        // Create test data
        $employees = Employee::factory()->count(10)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'is_active' => true,
        ]);

        // Create attendance for today
        foreach ($employees as $employee) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'clock_in_status' => 'on_time',
            ]);
        }

        DB::enableQueryLog();

        $response = $this->get(route('dashboard'));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();

        // Dashboard should use less than 40 queries (optimized)
        // Before optimization it was 50+ queries with N+1 problems
        // Current queries include: auth, tenant, stats, analytics, etc.
        expect(count($queries))->toBeLessThan(40);
    });

    it('dashboard shows correct attendance stats', function () {
        $employees = Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'is_active' => true,
        ]);

        // 3 on time, 2 late
        foreach ($employees->take(3) as $employee) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'clock_in_status' => 'on_time',
            ]);
        }

        foreach ($employees->skip(3) as $employee) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
                'clock_in_status' => 'late',
            ]);
        }

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('attendanceToday', function ($data) {
            return $data['present'] === 3 && $data['late'] === 2;
        });
    });
});

describe('Database Index Migration', function () {
    it('performance indexes exist on employees table', function () {
        $indexes = DB::select("PRAGMA index_list('employees')");
        $indexNames = collect($indexes)->pluck('name')->toArray();

        expect($indexNames)->toContain('idx_employees_company_active');
        expect($indexNames)->toContain('idx_employees_company_department');
    });

    it('performance indexes exist on attendances table', function () {
        $indexes = DB::select("PRAGMA index_list('attendances')");
        $indexNames = collect($indexes)->pluck('name')->toArray();

        expect($indexNames)->toContain('idx_attendances_company_date');
        expect($indexNames)->toContain('idx_attendances_status');
    });

    it('performance indexes exist on leave_requests table', function () {
        $indexes = DB::select("PRAGMA index_list('leave_requests')");
        $indexNames = collect($indexes)->pluck('name')->toArray();

        expect($indexNames)->toContain('idx_leave_requests_company_status');
    });

    it('performance indexes exist on payrolls table', function () {
        $indexes = DB::select("PRAGMA index_list('payrolls')");
        $indexNames = collect($indexes)->pluck('name')->toArray();

        expect($indexNames)->toContain('idx_payrolls_company_period');
    });
});
