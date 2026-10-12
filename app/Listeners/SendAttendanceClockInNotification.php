<?php

namespace App\Listeners;

use App\Events\AttendanceClockIn;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendAttendanceClockInNotification implements ShouldQueue
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
    public function handle(AttendanceClockIn $event): void
    {
        $this->pushNotificationService->notifyAttendanceClockIn($event->attendance);
    }
}
