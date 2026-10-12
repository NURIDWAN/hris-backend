<?php

namespace App\Listeners;

use App\Events\AttendanceClockOut;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAttendanceClockOutNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected PushNotificationService $pushNotificationService
    ) {}

    /**
     * Handle the event.
     */
    public function handle(AttendanceClockOut $event): void
    {
        $this->pushNotificationService->notifyAttendanceClockOut($event->attendance);
    }
}
