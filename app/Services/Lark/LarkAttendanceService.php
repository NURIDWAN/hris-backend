<?php

namespace App\Services\Lark;

use App\Models\Attendance;
use App\Models\Company;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class LarkAttendanceService
{
    public function __construct(
        protected LarkApiClient $apiClient,
        protected LarkEmployeeService $employeeService
    ) {}

    /**
     * Push a single attendance punch (clock_in or clock_out) to Lark.
     */
    public function pushAttendance(Attendance $attendance, string $type = 'clock_in'): array
    {
        $company = $attendance->company;
        if (! $company) {
            throw new Exception('Attendance record has no associated company.');
        }

        $integration = $company->larkIntegration;
        if (! $integration || ! $integration->is_active) {
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'Integrasi Lark tidak aktif.',
            ];
        }

        if ($type === 'clock_in' && ! $integration->push_clock_in) {
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'Sinkronisasi clock-in Lark dinonaktifkan.',
            ];
        }

        if ($type === 'clock_out' && ! $integration->push_clock_out) {
            return [
                'success' => false,
                'status' => 'skipped',
                'message' => 'Sinkronisasi clock-out Lark dinonaktifkan.',
            ];
        }

        $employee = $attendance->employee;
        if (! $employee) {
            throw new Exception('Attendance record has no associated employee.');
        }

        // If employee has no lark_user_id yet, attempt auto-mapping if email exists
        if (empty($employee->lark_user_id) && ! empty($employee->email)) {
            $this->employeeService->syncEmployees($company);
            $employee->refresh();
        }

        if (empty($employee->lark_user_id)) {
            $msg = "Karyawan {$employee->first_name} {$employee->last_name} belum dipetakan ke Lark User ID.";
            $attendance->update(['lark_sync_error' => $msg]);

            return [
                'success' => false,
                'status' => 'unmapped_employee',
                'message' => $msg,
            ];
        }

        $punchTime = $type === 'clock_in' ? $attendance->clock_in : $attendance->clock_out;
        if (! $punchTime) {
            return [
                'success' => false,
                'status' => 'empty_time',
                'message' => "Waktu {$type} tidak ditemukan pada absensi.",
            ];
        }

        $locationName = $attendance->officeLocation?->name
            ?? ($type === 'clock_in' ? 'Clock In - JagoGaji App' : 'Clock Out - JagoGaji App');

        $comment = $type === 'clock_in'
            ? ($attendance->clock_in_notes ?: 'Clock in via JagoGaji')
            : ($attendance->clock_out_notes ?: 'Clock out via JagoGaji');

        $recordPayload = [
            'user_id' => $employee->lark_user_id,
            'creator_id' => $employee->lark_user_id,
            'location_name' => $locationName,
            'check_time' => (string) $punchTime->timestamp,
            'comment' => $comment,
        ];

        try {
            $response = $this->apiClient->post(
                $integration,
                'attendance/v1/user_flows/batch_create?employee_type=employee_id',
                ['flow_records' => [$recordPayload]]
            );

            $createdRecords = $response['data']['flow_records'] ?? ($response['data']['user_flow_records'] ?? []);
            $recordId = ! empty($createdRecords) ? ($createdRecords[0]['record_id'] ?? null) : null;
            $fallbackRecordId = 'synced_'.$punchTime->timestamp.'_'.$employee->id;

            $attendance->update([
                'lark_record_id' => $recordId ?: ($attendance->lark_record_id ?: $fallbackRecordId),
                'lark_synced_at' => now(),
                'lark_sync_error' => null,
            ]);

            return [
                'success' => true,
                'status' => 'synced',
                'record_id' => $recordId ?: $fallbackRecordId,
            ];
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            Log::error('Failed pushing attendance to Lark', [
                'attendance_id' => $attendance->id,
                'employee_id' => $employee->id,
                'type' => $type,
                'error' => $errorMessage,
            ]);

            $attendance->update([
                'lark_sync_error' => $errorMessage,
            ]);

            throw $e;
        }
    }

    /**
     * Retry pushing unsynced attendances for a specific company and date.
     */
    public function retryUnsyncedAttendances(Company $company, ?Carbon $date = null): array
    {
        $targetDate = $date ?? $company->today();

        $attendances = Attendance::with(['employee', 'officeLocation'])
            ->where('company_id', $company->id)
            ->whereDate('date', $targetDate)
            ->whereNotNull('clock_in')
            ->whereNull('lark_synced_at')
            ->get();

        $synced = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($attendances as $attendance) {
            try {
                $result = $this->pushAttendance($attendance, 'clock_in');
                if ($result['success'] ?? false) {
                    // Also push clock_out if employee already clocked out
                    if ($attendance->clock_out) {
                        $this->pushAttendance($attendance, 'clock_out');
                    }
                    $synced++;
                } else {
                    $skipped++;
                }
            } catch (Exception) {
                $failed++;
            }
        }

        return [
            'total' => $attendances->count(),
            'synced' => $synced,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }
}
