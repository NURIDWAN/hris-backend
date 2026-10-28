<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttendanceController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Attendance::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'employee.position:id,name',
            'workSchedule:id,name,start_time,end_time',
            'officeLocation:id,name',
        ])->where('company_id', $companyId);

        if ($date = $request->input('date')) {
            $query->whereDate('date', $date);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($departmentId = $request->input('department_id')) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($month = $request->input('month')) {
            $parsed = Carbon::parse($month);
            $query->whereYear('date', $parsed->year)->whereMonth('date', $parsed->month);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $attendances = $query->orderBy('date', 'desc')
            ->orderBy('clock_in', 'desc')
            ->paginate($perPage);

        return $this->paginatedResponse($attendances, 'Daftar kehadiran berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'date' => ['required', 'date'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', 'in:present,absent,late,half_day,leave,holiday,weekend'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $existing = Attendance::where('company_id', $companyId)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->first();

        if ($existing) {
            return $this->errorResponse('Data kehadiran untuk karyawan pada tanggal tersebut sudah ada', 422);
        }

        $employee = Employee::where('company_id', $companyId)->findOrFail($validated['employee_id']);
        $date = Carbon::parse($validated['date']);
        $resolvedSchedule = method_exists($employee, 'resolveScheduleForDate')
            ? $employee->resolveScheduleForDate($date)
            : $employee->workSchedule;

        $clockInTime = ! empty($validated['clock_in'])
            ? $date->copy()->setTimeFromTimeString($validated['clock_in'])
            : null;
        $clockOutTime = ! empty($validated['clock_out'])
            ? $date->copy()->setTimeFromTimeString($validated['clock_out'])
            : null;

        $lateMinutes = $this->calculateLateMinutes($validated['clock_in'] ?? null, $resolvedSchedule);
        $workingMinutes = $this->calculateWorkingMinutes($validated['clock_in'] ?? null, $validated['clock_out'] ?? null);

        $attendance = Attendance::create([
            'company_id' => $companyId,
            'employee_id' => $employee->id,
            'work_schedule_id' => $resolvedSchedule?->id,
            'date' => $date->format('Y-m-d'),
            'scheduled_start' => $resolvedSchedule?->start_time ? Carbon::parse($resolvedSchedule->start_time)->format('H:i') : null,
            'scheduled_end' => $resolvedSchedule?->end_time ? Carbon::parse($resolvedSchedule->end_time)->format('H:i') : null,
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime,
            'status' => $validated['status'] ?? ($lateMinutes > 0 ? 'late' : 'present'),
            'is_manual_entry' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'admin_notes' => $validated['admin_notes'] ?? null,
            'late_minutes' => $lateMinutes,
            'working_minutes' => $workingMinutes,
        ]);

        return $this->successResponse(
            $attendance->load(['employee:id,first_name,last_name,employee_id', 'workSchedule']),
            'Data kehadiran manual berhasil disimpan',
            201
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $attendance = Attendance::with([
            'employee',
            'workSchedule',
            'officeLocation',
            'approvedBy:id,name,email',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($attendance, 'Detail kehadiran berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $attendance = Attendance::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'date' => ['sometimes', 'required', 'date'],
            'clock_in' => ['nullable', 'date_format:H:i'],
            'clock_out' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', 'in:present,absent,late,half_day,leave,holiday,weekend'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $date = isset($validated['date']) ? Carbon::parse($validated['date']) : Carbon::parse($attendance->date);
        $employee = $attendance->employee;
        $resolvedSchedule = method_exists($employee, 'resolveScheduleForDate')
            ? $employee->resolveScheduleForDate($date)
            : $employee->workSchedule;

        $clockInStr = array_key_exists('clock_in', $validated)
            ? $validated['clock_in']
            : ($attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : null);

        $clockOutStr = array_key_exists('clock_out', $validated)
            ? $validated['clock_out']
            : ($attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : null);

        $clockInTime = $clockInStr ? $date->copy()->setTimeFromTimeString($clockInStr) : null;
        $clockOutTime = $clockOutStr ? $date->copy()->setTimeFromTimeString($clockOutStr) : null;

        $lateMinutes = $this->calculateLateMinutes($clockInStr, $resolvedSchedule);
        $workingMinutes = $this->calculateWorkingMinutes($clockInStr, $clockOutStr);

        $updateData = [
            'date' => $date->format('Y-m-d'),
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime,
            'late_minutes' => $lateMinutes,
            'working_minutes' => $workingMinutes,
            'is_manual_entry' => true,
        ];

        if (isset($validated['status'])) {
            $updateData['status'] = $validated['status'];
        }
        if (array_key_exists('admin_notes', $validated)) {
            $updateData['admin_notes'] = $validated['admin_notes'];
        }

        $attendance->update($updateData);

        return $this->successResponse(
            $attendance->fresh(['employee', 'workSchedule']),
            'Data kehadiran berhasil diperbarui'
        );
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $attendance = Attendance::where('company_id', $companyId)->findOrFail($id);
        $attendance->delete();

        return $this->successResponse(null, 'Data kehadiran berhasil dihapus');
    }

    public function report(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $month = $request->input('month', now()->format('Y-m'));
        $date = Carbon::parse($month);

        $query = Attendance::where('company_id', $companyId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month);

        $totalRecords = (clone $query)->count();
        $present = (clone $query)->where('status', 'present')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $leave = (clone $query)->where('status', 'leave')->count();
        $halfDay = (clone $query)->where('status', 'half_day')->count();

        $activeEmployees = Employee::where('company_id', $companyId)->where('is_active', true)->count();

        return $this->successResponse([
            'month' => $date->format('Y-m'),
            'total_active_employees' => $activeEmployees,
            'summary' => [
                'total_records' => $totalRecords,
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'leave' => $leave,
                'half_day' => $halfDay,
                'on_time_rate' => ($totalRecords > 0) ? round(($present / $totalRecords) * 100, 1) : 0,
            ],
        ], 'Laporan rekapitulasi kehadiran berhasil diambil');
    }

    private function calculateLateMinutes(?string $clockIn, ?WorkSchedule $schedule): int
    {
        if (! $clockIn || ! $schedule || ! $schedule->start_time) {
            return 0;
        }

        $clockInTime = Carbon::parse($clockIn);
        $scheduledStart = Carbon::parse(
            $schedule->start_time instanceof Carbon ? $schedule->start_time->format('H:i') : $schedule->start_time
        );

        if ($clockInTime->gt($scheduledStart)) {
            return (int) $scheduledStart->diffInMinutes($clockInTime);
        }

        return 0;
    }

    private function calculateWorkingMinutes(?string $clockIn, ?string $clockOut): int
    {
        if (! $clockIn || ! $clockOut) {
            return 0;
        }

        $start = Carbon::parse($clockIn);
        $end = Carbon::parse($clockOut);

        return (int) $start->diffInMinutes($end);
    }
}
