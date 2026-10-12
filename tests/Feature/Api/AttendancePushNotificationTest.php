<?php

use App\Events\AttendanceClockIn;
use App\Events\AttendanceClockOut;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\Employee;
use App\Models\User;
use App\Models\WorkSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'enable_gps_validation' => false,
        'enable_face_recognition' => false,
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->workSchedule = WorkSchedule::factory()->create([
        'company_id' => $this->company->id,
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'work_schedule_id' => $this->workSchedule->id,
    ]);

    // Register device token for push notifications
    $this->deviceToken = DeviceToken::factory()->create([
        'user_id' => $this->user->id,
        'is_active' => true,
    ]);
});

describe('Attendance API - Push Notification Events', function () {
    describe('Clock In Events', function () {
        it('dispatches AttendanceClockIn event after successful clock in', function () {
            Event::fake([AttendanceClockIn::class]);

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertOk();

            Event::assertDispatched(AttendanceClockIn::class, function ($event) {
                return $event->attendance->employee_id === $this->employee->id;
            });
        });

        it('includes attendance data in event', function () {
            Event::fake([AttendanceClockIn::class]);

            Sanctum::actingAs($this->user, ['*']);

            $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            Event::assertDispatched(AttendanceClockIn::class, function ($event) {
                return $event->attendance->clock_in !== null
                    && $event->attendance->status !== null;
            });
        });

        it('does not dispatch event when clock in fails', function () {
            Event::fake([AttendanceClockIn::class]);

            Sanctum::actingAs($this->user, ['*']);

            // First clock in
            Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
            ]);

            // Try to clock in again (should fail)
            $response = $this->postJson('/api/v1/attendance/clock-in', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422);

            Event::assertNotDispatched(AttendanceClockIn::class);
        });
    });

    describe('Clock Out Events', function () {
        beforeEach(function () {
            $this->attendance = Attendance::factory()->clockedInOnly()->create([
                'company_id' => $this->company->id,
                'employee_id' => $this->employee->id,
                'date' => today(),
                'clock_in' => now()->subHours(8),
                'status' => 'present',
            ]);
        });

        it('dispatches AttendanceClockOut event after successful clock out', function () {
            Event::fake([AttendanceClockOut::class]);

            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertOk();

            Event::assertDispatched(AttendanceClockOut::class, function ($event) {
                return $event->attendance->employee_id === $this->employee->id
                    && $event->attendance->clock_out !== null;
            });
        });

        it('includes working hours in clock out event', function () {
            Event::fake([AttendanceClockOut::class]);

            Sanctum::actingAs($this->user, ['*']);

            $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            Event::assertDispatched(AttendanceClockOut::class, function ($event) {
                return $event->attendance->working_minutes > 0;
            });
        });

        it('does not dispatch event when clock out fails', function () {
            Event::fake([AttendanceClockOut::class]);

            Sanctum::actingAs($this->user, ['*']);

            // Already clocked out
            $this->attendance->update(['clock_out' => now()]);

            $response = $this->postJson('/api/v1/attendance/clock-out', [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
            ]);

            $response->assertStatus(422);

            Event::assertNotDispatched(AttendanceClockOut::class);
        });
    });
});
