<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

describe('AttendanceReportController', function () {
    describe('index', function () {
        it('displays attendance report page', function () {
            $response = $this->get(route('reports.attendance'));

            $response->assertStatus(200);
            $response->assertViewIs('reports.attendance.index');
        });

        it('shows monthly attendance summary', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Create attendance for current month with unique dates
            $startOfMonth = now()->startOfMonth();
            for ($i = 0; $i < 15; $i++) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => $startOfMonth->copy()->addDays($i),
                    'status' => 'present',
                ]);
            }

            $response = $this->get(route('reports.attendance'));

            $response->assertStatus(200);
            $response->assertViewHas('summary');
        });

        it('can filter by date range', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Create attendance for different dates
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => now()->subDays(5),
            ]);

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => now()->subDays(20),
            ]);

            $response = $this->get(route('reports.attendance', [
                'start_date' => now()->subDays(10)->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
            ]));

            $response->assertStatus(200);
        });

        it('can filter by department', function () {
            $department = Department::factory()->create(['company_id' => $this->company->id]);
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
                'department_id' => $department->id,
            ]);

            Attendance::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('reports.attendance', [
                'department_id' => $department->id,
            ]));

            $response->assertStatus(200);
        });
    });

    describe('daily', function () {
        it('shows daily attendance report', function () {
            $employees = Employee::factory()->count(10)->create([
                'company_id' => $this->company->id,
                'is_active' => true,
            ]);

            foreach ($employees->take(7) as $employee) {
                Attendance::factory()->create([
                    'company_id' => $this->company->id,
                    'employee_id' => $employee->id,
                    'date' => today(),
                ]);
            }

            $response = $this->get(route('reports.attendance.daily'));

            $response->assertStatus(200);
            $response->assertViewHas('attendances');
            $response->assertViewHas('summary');
        });

        it('can filter by specific date', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            $targetDate = now()->subDays(3);

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'date' => $targetDate,
            ]);

            $response = $this->get(route('reports.attendance.daily', [
                'date' => $targetDate->format('Y-m-d'),
            ]));

            $response->assertStatus(200);
        });
    });

    describe('lateness', function () {
        it('shows lateness report', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            // Create late attendances
            Attendance::factory()->late(30)->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            // Create on-time attendances
            Attendance::factory()->count(3)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
                'clock_in_status' => 'on_time',
                'late_minutes' => 0,
            ]);

            $response = $this->get(route('reports.attendance.lateness'));

            $response->assertStatus(200);
            $response->assertViewHas('lateAttendances');
        });
    });

    describe('export', function () {
        it('can export attendance to excel', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            Attendance::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('reports.attendance.export', ['format' => 'excel']));

            $response->assertStatus(200);
            $response->assertDownload();
        });

        it('can export attendance to pdf', function () {
            $employee = Employee::factory()->create([
                'company_id' => $this->company->id,
            ]);

            Attendance::factory()->count(5)->create([
                'company_id' => $this->company->id,
                'employee_id' => $employee->id,
            ]);

            $response = $this->get(route('reports.attendance.export', ['format' => 'pdf']));

            $response->assertStatus(200);
        });
    });
});
