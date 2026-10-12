<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
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
});

describe('Dashboard - Index', function () {
    it('displays dashboard with analytics data', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewIs('dashboard');
        $response->assertViewHas('stats');
        $response->assertViewHas('analytics');
    });

    it('includes attendance chart data in analytics', function () {
        // Create some attendance data
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'date' => now(),
            'clock_in' => now()->setTime(8, 0),
            'clock_in_status' => 'on_time',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $analytics = $response->viewData('analytics');

        expect($analytics)->toHaveKey('attendance_chart');
        expect($analytics['attendance_chart'])->toHaveKeys(['labels', 'datasets']);
    });

    it('includes employee by department data in analytics', function () {
        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $analytics = $response->viewData('analytics');

        expect($analytics)->toHaveKey('employee_by_department');
        expect($analytics['employee_by_department'])->toHaveKeys(['labels', 'data', 'colors']);
    });

    it('includes payroll trend data in analytics', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $analytics = $response->viewData('analytics');

        expect($analytics)->toHaveKey('payroll_trend');
        expect($analytics['payroll_trend'])->toHaveKeys(['labels', 'data']);
    });

    it('includes employee status distribution in analytics', function () {
        Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $analytics = $response->viewData('analytics');

        expect($analytics)->toHaveKey('employee_status');
        expect($analytics['employee_status'])->toHaveKeys(['labels', 'data', 'colors']);
    });

    it('includes leave statistics in analytics', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $analytics = $response->viewData('analytics');

        expect($analytics)->toHaveKey('leave_statistics');
    });

    it('includes monthly summary in analytics', function () {
        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $analytics = $response->viewData('analytics');

        expect($analytics)->toHaveKey('monthly_summary');
        expect($analytics['monthly_summary'])->toHaveKeys([
            'total_employees',
            'new_employees',
            'resigned_employees',
            'turnover_rate',
        ]);
    });
});

describe('Dashboard - Access Control', function () {
    it('requires authentication', function () {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    });
});

describe('Dashboard - Tenant Isolation', function () {
    it('only shows data for current company', function () {
        $otherCompany = Company::factory()->create();
        $otherWorkSchedule = WorkSchedule::factory()->create([
            'company_id' => $otherCompany->id,
        ]);
        $otherDept = Department::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        // Create 5 employees for current company
        Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
            'department_id' => $this->department->id,
            'is_active' => true,
        ]);

        // Create 10 employees for other company
        Employee::factory()->count(10)->create([
            'company_id' => $otherCompany->id,
            'work_schedule_id' => $otherWorkSchedule->id,
            'department_id' => $otherDept->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('dashboard'));

        $response->assertOk();
        $stats = $response->viewData('stats');

        expect($stats['total_employees'])->toBe(5);
    });
});
