<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\User;

class PushNotificationService
{
    public function __construct(
        protected FcmService $fcmService
    ) {}

    /**
     * Send notification to user and record it.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function sendToUser(
        User $user,
        string $title,
        string $message,
        string $type,
        array $data = [],
        ?string $link = null
    ): array {
        // Create notification record
        $notification = Notification::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'link' => $link,
            'data' => $data,
        ]);

        // Send push notification
        $result = $this->fcmService->sendToUser(
            $user,
            [
                'title' => $title,
                'body' => $message,
            ],
            array_merge($data, [
                'notification_id' => (string) $notification->id,
                'type' => $type,
            ])
        );

        // Update notification with push status
        if ($result['success_count'] > 0) {
            $notification->update(['push_sent_at' => now()]);
        } elseif ($result['total'] > 0 && $result['failure_count'] > 0) {
            $notification->update(['push_error' => 'All devices failed']);
        }

        return $result;
    }

    /**
     * Notify user about attendance clock in.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function notifyAttendanceClockIn(Attendance $attendance): array
    {
        $employee = $attendance->employee;
        $user = $employee->user;

        $status = $attendance->status === 'late' ? 'terlambat' : 'tepat waktu';
        $time = $attendance->clock_in->format('H:i');

        $title = 'Clock In Berhasil';
        $message = "Anda telah melakukan clock in pukul {$time} ({$status})";

        if ($attendance->late_minutes > 0) {
            $message .= ". Terlambat {$attendance->late_minutes} menit.";
        }

        $data = [
            'attendance_id' => (string) $attendance->id,
            'clock_in' => $attendance->clock_in->toIso8601String(),
            'status' => $attendance->status,
            'late_minutes' => $attendance->late_minutes ?? 0,
        ];

        return $this->sendToUser(
            $user,
            $title,
            $message,
            'attendance_clock_in',
            $data
        );
    }

    /**
     * Notify user about attendance clock out.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function notifyAttendanceClockOut(Attendance $attendance): array
    {
        $employee = $attendance->employee;
        $user = $employee->user;

        $clockOut = $attendance->clock_out->format('H:i');
        $hours = floor($attendance->working_minutes / 60);
        $minutes = $attendance->working_minutes % 60;

        $title = 'Clock Out Berhasil';
        $message = "Anda telah melakukan clock out pukul {$clockOut}. Total kerja: {$hours} jam {$minutes} menit.";

        if ($attendance->overtime_minutes > 0) {
            $overtimeHours = floor($attendance->overtime_minutes / 60);
            $overtimeMinutes = $attendance->overtime_minutes % 60;
            $message .= " Lembur: {$overtimeHours} jam {$overtimeMinutes} menit.";
        }

        $data = [
            'attendance_id' => (string) $attendance->id,
            'clock_out' => $attendance->clock_out->toIso8601String(),
            'working_minutes' => $attendance->working_minutes,
            'overtime_minutes' => $attendance->overtime_minutes ?? 0,
        ];

        return $this->sendToUser(
            $user,
            $title,
            $message,
            'attendance_clock_out',
            $data
        );
    }

    /**
     * Notify approver about new leave request.
     */
    public function notifyLeaveRequested(LeaveRequest $leaveRequest, User $approver): void
    {
        $employeeName = $leaveRequest->employee->full_name;
        $leaveType = $leaveRequest->leaveType?->name ?? 'Cuti';
        $startDate = $leaveRequest->start_date->format('d M Y');
        $endDate = $leaveRequest->end_date->format('d M Y');

        $title = 'Pengajuan Cuti Baru';
        $message = "{$employeeName} mengajukan {$leaveType} untuk tanggal {$startDate} - {$endDate}.";

        $data = [
            'leave_request_id' => (string) $leaveRequest->id,
            'employee_name' => $employeeName,
            'leave_type' => $leaveType,
            'start_date' => $leaveRequest->start_date->toDateString(),
            'end_date' => $leaveRequest->end_date->toDateString(),
        ];

        $this->sendToUser(
            $approver,
            $title,
            $message,
            'leave_requested',
            $data,
            route('leave-requests.show', $leaveRequest->id)
        );
    }

    /**
     * Notify user about leave request approval/rejection.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}|null
     */
    public function notifyLeaveApproval(LeaveRequest $leaveRequest): ?array
    {
        if ($leaveRequest->status === 'pending') {
            return null;
        }

        $employee = $leaveRequest->employee;
        $user = $employee->user;

        $leaveType = $leaveRequest->leaveType?->name ?? 'Cuti';
        $startDate = $leaveRequest->start_date->format('d M Y');
        $endDate = $leaveRequest->end_date->format('d M Y');

        if ($leaveRequest->status === 'approved') {
            $title = 'Pengajuan Cuti Disetujui';
            $message = "Pengajuan {$leaveType} Anda tanggal {$startDate} - {$endDate} telah disetujui.";
            $type = 'leave_approved';
        } else {
            $title = 'Pengajuan Cuti Ditolak';
            $message = "Pengajuan {$leaveType} Anda tanggal {$startDate} - {$endDate} ditolak.";
            if ($leaveRequest->rejection_reason) {
                $message .= " Alasan: {$leaveRequest->rejection_reason}";
            }
            $type = 'leave_rejected';
        }

        $data = [
            'leave_request_id' => (string) $leaveRequest->id,
            'status' => $leaveRequest->status,
            'leave_type' => $leaveType,
            'start_date' => $leaveRequest->start_date->toDateString(),
            'end_date' => $leaveRequest->end_date->toDateString(),
        ];

        return $this->sendToUser(
            $user,
            $title,
            $message,
            $type,
            $data
        );
    }

    /**
     * Notify user about payroll/payslip availability.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function notifyPayrollReady(User $user, string $period, int $amount): array
    {
        $formattedAmount = 'Rp '.number_format($amount, 0, ',', '.');

        $title = 'Slip Gaji Tersedia';
        $message = "Slip gaji periode {$period} telah tersedia. Total: {$formattedAmount}";

        return $this->sendToUser(
            $user,
            $title,
            $message,
            'payroll_ready',
            [
                'period' => $period,
                'amount' => $amount,
            ]
        );
    }

    /**
     * Send announcement notification to user.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function notifyAnnouncement(User $user, string $title, string $excerpt, int $announcementId): array
    {
        return $this->sendToUser(
            $user,
            $title,
            $excerpt,
            'announcement',
            ['announcement_id' => (string) $announcementId],
            "/announcements/{$announcementId}"
        );
    }
}
