<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Attendance;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\OvertimeRequest;
use App\Models\PayrollItem;
use App\Models\Reimbursement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class DashboardController extends Controller
{
    #[OA\Get(
        path: '/dashboard',
        summary: 'Dashboard summary untuk mobile app',
        description: 'Mengembalikan ringkasan data untuk dashboard mobile app termasuk kehadiran, cuti, payroll, dan request pending',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Dashboard data berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'attendance',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'today_status', type: 'string', nullable: true, example: 'present'),
                                        new OA\Property(property: 'clock_in', type: 'string', nullable: true, example: '08:00'),
                                        new OA\Property(property: 'clock_out', type: 'string', nullable: true, example: '17:00'),
                                        new OA\Property(property: 'monthly_present', type: 'integer', example: 20),
                                        new OA\Property(property: 'monthly_absent', type: 'integer', example: 2),
                                        new OA\Property(property: 'monthly_late', type: 'integer', example: 3),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'leave',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'annual_balance', type: 'number', example: 12),
                                        new OA\Property(property: 'annual_used', type: 'number', example: 5),
                                        new OA\Property(property: 'pending_requests', type: 'integer', example: 1),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'payroll',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'last_payslip_month', type: 'string', nullable: true, example: 'Januari 2026'),
                                        new OA\Property(property: 'last_payslip_amount', type: 'number', example: 8500000),
                                        new OA\Property(property: 'ytd_gross', type: 'number', example: 120000000),
                                        new OA\Property(property: 'ytd_net', type: 'number', example: 102000000),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'requests',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'pending_overtime', type: 'integer', example: 2),
                                        new OA\Property(property: 'pending_reimbursement', type: 'integer', example: 1),
                                        new OA\Property(property: 'pending_leave', type: 'integer', example: 0),
                                    ]
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee data not found',
            ], 404);
        }

        $now = $company->now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        // Attendance data
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->whereDate('date', $company->today())
            ->first();

        $monthlyAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        // Leave balance (annual leave)
        $annualLeaveType = LeaveType::where('company_id', $employee->company_id)
            ->where('is_annual', true)
            ->first();

        $leaveBalance = null;
        if ($annualLeaveType) {
            $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
                ->where('leave_type_id', $annualLeaveType->id)
                ->where('year', $now->year)
                ->first();
        }

        // Pending leave requests
        $pendingLeaveRequests = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        // Payroll data (Year to date)
        $payrollItems = PayrollItem::where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) use ($now) {
                $query->whereYear('period_start', $now->year)
                    ->where('status', 'paid');
            })
            ->with('payroll')
            ->get();

        $lastPayslip = $payrollItems->sortByDesc(function ($item) {
            return $item->payroll->period_end;
        })->first();

        // Pending requests count
        $pendingOvertime = OvertimeRequest::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        $pendingReimbursement = Reimbursement::where('employee_id', $employee->id)
            ->where('status', 'pending')
            ->count();

        // Today's schedule (multi-shift aware)
        $employee->load('weeklySchedules.workSchedule', 'workSchedule');
        $todaySchedule = $employee->resolveScheduleForDate($company->today());

        return response()->json([
            'success' => true,
            'data' => [
                'attendance' => [
                    'today_status' => $todayAttendance?->status,
                    'clock_in' => $todayAttendance?->clock_in?->setTimezone($company->timezone)->format('H:i'),
                    'clock_out' => $todayAttendance?->clock_out?->setTimezone($company->timezone)->format('H:i'),
                    'monthly_present' => $monthlyAttendance->whereIn('status', ['present', 'late'])->count(),
                    'monthly_absent' => $monthlyAttendance->where('status', 'absent')->count(),
                    'monthly_late' => $monthlyAttendance->where('status', 'late')->count(),
                ],
                'schedule' => $todaySchedule ? [
                    'name' => $todaySchedule->name,
                    'start_time' => \Carbon\Carbon::parse($todaySchedule->start_time)->format('H:i'),
                    'end_time' => \Carbon\Carbon::parse($todaySchedule->end_time)->format('H:i'),
                    'is_overnight' => $todaySchedule->is_overnight,
                ] : null,
                'leave' => [
                    'annual_balance' => $leaveBalance?->entitled_days ?? 0,
                    'annual_used' => $leaveBalance?->used_days ?? 0,
                    'pending_requests' => $pendingLeaveRequests,
                ],
                'payroll' => [
                    'last_payslip_month' => $lastPayslip?->payroll?->period_start?->translatedFormat('F Y'),
                    'last_payslip_amount' => $lastPayslip?->net_salary ?? 0,
                    'ytd_gross' => $payrollItems->sum('gross_salary'),
                    'ytd_net' => $payrollItems->sum('net_salary'),
                ],
                'requests' => [
                    'pending_overtime' => $pendingOvertime,
                    'pending_reimbursement' => $pendingReimbursement,
                    'pending_leave' => $pendingLeaveRequests,
                ],
            ],
        ]);
    }

    #[OA\Get(
        path: '/dashboard/attendance-chart',
        summary: 'Data chart kehadiran',
        description: 'Mengembalikan data untuk chart kehadiran dalam format yang siap digunakan oleh chart library',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'days',
                in: 'query',
                description: 'Jumlah hari (default: 7, max: 30)',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 7, maximum: 30)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Chart data berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'labels',
                                    type: 'array',
                                    items: new OA\Items(type: 'string'),
                                    example: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
                                ),
                                new OA\Property(
                                    property: 'datasets',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'label', type: 'string', example: 'Jam Kerja'),
                                            new OA\Property(
                                                property: 'data',
                                                type: 'array',
                                                items: new OA\Items(type: 'number'),
                                                example: [8, 8.5, 7.5, 8, 9, 0, 0]
                                            ),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function attendanceChart(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee data not found',
            ], 404);
        }

        $company = $user->company;
        $days = min((int) $request->input('days', 7), 30);
        $startDate = $company->now()->subDays($days - 1)->startOfDay();
        $endDate = $company->now()->endOfDay();

        $attendances = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('date')
            ->get()
            ->keyBy(fn ($item) => $item->date->toDateString());

        $labels = [];
        $workingHours = [];

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $labels[] = $date->translatedFormat('D, d');
            $dateKey = $date->toDateString();

            if (isset($attendances[$dateKey])) {
                $workingHours[] = round($attendances[$dateKey]->working_minutes / 60, 1);
            } else {
                $workingHours[] = 0;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'datasets' => [
                    [
                        'label' => 'Jam Kerja',
                        'data' => $workingHours,
                    ],
                ],
            ],
        ]);
    }

    #[OA\Get(
        path: '/dashboard/quick-stats',
        summary: 'Statistik cepat bulan ini',
        description: 'Mengembalikan statistik ringkas untuk bulan berjalan',
        tags: ['Dashboard'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Quick stats berhasil diambil',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'total_working_days_this_month', type: 'integer', example: 22),
                                new OA\Property(property: 'total_present_days', type: 'integer', example: 20),
                                new OA\Property(property: 'total_absent_days', type: 'integer', example: 2),
                                new OA\Property(property: 'total_leave_days', type: 'integer', example: 1),
                                new OA\Property(property: 'total_late_count', type: 'integer', example: 3),
                                new OA\Property(property: 'total_overtime_hours', type: 'number', example: 12.5),
                                new OA\Property(property: 'attendance_percentage', type: 'number', example: 90.9),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function quickStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee data not found',
            ], 404);
        }

        $company = $user->company;
        $now = $company->now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $monthlyAttendance = Attendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->get();

        $totalRecords = $monthlyAttendance->count();
        $presentDays = $monthlyAttendance->whereIn('status', ['present', 'late'])->count();
        $absentDays = $monthlyAttendance->where('status', 'absent')->count();
        $leaveDays = $monthlyAttendance->where('status', 'leave')->count();
        $lateCount = $monthlyAttendance->where('status', 'late')->count();
        $totalOvertimeMinutes = $monthlyAttendance->sum('overtime_minutes');

        $attendancePercentage = $totalRecords > 0
            ? round(($presentDays / $totalRecords) * 100, 1)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_working_days_this_month' => $totalRecords,
                'total_present_days' => $presentDays,
                'total_absent_days' => $absentDays,
                'total_leave_days' => $leaveDays,
                'total_late_count' => $lateCount,
                'total_overtime_hours' => round($totalOvertimeMinutes / 60, 1),
                'attendance_percentage' => $attendancePercentage,
            ],
        ]);
    }
}
