<?php

use App\Events\AttendanceClockIn;
use App\Events\AttendanceClockOut;
use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestRejected;
use App\Listeners\SendAttendanceClockInNotification;
use App\Listeners\SendAttendanceClockOutNotification;
use App\Listeners\SendLeaveApprovalNotification;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);

    DeviceToken::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);
});

describe('Event-Listener Integration', function () {
    it('registers AttendanceClockIn listener correctly', function () {
        Event::fake();

        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        AttendanceClockIn::dispatch($attendance);

        Event::assertDispatched(AttendanceClockIn::class);
        Event::assertListening(AttendanceClockIn::class, SendAttendanceClockInNotification::class);
    });

    it('registers AttendanceClockOut listener correctly', function () {
        Event::fake();

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        AttendanceClockOut::dispatch($attendance);

        Event::assertDispatched(AttendanceClockOut::class);
        Event::assertListening(AttendanceClockOut::class, SendAttendanceClockOutNotification::class);
    });

    it('registers LeaveRequestApproved listener correctly', function () {
        Event::fake();

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'approved',
        ]);

        LeaveRequestApproved::dispatch($leaveRequest);

        Event::assertDispatched(LeaveRequestApproved::class);
        Event::assertListening(LeaveRequestApproved::class, SendLeaveApprovalNotification::class);
    });

    it('registers LeaveRequestRejected listener correctly', function () {
        Event::fake();

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'rejected',
        ]);

        LeaveRequestRejected::dispatch($leaveRequest);

        Event::assertDispatched(LeaveRequestRejected::class);
        Event::assertListening(LeaveRequestRejected::class, SendLeaveApprovalNotification::class);
    });

    it('listeners implement ShouldQueue interface', function () {
        $listener = new SendAttendanceClockInNotification(
            app(\App\Services\PushNotificationService::class)
        );

        expect($listener)->toBeInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class);
    });

    it('full integration: dispatching event creates notification', function () {
        // Sync queue for testing
        config(['queue.default' => 'sync']);

        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'status' => 'present',
        ]);

        // Dispatch real event (not faked)
        AttendanceClockIn::dispatch($attendance);

        // Should have created notification record
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'attendance_clock_in',
        ]);
    });
});
