<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use App\Services\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardAnalyticsService $analyticsService
    ) {}

    /**
     * Get dashboard summary stats & attendance breakdown
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = $user->company;

        if (! $company) {
            // If superadmin without company, fallback to default or first company
            $company = Company::first();
        }

        if (! $company) {
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

        $companyNow = method_exists($company, 'now') ? $company->now() : now();
        $companyToday = method_exists($company, 'today') ? $company->today() : today();

        // 1. Total active employees
        $totalEmployees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        // 2. Attendance today
        $attendanceStats = Attendance::where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->selectRaw("
                SUM(CASE WHEN clock_in_status = 'on_time' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN clock_in_status IN ('late', 'very_late') THEN 1 ELSE 0 END) as late
            ")
            ->first();

        $present = (int) ($attendanceStats->present ?? 0);
        $late = (int) ($attendanceStats->late ?? 0);

        // Leave count today
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

        // 3. Pending leave requests
        $pendingLeaves = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        // 4. Payroll this month
        $totalPayrollThisMonth = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.company_id', $company->id)
            ->where('payrolls.period_month', $companyNow->month)
            ->where('payrolls.period_year', $companyNow->year)
            ->sum('payroll_items.net_salary');

        // 5. Recent Clock Ins
        $recentClockIns = Attendance::with(['employee:id,first_name,last_name,employee_id,avatar,department_id', 'employee.department:id,name'])
            ->where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->whereNotNull('clock_in')
            ->orderBy('clock_in', 'desc')
            ->limit(5)
            ->get()
            ->map(fn ($att) => [
                'id' => $att->id,
                'employee_name' => $att->employee ? ($att->employee->first_name . ' ' . $att->employee->last_name) : 'Karyawan',
                'employee_id' => $att->employee?->employee_id,
                'avatar' => $att->employee?->avatar,
                'department' => $att->employee?->department?->name ?? '-',
                'clock_in' => $att->clock_in ? (is_string($att->clock_in) ? $att->clock_in : $att->clock_in->format('H:i:s')) : null,
                'clock_in_status' => $att->clock_in_status,
            ]);

        // 6. Analytics charts
        $analytics = $this->analyticsService->getAllAnalytics($company->id, $company);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_employees' => $totalEmployees,
                    'present_today' => $presentToday,
                    'pending_leaves' => $pendingLeaves,
                    'total_payroll_this_month' => (float) $totalPayrollThisMonth,
                    'attendance_percentage' => $attendancePercentage,
                ],
                'attendance_today' => [
                    'present' => $present,
                    'late' => $late,
                    'on_leave' => $onLeave,
                    'absent' => $absent,
                ],
                'recent_clock_ins' => $recentClockIns,
                'analytics' => $analytics,
            ],
        ]);
    }
}
