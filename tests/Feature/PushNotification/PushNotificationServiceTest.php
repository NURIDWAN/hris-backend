<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

describe('PushNotificationService', function () {
    describe('notifyAttendanceClockIn', function () {
        it('sends push notification on clock in', function () {
            $pushService = app(PushNotificationService::class);

            $attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'status' => 'present',
            ]);

            $result = $pushService->notifyAttendanceClockIn($attendance);

            expect($result)->toBeArray();
            expect($result['total'])->toBeGreaterThanOrEqual(1);
        });

        it('creates notification record', function () {
            $pushService = app(PushNotificationService::class);

            $attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'status' => 'present',
            ]);

            $pushService->notifyAttendanceClockIn($attendance);

            $this->assertDatabaseHas('notifications', [
                'user_id' => $this->user->id,
                'type' => 'attendance_clock_in',
            ]);
        });

        it('includes late info in notification when employee is late', function () {
            $pushService = app(PushNotificationService::class);

            $attendance = Attendance::factory()->clockedInOnly()->late(30)->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
            ]);

            $pushService->notifyAttendanceClockIn($attendance);

            $notification = Notification::where('user_id', $this->user->id)
                ->where('type', 'attendance_clock_in')
                ->first();

            expect($notification->data)->toHaveKey('late_minutes');
            expect($notification->data['late_minutes'])->toBe(30);
        });
    });

    describe('notifyAttendanceClockOut', function () {
        it('sends push notification on clock out', function () {
            $pushService = app(PushNotificationService::class);

            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'status' => 'present',
                'working_minutes' => 480,
            ]);

            $result = $pushService->notifyAttendanceClockOut($attendance);

            expect($result)->toBeArray();
            expect($result['total'])->toBeGreaterThanOrEqual(1);
        });

        it('creates notification record with working hours', function () {
            $pushService = app(PushNotificationService::class);

            $attendance = Attendance::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'working_minutes' => 510,
            ]);

            $pushService->notifyAttendanceClockOut($attendance);

            $notification = Notification::where('user_id', $this->user->id)
                ->where('type', 'attendance_clock_out')
                ->first();

            expect($notification)->not->toBeNull();
            expect($notification->data['working_minutes'])->toBe(510);
        });
    });

    describe('notifyLeaveApproval', function () {
        it('sends push notification when leave is approved', function () {
            $pushService = app(PushNotificationService::class);

            $leaveRequest = LeaveRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'approved',
            ]);

            $result = $pushService->notifyLeaveApproval($leaveRequest);

            expect($result)->toBeArray();
            expect($result['total'])->toBeGreaterThanOrEqual(1);
        });

        it('sends push notification when leave is rejected', function () {
            $pushService = app(PushNotificationService::class);

            $leaveRequest = LeaveRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'rejected',
            ]);

            $result = $pushService->notifyLeaveApproval($leaveRequest);

            expect($result)->toBeArray();

            $notification = Notification::where('user_id', $this->user->id)
                ->where('type', 'leave_rejected')
                ->first();

            expect($notification)->not->toBeNull();
        });

        it('does not send notification for pending leave', function () {
            $pushService = app(PushNotificationService::class);

            $leaveRequest = LeaveRequest::factory()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'status' => 'pending',
            ]);

            $result = $pushService->notifyLeaveApproval($leaveRequest);

            expect($result)->toBeNull();
        });
    });

    describe('sendToUser', function () {
        it('sends custom notification to user', function () {
            $pushService = app(PushNotificationService::class);

            $result = $pushService->sendToUser(
                $this->user,
                'Custom Title',
                'Custom message body',
                'custom_type',
                ['key' => 'value']
            );

            expect($result)->toBeArray();
            expect($result['total'])->toBe(1);

            $this->assertDatabaseHas('notifications', [
                'user_id' => $this->user->id,
                'title' => 'Custom Title',
                'type' => 'custom_type',
            ]);
        });

        it('marks notification as push_sent', function () {
            $pushService = app(PushNotificationService::class);

            $pushService->sendToUser(
                $this->user,
                'Test Title',
                'Test body',
                'test_type',
                []
            );

            $notification = Notification::where('user_id', $this->user->id)->first();

            expect($notification->push_sent_at)->not->toBeNull();
        });
    });
});
