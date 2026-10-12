<?php

namespace App\Listeners;

use App\Events\LeaveRequestApproved;
use App\Events\LeaveRequestRejected;
use App\Services\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLeaveApprovalNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected PushNotificationService $pushNotificationService
    ) {}

    /**
     * Handle the LeaveRequestApproved event.
     */
    public function handle(LeaveRequestApproved|LeaveRequestRejected $event): void
    {
        $this->pushNotificationService->notifyLeaveApproval($event->leaveRequest);
    }
}
