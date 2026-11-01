<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\OfficeLocation;
use App\Models\PayrollItem;
use App\Models\User;
use App\Services\DashboardAnalyticsService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends AdminBaseController
{
    private const DEFAULT_LATITUDE = -6.2088;

    private const DEFAULT_LONGITUDE = 106.8456;

    public function __construct(
        private DashboardAnalyticsService $analyticsService
    ) {}

    /**
     * Get dashboard summary stats & attendance breakdown
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->emptyStatsResponse();
        }

        $companyNow = method_exists($company, 'now') ? $company->now() : now();
        $companyToday = method_exists($company, 'today') ? $company->today() : today();

        // Determine user role type
        $roleType = 'user';
        if ($user->is_superadmin || $user->hasRole('super-admin')) {
            $roleType = 'superadmin';
        } elseif ($user->hasAnyRole(['admin', 'hr-manager', 'payroll-manager', 'manager'])) {
            $roleType = 'admin';
        }

        // If user is regular employee, return dedicated personal dashboard data
        if ($roleType === 'user') {
            return $this->getUserDashboardResponse($user, $company, $companyToday, $companyNow);
        }

        $totalEmployees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        $attendanceBreakdown = $this->calculateAttendanceBreakdown($company, $companyToday, $totalEmployees);

        $pendingLeaves = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        $canViewPayroll = $user->is_superadmin || $user->hasRole('super-admin') || $user->can('view payroll') || $user->hasAnyRole(['admin', 'payroll-manager']);
        $totalPayrollThisMonth = $canViewPayroll ? $this->calculateTotalPayrollThisMonth($company, $companyNow) : 0;

        $recentClockIns = $this->getRecentClockIns($company, $companyToday);
        $officeLocations = $this->getOfficeLocations($company);
        $attendanceLocations = $this->getAttendanceLocations($company, $companyToday, $officeLocations);
        $analytics = $this->analyticsService->getAllAnalytics($company->id, $company);

        $systemStats = null;
        if ($roleType === 'superadmin') {
            $systemStats = [
                'total_users' => User::where('company_id', $company->id)->count(),
                'total_departments' => Department::where('company_id', $company->id)->count(),
                'active_offices' => OfficeLocation::where('company_id', $company->id)->where('is_active', true)->count(),
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'role_type' => $roleType,
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug ?? null,
                    'subscription_plan' => $company->subscription_plan ?? null,
                    'logo' => $company->logo ?? null,
                ],
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'present_today' => $attendanceBreakdown['present_today'],
                    'pending_leaves' => $pendingLeaves,
                    'total_payroll_this_month' => $totalPayrollThisMonth,
                    'attendance_percentage' => $attendanceBreakdown['percentage'],
                ],
                'attendance_today' => [
                    'present' => $attendanceBreakdown['present'],
                    'late' => $attendanceBreakdown['late'],
                    'on_leave' => $attendanceBreakdown['on_leave'],
                    'absent' => $attendanceBreakdown['absent'],
                ],
                'recent_clock_ins' => $recentClockIns,
                'office_locations' => $officeLocations,
                'attendance_locations' => $attendanceLocations,
                'analytics' => $analytics,
                'system_stats' => $systemStats,
            ],
        ]);
    }

    /**
     * Build personal self-service dashboard response for regular employees
     */
    private function getUserDashboardResponse(User $user, Company $company, CarbonInterface $companyToday, CarbonInterface $companyNow): JsonResponse
    {
        $employee = Employee::with(['department', 'position'])
            ->where('user_id', $user->id)
            ->first();

        $todayAttendance = null;
        if ($employee) {
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $companyToday)
                ->first();
        }

        $monthAttendances = collect();
        if ($employee) {
            $monthAttendances = Attendance::where('employee_id', $employee->id)
                ->whereMonth('date', $companyNow->month)
                ->whereYear('date', $companyNow->year)
                ->get();
        }

        $onLeaveCount = 0;
        if ($employee) {
            $onLeaveCount = LeaveRequest::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->whereMonth('start_date', $companyNow->month)
                ->whereYear('start_date', $companyNow->year)
                ->count();
        }

        $leaveBalances = $employee
            ? LeaveBalance::where('employee_id', $employee->id)->get()
            : collect();

        $recentHistory = $employee
            ? Attendance::where('employee_id', $employee->id)
                ->whereNotNull('clock_in')
                ->orderBy('date', 'desc')
                ->limit(5)
                ->get()
            : collect();

        $officeLocations = $this->getOfficeLocations($company);

        $announcements = Announcement::where('company_id', $company->id)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get(['id', 'title', 'content', 'created_at']);

        return response()->json([
            'success' => true,
            'data' => [
                'role_type' => 'user',
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'slug' => $company->slug ?? null,
                    'subscription_plan' => $company->subscription_plan ?? null,
                    'logo' => $company->logo ?? null,
                ],
                'employee' => $employee ? [
                    'id' => $employee->id,
                    'name' => $employee->first_name.' '.$employee->last_name,
                    'employee_id' => $employee->employee_id,
                    'department' => $employee->department?->name ?? 'Operasional',
                    'position' => $employee->position?->name ?? 'Staff',
                ] : null,
                'today_attendance' => [
                    'clock_in' => $this->formatClockInTime($todayAttendance?->clock_in),
                    'clock_out' => $this->formatClockInTime($todayAttendance?->clock_out),
                    'status' => $todayAttendance?->clock_in_status ?? 'absent',
                    'schedule' => [
                        'start_time' => '08:00',
                        'end_time' => '17:00',
                    ],
                ],
                'month_summary' => [
                    'present' => $monthAttendances->where('clock_in_status', 'on_time')->count(),
                    'late' => $monthAttendances->whereIn('clock_in_status', ['late', 'very_late'])->count(),
                    'on_leave' => $onLeaveCount,
                    'total_days' => $monthAttendances->count(),
                ],
                'leave_balance' => [
                    'remaining' => (int) $leaveBalances->sum('remaining_days'),
                    'used' => (int) $leaveBalances->sum('used_days'),
                    'total' => (int) $leaveBalances->sum('entitlement_days') ?: 12,
                ],
                'recent_history' => $recentHistory->map(fn ($att) => [
                    'id' => $att->id,
                    'date' => $att->date instanceof \DateTimeInterface ? $att->date->format('Y-m-d') : (string) $att->date,
                    'clock_in' => $this->formatClockInTime($att->clock_in),
                    'clock_out' => $this->formatClockInTime($att->clock_out),
                    'status' => $att->clock_in_status,
                ]),
                'office_locations' => $officeLocations,
                'announcements' => $announcements,
            ],
        ]);
    }

    /**
     * Fallback response when no company is available
     */
    private function emptyStatsResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'company' => null,
                'stats' => [
                    'total_employees' => 0,
                    'present_today' => 0,
                    'pending_leaves' => 0,
                    'total_payroll_this_month' => 0,
                    'new_employees_this_month' => 0,
                    'attendance_percentage' => 0,
                ],
                'attendance_today' => [
                    'present' => 0,
                    'late' => 0,
                    'on_leave' => 0,
                    'absent' => 0,
                ],
                'recent_clock_ins' => [],
                'analytics' => null,
            ],
        ]);
    }

    /**
     * Calculate today's attendance summary and breakdown counts
     *
     * @return array{present: int, late: int, on_leave: int, absent: int, present_today: int, percentage: float|int}
     */
    private function calculateAttendanceBreakdown(Company $company, CarbonInterface $companyToday, int $totalEmployees): array
    {
        $attendanceStats = Attendance::where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->selectRaw("
                SUM(CASE WHEN clock_in_status = 'on_time' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN clock_in_status IN ('late', 'very_late') THEN 1 ELSE 0 END) as late
            ")
            ->first();

        $present = (int) ($attendanceStats->present ?? 0);
        $late = (int) ($attendanceStats->late ?? 0);

        $onLeave = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $companyToday)
            ->whereDate('end_date', '>=', $companyToday)
            ->count();

        $presentToday = $present + $late;
        $absent = max(0, $totalEmployees - $present - $late - $onLeave);
        $attendancePercentage = $totalEmployees > 0
            ? round(($presentToday / $totalEmployees) * 100, 1)
            : 0;

        return [
            'present' => $present,
            'late' => $late,
            'on_leave' => $onLeave,
            'absent' => $absent,
            'present_today' => $presentToday,
            'percentage' => $attendancePercentage,
        ];
    }

    /**
     * Calculate total net payroll for current month
     */
    private function calculateTotalPayrollThisMonth(Company $company, CarbonInterface $companyNow): float
    {
        $total = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.company_id', $company->id)
            ->where('payrolls.period_month', $companyNow->month)
            ->where('payrolls.period_year', $companyNow->year)
            ->sum('payroll_items.net_salary');

        return (float) $total;
    }

    /**
     * Retrieve the 5 most recent clock-ins for today
     */
    private function getRecentClockIns(Company $company, CarbonInterface $companyToday): Collection
    {
        return Attendance::with([
            'employee:id,first_name,last_name,employee_id,avatar,department_id',
            'employee.department:id,name',
        ])
            ->where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->whereNotNull('clock_in')
            ->orderBy('clock_in', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'employee_name' => $this->formatEmployeeName($att->employee),
                'employee_id' => $att->employee?->employee_id,
                'avatar' => $att->employee?->avatar,
                'department' => $att->employee?->department?->name ?? '-',
                'clock_in' => $this->formatClockInTime($att->clock_in),
                'clock_in_status' => $att->clock_in_status,
            ]);
    }

    /**
     * Retrieve active office locations for geofencing
     */
    private function getOfficeLocations(Company $company): EloquentCollection
    {
        return OfficeLocation::where('company_id', $company->id)
            ->where('is_active', true)
            ->get(['id', 'name', 'code', 'address', 'city', 'latitude', 'longitude', 'radius', 'is_headquarters']);
    }

    /**
     * Map attendance GPS points with coordinate fallbacks
     */
    private function getAttendanceLocations(Company $company, CarbonInterface $companyToday, EloquentCollection $officeLocations): Collection
    {
        $defaultLat = self::DEFAULT_LATITUDE;
        $defaultLng = self::DEFAULT_LONGITUDE;

        if ($officeLocations->isNotEmpty()) {
            $hq = $officeLocations->firstWhere('is_headquarters', true) ?? $officeLocations->first();
            $defaultLat = $hq->latitude ?? $defaultLat;
            $defaultLng = $hq->longitude ?? $defaultLng;
        }

        return Attendance::with([
            'employee:id,first_name,last_name,employee_id,avatar,department_id',
            'employee.department:id,name',
        ])
            ->where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->whereNotNull('clock_in')
            ->get()
            ->map(function ($att, $index) use ($defaultLat, $defaultLng) {
                $lat = $att->clock_in_latitude ? (float) $att->clock_in_latitude : ($defaultLat + (sin($index + 1) * 0.0006));
                $lng = $att->clock_in_longitude ? (float) $att->clock_in_longitude : ($defaultLng + (cos($index + 1) * 0.0006));

                return [
                    'id' => $att->id,
                    'employee_name' => $this->formatEmployeeName($att->employee),
                    'employee_id' => $att->employee?->employee_id,
                    'avatar' => $att->employee?->avatar,
                    'department' => $att->employee?->department?->name ?? '-',
                    'clock_in' => $this->formatClockInTime($att->clock_in),
                    'clock_in_status' => $att->clock_in_status,
                    'latitude' => $lat,
                    'longitude' => $lng,
                ];
            });
    }

    /**
     * Format full employee name with fallback
     */
    private function formatEmployeeName(?Employee $employee): string
    {
        return $employee ? ($employee->first_name.' '.$employee->last_name) : 'Karyawan';
    }

    /**
     * Format clock-in timestamp to standard H:i:s
     */
    private function formatClockInTime(mixed $clockIn): ?string
    {
        if (! $clockIn) {
            return null;
        }

        return is_string($clockIn) ? $clockIn : $clockIn->format('H:i:s');
    }
}
