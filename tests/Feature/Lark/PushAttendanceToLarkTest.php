<?php

use App\Events\AttendanceClockIn;
use App\Events\AttendanceClockOut;
use App\Jobs\PushAttendanceRecordToLarkJob;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LarkIntegration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'lark_user_id' => 'ou_emp_test',
    ]);
    $this->attendance = Attendance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'date' => Carbon::today(),
        'clock_in' => Carbon::now(),
    ]);
});

test('AttendanceClockIn dispatches PushAttendanceRecordToLarkJob when integration is active', function () {
    Queue::fake();

    LarkIntegration::create([
        'company_id' => $this->company->id,
        'app_id' => 'cli_123',
        'app_secret' => 'sec_123',
        'is_active' => true,
    ]);

    event(new AttendanceClockIn($this->attendance));

    Queue::assertPushed(PushAttendanceRecordToLarkJob::class, function ($job) {
        return $job->attendance->id === $this->attendance->id && $job->type === 'clock_in';
    });
});

test('AttendanceClockOut dispatches PushAttendanceRecordToLarkJob when integration is active', function () {
    Queue::fake();

    LarkIntegration::create([
        'company_id' => $this->company->id,
        'app_id' => 'cli_123',
        'app_secret' => 'sec_123',
        'is_active' => true,
    ]);

    $this->attendance->update(['clock_out' => Carbon::now()]);
    event(new AttendanceClockOut($this->attendance));

    Queue::assertPushed(PushAttendanceRecordToLarkJob::class, function ($job) {
        return $job->attendance->id === $this->attendance->id && $job->type === 'clock_out';
    });
});

test('Attendance events do NOT dispatch push job when integration is inactive', function () {
    Queue::fake();

    LarkIntegration::create([
        'company_id' => $this->company->id,
        'app_id' => 'cli_123',
        'app_secret' => 'sec_123',
        'is_active' => false,
    ]);

    event(new AttendanceClockIn($this->attendance));

    Queue::assertNotPushed(PushAttendanceRecordToLarkJob::class);
});
