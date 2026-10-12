<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Attendance Model', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();
        $this->workSchedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
            'late_tolerance' => 15,
            'early_leave_tolerance' => 15,
        ]);
        $this->employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $this->workSchedule->id,
        ]);
    });

    describe('factory', function () {
        it('can create attendance using factory', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance)->toBeInstanceOf(Attendance::class);
            expect($attendance->company_id)->toBe($this->company->id);
            expect($attendance->employee_id)->toBe($this->employee->id);
        });

        it('can create late attendance', function () {
            $attendance = Attendance::factory()->late(30)->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->status)->toBe('late');
            expect($attendance->late_minutes)->toBe(30);
            expect($attendance->clock_in_status)->toBe('late');
        });

        it('can create very late attendance', function () {
            $attendance = Attendance::factory()->late(45)->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->clock_in_status)->toBe('very_late');
        });

        it('can create absent attendance', function () {
            $attendance = Attendance::factory()->absent()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->status)->toBe('absent');
            expect($attendance->clock_in)->toBeNull();
            expect($attendance->clock_out)->toBeNull();
            expect($attendance->working_minutes)->toBe(0);
        });

        it('can create half day attendance', function () {
            $attendance = Attendance::factory()->halfDay()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->status)->toBe('half_day');
            expect($attendance->working_minutes)->toBe(240);
            expect($attendance->clock_out_status)->toBe('early');
        });

        it('can create attendance with overtime', function () {
            $attendance = Attendance::factory()->withOvertime(60)->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->overtime_minutes)->toBe(60);
            expect($attendance->clock_out_status)->toBe('overtime');
            expect($attendance->working_minutes)->toBe(540); // 8 hours + 1 hour overtime
        });

        it('can create on leave attendance', function () {
            $attendance = Attendance::factory()->onLeave()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->status)->toBe('leave');
            expect($attendance->clock_in)->toBeNull();
        });

        it('can create clocked in only attendance', function () {
            $attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->clock_in)->not->toBeNull();
            expect($attendance->clock_out)->toBeNull();
        });
    });

    describe('relationships', function () {
        it('belongs to a company', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->company)->toBeInstanceOf(Company::class);
            expect($attendance->company->id)->toBe($this->company->id);
        });

        it('belongs to an employee', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->employee)->toBeInstanceOf(Employee::class);
            expect($attendance->employee->id)->toBe($this->employee->id);
        });

        it('belongs to a work schedule', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'work_schedule_id' => $this->workSchedule->id,
            ]);

            expect($attendance->workSchedule)->toBeInstanceOf(WorkSchedule::class);
        });
    });

    describe('scopes', function () {
        it('can filter by date', function () {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $today,
            ]);
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $yesterday,
            ]);

            $attendances = Attendance::forDate($today)->get();

            expect($attendances)->toHaveCount(1);
        });

        it('can filter by month', function () {
            $thisMonth = Carbon::today();
            $lastMonth = Carbon::today()->subMonth();

            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $thisMonth,
            ]);
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => $lastMonth,
            ]);

            $attendances = Attendance::forMonth($thisMonth->year, $thisMonth->month)->get();

            expect($attendances)->toHaveCount(1);
        });

        it('can filter present attendances', function () {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'present',
            ]);
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'absent',
            ]);

            $presentAttendances = Attendance::present()->get();

            expect($presentAttendances)->toHaveCount(1);
        });

        it('can filter absent attendances', function () {
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'absent',
            ]);
            Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'present',
            ]);

            $absentAttendances = Attendance::absent()->get();

            expect($absentAttendances)->toHaveCount(1);
        });
    });

    describe('accessors', function () {
        it('returns formatted clock in', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'clock_in' => Carbon::today()->setTime(8, 30, 0),
            ]);

            expect($attendance->formatted_clock_in)->toBe('08:30');
        });

        it('returns formatted clock out', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'clock_out' => Carbon::today()->setTime(17, 15, 0),
            ]);

            expect($attendance->formatted_clock_out)->toBe('17:15');
        });

        it('returns working hours in human readable format', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'working_minutes' => 510, // 8 hours 30 minutes
            ]);

            expect($attendance->working_hours)->toBe('8 jam 30 menit');
        });

        it('returns status label in Indonesian', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'present',
            ]);

            expect($attendance->status_label)->toBe('Hadir');
        });

        it('returns status color', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'absent',
            ]);

            expect($attendance->status_color)->toBe('danger');
        });
    });

    describe('methods', function () {
        it('can check if clocked in', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'clock_in' => Carbon::now(),
            ]);

            expect($attendance->hasClockedIn())->toBeTrue();
        });

        it('can check if clocked out', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'clock_out' => Carbon::now(),
            ]);

            expect($attendance->hasClockedOut())->toBeTrue();
        });

        it('can check if attendance is complete', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'clock_in' => Carbon::today()->setTime(8, 0, 0),
                'clock_out' => Carbon::today()->setTime(17, 0, 0),
            ]);

            expect($attendance->isComplete())->toBeTrue();
        });

        it('returns false for incomplete attendance', function () {
            $attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->isComplete())->toBeFalse();
        });
    });

    describe('casts', function () {
        it('casts date to date', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->date)->toBeInstanceOf(Carbon::class);
        });

        it('casts clock in to datetime', function () {
            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->clock_in)->toBeInstanceOf(Carbon::class);
        });

        it('casts is_manual_entry to boolean', function () {
            $attendance = Attendance::factory()->manualEntry()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
            ]);

            expect($attendance->is_manual_entry)->toBeBool();
            expect($attendance->is_manual_entry)->toBeTrue();
        });
    });
});
