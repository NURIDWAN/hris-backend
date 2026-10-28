<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\OfficeLocation;
use App\Models\PayrollItem;
use App\Services\DashboardAnalyticsService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class DashboardController extends Controller
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
        $company = $user->company ?? Company::first();

        if (! $company) {
            return $this->emptyStatsResponse();
        }

        $companyNow = method_exists($company, 'now') ? $company->now() : now();
        $companyToday = method_exists($company, 'today') ? $company->today() : today();

        $totalEmployees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        $attendanceBreakdown = $this->calculateAttendanceBreakdown($company, $companyToday, $totalEmployees);

        $pendingLeaves = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        $totalPayrollThisMonth = $this->calculateTotalPayrollThisMonth($company, $companyNow);
        $recentClockIns = $this->getRecentClockIns($company, $companyToday);
        $officeLocations = $this->getOfficeLocations($company);
        $attendanceLocations = $this->getAttendanceLocations($company, $companyToday, $officeLocations);
        $analytics = $this->analyticsService->getAllAnalytics($company->id, $company);

        return response()->json([
            'success' => true,
            'data' => [
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
