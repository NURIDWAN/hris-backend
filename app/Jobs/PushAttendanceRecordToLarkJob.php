<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Services\Lark\LarkAttendanceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PushAttendanceRecordToLarkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public Attendance $attendance,
        public string $type = 'clock_in'
    ) {}

    public function handle(LarkAttendanceService $attendanceService): void
    {
        $attendanceService->pushAttendance($this->attendance, $this->type);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('PushAttendanceRecordToLarkJob permanently failed', [
            'attendance_id' => $this->attendance->id,
            'type' => $this->type,
            'error' => $exception?->getMessage(),
        ]);

        $this->attendance->update([
            'lark_sync_error' => 'Job failed: '.($exception?->getMessage() ?? 'Unknown error'),
        ]);
    }
}
