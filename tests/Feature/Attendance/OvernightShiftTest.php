<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);
    createStandardRoles($this->company->id);
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');
    $this->actingAs($this->user);
});

describe('WorkSchedule overnight detection', function () {
    it('auto-detects overnight shift when end_time is before start_time', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        expect($schedule->is_overnight)->toBeTrue();
    });

    it('does not mark as overnight when end_time is after start_time', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        expect($schedule->is_overnight)->toBeFalse();
    });

    it('calculates correct scheduled end for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $shiftDate = Carbon::parse('2026-02-24');
        $scheduledEnd = $schedule->getScheduledEnd($shiftDate);

        // For overnight shift, end should be on the next day
        expect($scheduledEnd->format('Y-m-d H:i'))->toBe('2026-02-25 06:00');
    });

    it('calculates correct scheduled end for regular shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $shiftDate = Carbon::parse('2026-02-24');
        $scheduledEnd = $schedule->getScheduledEnd($shiftDate);

        // For regular shift, end should be on the same day
        expect($scheduledEnd->format('Y-m-d H:i'))->toBe('2026-02-24 17:00');
    });
});

describe('Attendance overnight calculations', function () {
    it('calculates correct overtime for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'early_leave_tolerance' => 15,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        // Create attendance for overnight shift
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'date' => '2026-02-24',
            'scheduled_start' => '22:00',
            'scheduled_end' => '06:00',
            'clock_in' => Carbon::parse('2026-02-24 22:00'),
            'clock_out' => Carbon::parse('2026-02-25 06:30'), // 30 min overtime
            'status' => 'present',
        ]);

        $attendance->recalculate();

        // Should have 30 minutes overtime (clocked out at 06:30, scheduled 06:00)
        expect((int) $attendance->overtime_minutes)->toBe(30);
        expect($attendance->clock_out_status)->toBe('overtime');
    });

    it('calculates correct early leave for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'early_leave_tolerance' => 15,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        // Create attendance for overnight shift with early leave
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'date' => '2026-02-24',
            'scheduled_start' => '22:00',
            'scheduled_end' => '06:00',
            'clock_in' => Carbon::parse('2026-02-24 22:00'),
            'clock_out' => Carbon::parse('2026-02-25 05:00'), // 1 hour early
            'status' => 'present',
        ]);

        $attendance->recalculate();

        // Should have 60 minutes early leave (clocked out at 05:00, scheduled 06:00)
        expect((int) $attendance->early_leave_minutes)->toBe(60);
        expect($attendance->clock_out_status)->toBe('early');
    });

    it('calculates on-time clock out for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'early_leave_tolerance' => 15,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        // Create attendance for overnight shift - on time
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'date' => '2026-02-24',
            'scheduled_start' => '22:00',
            'scheduled_end' => '06:00',
            'clock_in' => Carbon::parse('2026-02-24 22:00'),
            'clock_out' => Carbon::parse('2026-02-25 05:50'), // Within tolerance
            'status' => 'present',
        ]);

        $attendance->recalculate();

        // Should be on-time (within 15 min tolerance)
        expect($attendance->early_leave_minutes)->toBe(0);
        expect($attendance->overtime_minutes)->toBe(0);
        expect($attendance->clock_out_status)->toBe('on_time');
    });

    it('calculates correct working hours for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_duration' => 60,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        // Create attendance for overnight shift
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'date' => '2026-02-24',
            'scheduled_start' => '22:00',
            'scheduled_end' => '06:00',
            'clock_in' => Carbon::parse('2026-02-24 22:00'),
            'clock_out' => Carbon::parse('2026-02-25 06:00'),
            'status' => 'present',
        ]);

        $attendance->recalculate();

        // 8 hours total - 1 hour break = 420 minutes (7 hours)
        expect((int) $attendance->working_minutes)->toBe(420);
    });

    it('handles late clock-in for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'late_tolerance' => 15,
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        // Create attendance with late clock-in
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'date' => '2026-02-24',
            'scheduled_start' => '22:00',
            'scheduled_end' => '06:00',
            'clock_in' => Carbon::parse('2026-02-24 22:30'), // 30 min late
            'clock_out' => Carbon::parse('2026-02-25 06:00'),
            'status' => 'present',
        ]);

        $attendance->recalculate();

        // Should be 30 minutes late
        expect((int) $attendance->late_minutes)->toBe(30);
        expect($attendance->status)->toBe('late');
        expect($attendance->clock_in_status)->toBe('late');
    });
});

describe('Attendance model helpers', function () {
    it('correctly identifies overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
        ]);

        expect($attendance->isOvernightShift())->toBeTrue();
    });

    it('correctly identifies regular shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '08:00',
            'end_time' => '17:00',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
        ]);

        expect($attendance->isOvernightShift())->toBeFalse();
    });

    it('returns correct scheduled end datetime for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'work_schedule_id' => $schedule->id,
        ]);

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'work_schedule_id' => $schedule->id,
            'date' => '2026-02-24',
            'scheduled_end' => '06:00',
        ]);

        $scheduledEnd = $attendance->getScheduledEndDatetime();

        expect($scheduledEnd->format('Y-m-d H:i'))->toBe('2026-02-25 06:00');
    });
});

describe('WorkSchedule helper methods', function () {
    it('calculates overtime minutes correctly for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $shiftDate = Carbon::parse('2026-02-24');
        $clockOut = Carbon::parse('2026-02-25 07:00'); // 1 hour overtime

        $overtimeMinutes = $schedule->getOvertimeMinutes($clockOut, $shiftDate);

        expect($overtimeMinutes)->toBe(60);
    });

    it('calculates early leave minutes correctly for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $shiftDate = Carbon::parse('2026-02-24');
        $clockOut = Carbon::parse('2026-02-25 04:00'); // 2 hours early

        $earlyLeaveMinutes = $schedule->getEarlyLeaveMinutes($clockOut, $shiftDate);

        expect($earlyLeaveMinutes)->toBe(120);
    });

    it('detects early leave correctly for overnight shift', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'early_leave_tolerance' => 15,
        ]);

        $shiftDate = Carbon::parse('2026-02-24');
        $clockOutEarly = Carbon::parse('2026-02-25 05:00');
        $clockOutOnTime = Carbon::parse('2026-02-25 05:50');

        expect($schedule->isEarlyLeave($clockOutEarly, $shiftDate))->toBeTrue();
        expect($schedule->isEarlyLeave($clockOutOnTime, $shiftDate))->toBeFalse();
    });

    it('calculates working minutes with break deduction', function () {
        $schedule = WorkSchedule::factory()->create([
            'company_id' => $this->company->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'break_duration' => 60,
        ]);

        $clockIn = Carbon::parse('2026-02-24 22:00');
        $clockOut = Carbon::parse('2026-02-25 06:00');

        $workingMinutes = $schedule->calculateWorkingMinutes($clockIn, $clockOut);

        // 8 hours (480 min) - 60 min break = 420 min
        expect($workingMinutes)->toBe(420);
    });
});
