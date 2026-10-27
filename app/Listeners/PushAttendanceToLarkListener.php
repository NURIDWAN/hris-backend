<?php

namespace App\Listeners;

use App\Events\AttendanceClockIn;
use App\Events\AttendanceClockOut;
use App\Jobs\PushAttendanceRecordToLarkJob;

class PushAttendanceToLarkListener
{
    /**
     * Handle clock in event.
     */
    public function handleClockIn(AttendanceClockIn $event): void
    {
        $this->dispatchPushJob($event->attendance, 'clock_in');
    }

    /**
     * Handle clock out event.
     */
    public function handleClockOut(AttendanceClockOut $event): void
    {
        $this->dispatchPushJob($event->attendance, 'clock_out');
    }

    /**
     * Dispatch queue job if company has Lark integration active.
     */
    protected function dispatchPushJob($attendance, string $type): void
    {
        $company = $attendance->company;
        if (! $company) {
            return;
        }

        $integration = $company->larkIntegration;
        if (! $integration || ! $integration->is_active) {
            return;
        }

        PushAttendanceRecordToLarkJob::dispatch($attendance, $type);
    }
}
