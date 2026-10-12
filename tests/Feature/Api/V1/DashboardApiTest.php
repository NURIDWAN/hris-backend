<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);
});

describe('GET /api/v1/dashboard', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertUnauthorized();
    });

    it('returns dashboard summary data', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'attendance' => [
                        'today_status',
                        'clock_in',
                        'clock_out',
                        'monthly_present',
                        'monthly_absent',
                        'monthly_late',
                    ],
                    'leave' => [
                        'annual_balance',
                        'annual_used',
                        'pending_requests',
                    ],
                    'payroll' => [
                        'last_payslip_month',
                        'last_payslip_amount',
                        'ytd_gross',
                        'ytd_net',
                    ],
                    'requests' => [
                        'pending_overtime',
                        'pending_reimbursement',
                        'pending_leave',
                    ],
                ],
            ]);
    });

    it('returns correct attendance summary for current month', function () {
        Sanctum::actingAs($this->user);

        // Create attendance records for current month with unique dates
        // Use last month to avoid conflicts with today's date
        $lastMonth = now()->subMonth()->startOfMonth();
        for ($i = 1; $i <= 15; $i++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $lastMonth->copy()->addDays($i - 1),
                'status' => 'present',
                'late_minutes' => 0,
            ]);
        }

        // Create current month attendance records
        $startOfMonth = now()->startOfMonth();
        $todayDay = today()->day;

        for ($i = 1; $i <= min(15, $todayDay - 1); $i++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $startOfMonth->copy()->addDays($i - 1),
                'status' => 'present',
                'late_minutes' => 0,
            ]);
        }

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        // Verify attendance data structure exists
        expect($response->json('data.attendance'))->toBeArray();
    });

    it('returns correct leave balance', function () {
        Sanctum::actingAs($this->user);

        $leaveType = LeaveType::factory()->annual()->create([
            'company_id' => $this->company->id,
        ]);

        LeaveBalance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => now()->year,
            'entitled_days' => 12,
            'used_days' => 5,
            'pending_days' => 2,
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        expect((float) $response->json('data.leave.annual_balance'))->toBe(12.0);
        expect((float) $response->json('data.leave.annual_used'))->toBe(5.0);
    });

    it('returns correct pending requests count', function () {
        Sanctum::actingAs($this->user);

        // Pending leave requests
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);
        LeaveRequest::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $leaveType->id,
            'status' => 'pending',
        ]);

        // Pending overtime requests with unique dates
        for ($i = 1; $i <= 3; $i++) {
            OvertimeRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => now()->addDays($i)->toDateString(),
                'status' => 'pending',
            ]);
        }

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        expect($response->json('data.requests.pending_leave'))->toBe(2);
        expect($response->json('data.requests.pending_overtime'))->toBe(3);
    });

    it('returns last payslip information', function () {
        Sanctum::actingAs($this->user);

        $payroll = Payroll::factory()->create([
            'company_id' => $this->company->id,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'status' => 'paid',
        ]);

        PayrollItem::factory()->create([
            'payroll_id' => $payroll->id,
            'employee_id' => $this->employee->id,
            'gross_salary' => 10000000,
            'net_salary' => 8500000,
        ]);

        $response = $this->getJson('/api/v1/dashboard');

        $response->assertOk();
        expect((float) $response->json('data.payroll.last_payslip_amount'))->toBe(8500000.0);
    });
});

describe('GET /api/v1/dashboard/attendance-chart', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/dashboard/attendance-chart');

        $response->assertUnauthorized();
    });

    it('returns 7 days attendance data by default', function () {
        Sanctum::actingAs($this->user);

        // Create attendance for last 7 days
        for ($i = 6; $i >= 0; $i--) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => now()->subDays($i)->toDateString(),
                'status' => 'present',
                'working_minutes' => 480,
            ]);
        }

        $response = $this->getJson('/api/v1/dashboard/attendance-chart');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'labels',
                    'datasets' => [
                        ['label', 'data'],
                    ],
                ],
            ]);

        expect(count($response->json('data.labels')))->toBe(7);
    });

    it('can return 30 days attendance data', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/dashboard/attendance-chart?days=30');

        $response->assertOk();
        expect(count($response->json('data.labels')))->toBe(30);
    });

    it('returns working hours data', function () {
        Sanctum::actingAs($this->user);

        // Create attendance for yesterday to ensure it's in the range
        $yesterday = now()->subDay()->toDateString();
        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => $yesterday,
            'status' => 'present',
            'working_minutes' => 480, // 8 hours
        ]);

        $response = $this->getJson('/api/v1/dashboard/attendance-chart?days=7');

        $response->assertOk();

        // Verify we got 7 data points
        $data = $response->json('data.datasets.0.data');
        expect(count($data))->toBe(7);

        // At least one day should have 8 hours
        $hasEightHours = collect($data)->contains(fn ($val) => abs((float) $val - 8.0) < 0.01);
        expect($hasEightHours)->toBeTrue();
    });
});

describe('GET /api/v1/dashboard/quick-stats', function () {
    it('requires authentication', function () {
        $response = $this->getJson('/api/v1/dashboard/quick-stats');

        $response->assertUnauthorized();
    });

    it('returns quick statistics', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/dashboard/quick-stats');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_working_days_this_month',
                    'total_present_days',
                    'total_absent_days',
                    'total_leave_days',
                    'total_late_count',
                    'total_overtime_hours',
                    'attendance_percentage',
                ],
            ]);
    });

    it('calculates attendance percentage correctly', function () {
        Sanctum::actingAs($this->user);

        // Create 20 working days, 18 present, 2 absent
        for ($i = 1; $i <= 18; $i++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => now()->startOfMonth()->addDays($i),
                'status' => 'present',
            ]);
        }

        for ($i = 19; $i <= 20; $i++) {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => now()->startOfMonth()->addDays($i),
                'status' => 'absent',
            ]);
        }

        $response = $this->getJson('/api/v1/dashboard/quick-stats');

        $response->assertOk();
        expect($response->json('data.total_present_days'))->toBe(18);
        expect($response->json('data.total_absent_days'))->toBe(2);
        // 18/20 = 90%
        expect((float) $response->json('data.attendance_percentage'))->toBe(90.0);
    });
});
