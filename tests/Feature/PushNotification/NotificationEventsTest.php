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

    // Create device token for push
    $this->deviceToken = DeviceToken::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);
});

describe('AttendanceClockIn Event', function () {
    it('dispatches event when attendance clock in is created', function () {
        Event::fake([AttendanceClockIn::class]);

        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        event(new AttendanceClockIn($attendance));

        Event::assertDispatched(AttendanceClockIn::class, function ($event) use ($attendance) {
            return $event->attendance->id === $attendance->id;
        });
    });

    it('event contains attendance model', function () {
        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        $event = new AttendanceClockIn($attendance);

        expect($event->attendance)->toBeInstanceOf(Attendance::class);
        expect($event->attendance->id)->toBe($attendance->id);
    });
});

describe('AttendanceClockOut Event', function () {
    it('dispatches event when attendance clock out is recorded', function () {
        Event::fake([AttendanceClockOut::class]);

        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        event(new AttendanceClockOut($attendance));

        Event::assertDispatched(AttendanceClockOut::class, function ($event) use ($attendance) {
            return $event->attendance->id === $attendance->id;
        });
    });

    it('event contains attendance with clock out data', function () {
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'working_minutes' => 480,
        ]);

        $event = new AttendanceClockOut($attendance);

        expect($event->attendance->clock_out)->not->toBeNull();
        expect($event->attendance->working_minutes)->toBe(480);
    });
});

describe('LeaveRequestApproved Event', function () {
    it('dispatches event when leave request is approved', function () {
        Event::fake([LeaveRequestApproved::class]);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'approved',
        ]);

        event(new LeaveRequestApproved($leaveRequest));

        Event::assertDispatched(LeaveRequestApproved::class, function ($event) use ($leaveRequest) {
            return $event->leaveRequest->id === $leaveRequest->id;
        });
    });
});

describe('LeaveRequestRejected Event', function () {
    it('dispatches event when leave request is rejected', function () {
        Event::fake([LeaveRequestRejected::class]);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'rejected',
        ]);

        event(new LeaveRequestRejected($leaveRequest));

        Event::assertDispatched(LeaveRequestRejected::class, function ($event) use ($leaveRequest) {
            return $event->leaveRequest->id === $leaveRequest->id;
        });
    });
});

describe('SendAttendanceClockInNotification Listener', function () {
    it('creates notification when handling clock in event', function () {
        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'status' => 'present',
        ]);

        $event = new AttendanceClockIn($attendance);
        $listener = app(SendAttendanceClockInNotification::class);
        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'attendance_clock_in',
        ]);
    });

    it('sends push notification to user device', function () {
        $attendance = Attendance::factory()->clockedInOnly()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
        ]);

        $event = new AttendanceClockIn($attendance);
        $listener = app(SendAttendanceClockInNotification::class);
        $listener->handle($event);

        $notification = Notification::where('user_id', $this->user->id)
            ->where('type', 'attendance_clock_in')
            ->first();

        expect($notification->push_sent_at)->not->toBeNull();
    });
});

describe('SendAttendanceClockOutNotification Listener', function () {
    it('creates notification when handling clock out event', function () {
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'working_minutes' => 480,
        ]);

        $event = new AttendanceClockOut($attendance);
        $listener = app(SendAttendanceClockOutNotification::class);
        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'attendance_clock_out',
        ]);
    });
});

describe('SendLeaveApprovalNotification Listener', function () {
    it('creates notification when leave is approved', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'approved',
        ]);

        $event = new LeaveRequestApproved($leaveRequest);
        $listener = app(SendLeaveApprovalNotification::class);
        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'leave_approved',
        ]);
    });

    it('creates notification when leave is rejected', function () {
        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => 'rejected',
        ]);

        $event = new LeaveRequestRejected($leaveRequest);
        $listener = app(SendLeaveApprovalNotification::class);
        $listener->handle($event);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'leave_rejected',
        ]);
    });
});
