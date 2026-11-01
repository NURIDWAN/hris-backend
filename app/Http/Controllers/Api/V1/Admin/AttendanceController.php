<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\OfficeLocation;
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
            'employee:id,first_name,last_name,employee_id,department_id,position_id,avatar',
            'employee.department:id,name',
            'employee.position:id,name',
            'workSchedule:id,name,start_time,end_time',
            'officeLocation:id,name,code,address,city,latitude,longitude,radius,is_headquarters',
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

        if ($officeLocationId = $request->input('office_location_id')) {
            if ($officeLocationId !== 'ALL') {
                $query->where('office_location_id', $officeLocationId);
            }
        }

        if ($request->filled('month')) {
            $parsed = $this->parseMonthDate($request);
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
        $date = $this->parseMonthDate($request);

        $query = Attendance::where('company_id', $companyId)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month);

        if ($departmentId = $request->input('department_id')) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $totalRecords = (clone $query)->count();
        $present = (clone $query)->where('status', 'present')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $leave = (clone $query)->where('status', 'leave')->count();
        $halfDay = (clone $query)->where('status', 'half_day')->count();

        $activeEmployees = Employee::where('company_id', $companyId)->where('is_active', true)->count();

        // Aggregate attendance report per employee for the monthly summary table
        $employeeQuery = Employee::with(['department:id,name', 'position:id,name'])
            ->where('company_id', $companyId)
            ->where('is_active', true);

        if ($departmentId = $request->input('department_id')) {
            $employeeQuery->where('department_id', $departmentId);
        }

        $employees = $employeeQuery->get();

        $attendanceStats = (clone $query)
            ->selectRaw("
                employee_id,
                COUNT(*) as total_days,
                SUM(CASE WHEN status IN ('present', 'late') THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                COALESCE(SUM(late_minutes), 0) as total_late_minutes,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days
            ")
            ->groupBy('employee_id')
            ->get()
            ->keyBy('employee_id');

        $distinctDates = (clone $query)->distinct()->count('date');
        $baselineDays = max($distinctDates, 1);

        $report = $employees->map(function ($emp) use ($attendanceStats, $baselineDays) {
            $stat = $attendanceStats->get($emp->id);
            $presentDays = (int) ($stat->present_days ?? 0);
            $lateDays = (int) ($stat->late_days ?? 0);
            $totalLateMinutes = (int) ($stat->total_late_minutes ?? 0);
            $absentDays = (int) ($stat->absent_days ?? 0);
            $leaveDays = (int) ($stat->leave_days ?? 0);
            $totalDays = (int) ($stat->total_days ?? 0);

            $percentage = $baselineDays > 0 ? round(($presentDays / $baselineDays) * 100, 1) : 0;

            return [
                'employee_id' => $emp->id,
                'employee_number' => $emp->employee_id ?? '',
                'employee_name' => trim(($emp->first_name ?? '').' '.($emp->last_name ?? '')),
                'department_name' => $emp->department->name ?? 'Belum diatur',
                'position_name' => $emp->position->name ?? '-',
                'total_days' => $totalDays,
                'present_days' => $presentDays,
                'late_days' => $lateDays,
                'total_late_minutes' => $totalLateMinutes,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'attendance_percentage' => min($percentage, 100),
            ];
        })->values();

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
            'report' => $report,
        ], 'Laporan rekapitulasi kehadiran berhasil diambil');
    }

    private function parseMonthDate(Request $request): Carbon
    {
        $monthInput = $request->input('month');
        $yearInput = $request->input('year');

        if ($monthInput && is_numeric($monthInput)) {
            $year = $yearInput && is_numeric($yearInput) ? (int) $yearInput : (int) now()->year;
            $month = (int) $monthInput;

            return Carbon::createFromDate($year, $month, 1)->startOfMonth();
        }

        if ($monthInput && is_string($monthInput)) {
            try {
                return Carbon::parse($monthInput)->startOfMonth();
            } catch (\Exception $e) {
                // fallback to current date
            }
        }

        $year = $yearInput && is_numeric($yearInput) ? (int) $yearInput : (int) now()->year;

        return Carbon::createFromDate($year, (int) now()->month, 1)->startOfMonth();
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

    /**
     * Get multi-branch attendance map monitoring data
     */
    public function mapMonitor(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        $companyId = $company ? $company->id : $this->getCompanyId($request);

        $defaultLat = -6.2088;
        $defaultLng = 106.8456;

        $targetDate = $request->input('date');
        if (! $targetDate) {
            $targetDate = method_exists($company, 'today')
                ? $company->today()->format('Y-m-d')
                : now()->format('Y-m-d');
        }

        // Active office locations
        $offices = OfficeLocation::withCount('employees')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('is_headquarters', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        if ($offices->isNotEmpty()) {
            $hq = $offices->firstWhere('is_headquarters', true) ?? $offices->first();
            $defaultLat = (float) $hq->latitude;
            $defaultLng = (float) $hq->longitude;
        }

        // Build attendances query
        $query = Attendance::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id,avatar',
            'employee.department:id,name',
            'employee.position:id,name',
            'officeLocation',
        ])
            ->where('company_id', $companyId)
            ->whereDate('date', $targetDate);

        if ($officeId = $request->input('office_location_id')) {
            if ($officeId !== 'ALL') {
                $query->where('office_location_id', $officeId);
            }
        }

        if ($depId = $request->input('department_id')) {
            if ($depId !== 'ALL') {
                $query->whereHas('employee', function ($q) use ($depId) {
                    $q->where('department_id', $depId);
                });
            }
        }

        if ($search = $request->input('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%");
                });
            });
        }

        $attendances = $query->orderBy('clock_in', 'desc')->get();

        // Process attendances with coordinates, distance and geofence verification
        $processedAttendances = $attendances->map(function ($att, $index) use ($offices, $defaultLat, $defaultLng) {
            $hasCoords = $att->clock_in_latitude !== null && $att->clock_in_longitude !== null;
            $lat = $hasCoords ? (float) $att->clock_in_latitude : ($defaultLat + (sin($index + 1) * 0.0006));
            $lng = $hasCoords ? (float) $att->clock_in_longitude : ($defaultLng + (cos($index + 1) * 0.0006));

            // Determine matching office
            $office = $att->officeLocation;
            if (! $office && $att->office_location_id) {
                $office = $offices->firstWhere('id', $att->office_location_id);
            }
            if (! $office && $offices->isNotEmpty()) {
                // Find closest office
                $office = $offices->sortBy(fn ($o) => $o->distanceTo($lat, $lng))->first();
            }

            $distance = 0.0;
            $isWithinRadius = true;
            if ($office) {
                $distance = $office->distanceTo($lat, $lng);
                $isWithinRadius = $distance <= $office->radius;
            }

            $isOnTime = $att->clock_in_status === 'on_time' || ($att->status === 'present' && (int) $att->late_minutes === 0);
            $clockInStatus = $isOnTime ? 'on_time' : 'late';

            $photoUrl = null;
            if ($att->clock_in_photo) {
                $photoUrl = str_starts_with($att->clock_in_photo, 'http')
                    ? $att->clock_in_photo
                    : asset('storage/' . $att->clock_in_photo);
            }

            return [
                'id' => $att->id,
                'employee_id' => $att->employee_id,
                'employee_name' => trim(($att->employee?->first_name ?? '') . ' ' . ($att->employee?->last_name ?? '')),
                'employee_code' => $att->employee?->employee_id ?? '-',
                'avatar' => $att->employee?->avatar,
                'department' => $att->employee?->department?->name ?? 'Operasional',
                'position' => $att->employee?->position?->name ?? 'Staff',
                'date' => $att->date instanceof \DateTimeInterface ? $att->date->format('Y-m-d') : (string) $att->date,
                'clock_in' => $att->clock_in ? ($att->clock_in instanceof \DateTimeInterface ? $att->clock_in->format('H:i:s') : (string) $att->clock_in) : null,
                'clock_out' => $att->clock_out ? ($att->clock_out instanceof \DateTimeInterface ? $att->clock_out->format('H:i:s') : (string) $att->clock_out) : null,
                'status' => $att->status,
                'clock_in_status' => $clockInStatus,
                'late_minutes' => (int) ($att->late_minutes ?? 0),
                'latitude' => (float) $lat,
                'longitude' => (float) $lng,
                'office_id' => $office?->id,
                'office_name' => $office?->name ?? 'Kantor Utama',
                'office_radius' => $office?->radius ?? 100,
                'distance_meters' => (float) $distance,
                'is_within_radius' => (bool) $isWithinRadius,
                'clock_in_photo' => $photoUrl,
                'notes' => $att->clock_in_notes ?? $att->admin_notes ?? null,
            ];
        });

        // Compute office-level aggregation
        $officesData = $offices->map(function ($office) use ($processedAttendances) {
            $officeAtts = $processedAttendances->where('office_id', $office->id);
            $presentCount = $officeAtts->count();
            $onTimeCount = $officeAtts->where('clock_in_status', 'on_time')->count();
            $lateCount = $officeAtts->where('clock_in_status', 'late')->count();
            $outsideRadiusCount = $officeAtts->where('is_within_radius', false)->count();

            return [
                'id' => $office->id,
                'name' => $office->name,
                'code' => $office->code,
                'address' => $office->address,
                'city' => $office->city,
                'latitude' => (float) $office->latitude,
                'longitude' => (float) $office->longitude,
                'radius' => (int) $office->radius,
                'is_headquarters' => (bool) $office->is_headquarters,
                'assigned_count' => (int) ($office->employees_count ?? 0),
                'present_count' => $presentCount,
                'on_time_count' => $onTimeCount,
                'late_count' => $lateCount,
                'outside_radius_count' => $outsideRadiusCount,
            ];
        });

        // Filter status if requested
        $statusFilter = $request->input('status');
        $filteredAttendances = $processedAttendances;
        if ($statusFilter && $statusFilter !== 'ALL') {
            if ($statusFilter === 'on_time') {
                $filteredAttendances = $filteredAttendances->where('clock_in_status', 'on_time');
            } elseif ($statusFilter === 'late') {
                $filteredAttendances = $filteredAttendances->where('clock_in_status', 'late');
            } elseif ($statusFilter === 'outside_radius') {
                $filteredAttendances = $filteredAttendances->where('is_within_radius', false);
            }
        }

        $summary = [
            'total_offices' => $offices->count(),
            'total_present' => $processedAttendances->count(),
            'total_on_time' => $processedAttendances->where('clock_in_status', 'on_time')->count(),
            'total_late' => $processedAttendances->where('clock_in_status', 'late')->count(),
            'total_outside_radius' => $processedAttendances->where('is_within_radius', false)->count(),
        ];

        return $this->successResponse([
            'date' => $targetDate,
            'summary' => $summary,
            'offices' => $officesData->values(),
            'attendances' => $filteredAttendances->values(),
        ], 'Data monitoring peta presensi berhasil diambil');
    }
}
