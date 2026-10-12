<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\User;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('DashboardController', function () {
    describe('index', function () {
        it('displays dashboard page', function () {
            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $response->assertViewIs('dashboard');
        });

        it('shows employee count statistics', function () {
            // Create employees for this company
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            // Create employees for different company (should not be counted)
            $otherCompany = Company::factory()->create();
            Employee::factory()->count(3)->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $response->assertViewHas('stats');
            expect($response->viewData('stats')['total_employees'])->toBe(5);
        });

        it('shows today attendance count', function () {
            $employees = Employee::factory()->count(10)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            // Create today's attendance for 7 employees (using company timezone)
            foreach ($employees->take(7) as $employee) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $this->company->today(),
                    'clock_in' => $this->company->now()->setTime(8, 0),
                ]);
            }

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            expect($response->viewData('stats')['present_today'])->toBe(7);
        });

        it('shows pending leave requests count', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $leaveType = LeaveType::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Create pending leave requests
            LeaveRequest::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'status' => 'pending',
            ]);

            // Create approved leave request (should not be counted)
            LeaveRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'status' => 'approved',
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            expect($response->viewData('stats')['pending_leaves'])->toBe(3);
        });

        it('shows total payroll this month', function () {
            $payroll = Payroll::factory()->create([
                'company_id' => $this->company->id,
                'period_month' => now()->month,
                'period_year' => now()->year,
                'status' => 'paid',
            ]);

            // Create 3 employees with separate payroll items
            $employees = Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
            ]);

            foreach ($employees as $employee) {
                PayrollItem::factory()->create([
                    'payroll_id' => $payroll->id,
                    'employee_id' => $employee->id,
                    'net_salary' => 5000000,
                ]);
            }

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            expect($response->viewData('stats')['total_payroll_this_month'])->toBe(15000000.0);
        });

        it('shows attendance breakdown for today', function () {
            $employees = Employee::factory()->count(10)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            // 5 present on time (using company timezone)
            foreach ($employees->take(5) as $employee) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $this->company->today(),
                    'clock_in' => $this->company->now()->setTime(8, 15),
                    'status' => 'present',
                    'clock_in_status' => 'on_time',
                    'late_minutes' => 0,
                ]);
            }

            // 3 late (using company timezone)
            foreach ($employees->skip(5)->take(3) as $employee) {
                Attendance::factory()->late(30)->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $this->company->today(),
                ]);
            }

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $attendance = $response->viewData('attendanceToday');
            expect($attendance['present'])->toBe(5);
            expect($attendance['late'])->toBe(3);
        });

        it('shows recent clock-ins', function () {
            $employees = Employee::factory()->count(10)->create([
                'company_id' => $this->company->id,
            ]);

            foreach ($employees as $index => $employee) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $this->company->today(),
                    'clock_in' => $this->company->now()->subMinutes($index * 5),
                ]);
            }

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $recentClockIns = $response->viewData('recentClockIns');
            expect($recentClockIns)->toHaveCount(5);
        });

        it('shows recent employees', function () {
            Employee::factory()->count(10)->create([
                'company_id' => $this->company->id,
                'created_at' => now()->subDays(rand(1, 30)),
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $recentEmployees = $response->viewData('recentEmployees');
            expect($recentEmployees)->toHaveCount(5);
        });

        it('shows pending approvals', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $leaveType = LeaveType::factory()->create([
                'company_id' => $this->company->id,
            ]);

            LeaveRequest::factory()->count(10)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'status' => 'pending',
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $pendingApprovals = $response->viewData('pendingApprovals');
            expect($pendingApprovals)->toHaveCount(5);
        });

        it('shows birthdays this month', function () {
            // Employees with birthday this month
            Employee::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'date_of_birth' => now()->setDay(rand(1, 28)),
            ]);

            // Employee with birthday next month (should not appear)
            Employee::factory()->create([
                'company_id' => $this->company->id,
                'date_of_birth' => now()->addMonth()->setDay(15),
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $birthdays = $response->viewData('birthdaysThisMonth');
            expect($birthdays)->toHaveCount(3);
        });

        it('shows contracts expiring soon', function () {
            // Employees with contract ending in 30 days
            Employee::factory()->count(2)->create([
                'company_id' => $this->company->id,
                'employment_status' => 'contract',
                'contract_end_date' => now()->addDays(15),
            ]);

            // Employee with contract ending in 60 days (should not appear)
            Employee::factory()->create([
                'company_id' => $this->company->id,
                'employment_status' => 'contract',
                'contract_end_date' => now()->addDays(60),
            ]);

            // Permanent employee (no contract end date)
            Employee::factory()->create([
                'company_id' => $this->company->id,
                'employment_status' => 'permanent',
                'contract_end_date' => null,
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            $expiringContracts = $response->viewData('expiringContracts');
            expect($expiringContracts)->toHaveCount(2);
        });

        it('only shows data from the same company (multi-tenant)', function () {
            // Create data for this company
            Employee::factory()->count(5)->create([
                'company_id' => $this->company->id,
            ]);

            // Create data for another company
            $otherCompany = Company::factory()->create();
            Employee::factory()->count(10)->create([
                'company_id' => $otherCompany->id,
            ]);

            $response = $this->get(route('dashboard'));

            $response->assertStatus(200);
            expect($response->viewData('stats')['total_employees'])->toBe(5);
        });
    });
});
