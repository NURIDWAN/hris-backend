<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Services\DashboardAnalyticsService;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardAnalyticsService $analyticsService
    ) {}

    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        // If user has no company (super admin), show empty dashboard
        if (! $company) {
            return view('dashboard', [
                'stats' => [
                    'total_employees' => 0,
                    'present_today' => 0,
                    'pending_leaves' => 0,
                    'total_payroll_this_month' => 0,
                    'new_employees_this_month' => 0,
                    'attendance_percentage' => 0,
                ],
                'attendanceToday' => [
                    'present' => 0,
                    'late' => 0,
                    'on_leave' => 0,
                    'absent' => 0,
                ],
                'recentClockIns' => collect(),
                'recentEmployees' => collect(),
                'pendingApprovals' => collect(),
                'birthdaysThisMonth' => collect(),
                'expiringContracts' => collect(),
                'analytics' => [
                    'attendance_chart' => ['labels' => [], 'datasets' => ['present' => [], 'late' => [], 'absent' => []]],
                    'employee_by_department' => ['labels' => [], 'data' => [], 'colors' => []],
                    'payroll_trend' => ['labels' => [], 'data' => []],
                    'employee_status' => ['labels' => [], 'data' => [], 'colors' => []],
                    'leave_statistics' => ['labels' => [], 'data' => [], 'colors' => []],
                    'monthly_summary' => ['total_employees' => 0, 'new_employees' => 0, 'resigned_employees' => 0, 'turnover_rate' => 0],
                ],
            ]);
        }

        // Main statistics
        $stats = $this->getStats($company);

        // Attendance breakdown for today
        $attendanceToday = $this->getAttendanceToday($company);

        // Recent clock-ins
        $recentClockIns = $this->getRecentClockIns($company);

        // Recent employees
        $recentEmployees = $this->getRecentEmployees($company->id);

        // Pending approvals
        $pendingApprovals = $this->getPendingApprovals($company->id);

        // Birthdays this month
        $birthdaysThisMonth = $this->getBirthdaysThisMonth($company->id, $company);

        // Expiring contracts
        $expiringContracts = $this->getExpiringContracts($company->id);

        // Analytics data for charts
        $analytics = $this->analyticsService->getAllAnalytics($company->id, $company);

        return view('dashboard', compact(
            'stats',
            'attendanceToday',
            'recentClockIns',
            'recentEmployees',
            'pendingApprovals',
            'birthdaysThisMonth',
            'expiringContracts',
            'analytics'
        ));
    }

    private function getStats(Company $company): array
    {
        $companyNow = $company->now();
        $companyToday = $company->today();

        // Total employees
        $totalEmployees = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        // Present today (using company timezone)
        $presentToday = Attendance::where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->whereNotNull('clock_in')
            ->count();

        // Pending leave requests
        $pendingLeaves = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')
            ->count();

        // Total payroll this month - Optimized with join instead of whereHas
        $totalPayrollThisMonth = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.company_id', $company->id)
            ->where('payrolls.period_month', $companyNow->month)
            ->where('payrolls.period_year', $companyNow->year)
            ->sum('payroll_items.net_salary');

        // New employees this month
        $newEmployeesThisMonth = Employee::where('company_id', $company->id)
            ->whereMonth('hire_date', $companyNow->month)
            ->whereYear('hire_date', $companyNow->year)
            ->count();

        // Attendance percentage
        $attendancePercentage = $totalEmployees > 0
            ? round(($presentToday / $totalEmployees) * 100, 1)
            : 0;

        return [
            'total_employees' => $totalEmployees,
            'present_today' => $presentToday,
            'pending_leaves' => $pendingLeaves,
            'total_payroll_this_month' => (float) $totalPayrollThisMonth,
            'new_employees_this_month' => $newEmployeesThisMonth,
            'attendance_percentage' => $attendancePercentage,
        ];
    }

    private function getAttendanceToday(Company $company): array
    {
        $companyToday = $company->today();

        // Optimized: Single query with database aggregate instead of loading all records
        $attendanceStats = Attendance::where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->selectRaw("
                SUM(CASE WHEN clock_in_status = 'on_time' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN clock_in_status IN ('late', 'very_late') THEN 1 ELSE 0 END) as late
            ")
            ->first();

        $present = (int) ($attendanceStats->present ?? 0);
        $late = (int) ($attendanceStats->late ?? 0);

        // Leave count for today
        $onLeave = LeaveRequest::where('company_id', $company->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $companyToday)
            ->whereDate('end_date', '>=', $companyToday)
            ->count();

        // Active employees
        $totalActive = Employee::where('company_id', $company->id)
            ->where('is_active', true)
            ->count();

        // Absent = Total - Present - Late - Leave
        $absent = max(0, $totalActive - $present - $late - $onLeave);

        return [
            'present' => $present,
            'late' => $late,
            'on_leave' => $onLeave,
            'absent' => $absent,
        ];
    }

    private function getRecentClockIns(Company $company)
    {
        return Attendance::with('employee')
            ->where('company_id', $company->id)
            ->whereDate('date', $company->today())
            ->whereNotNull('clock_in')
            ->orderByDesc('clock_in')
            ->take(5)
            ->get();
    }

    private function getRecentEmployees(int $companyId)
    {
        return Employee::with(['department', 'position'])
            ->where('company_id', $companyId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get();
    }

    private function getPendingApprovals(int $companyId)
    {
        $user = auth()->user();
        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->where('company_id', $companyId)
            ->where('status', 'pending');

        // If manager but not admin/hr/payroll, filter by their subordinates
        $adminRoles = ['admin', 'hr-manager', 'payroll-manager'];
        if ($user->hasRole('manager') && ! $user->hasAnyRole($adminRoles)) {
            $employee = $user->employee;
            if ($employee) {
                // Get employees where this user is the manager
                $subordinateIds = Employee::where('manager_id', $employee->id)->pluck('id');
                $query->whereIn('employee_id', $subordinateIds);
            } else {
                return collect();
            }
        }

        return $query->orderByDesc('created_at')
            ->take(5)
            ->get();
    }

    private function getBirthdaysThisMonth(int $companyId, ?Company $company = null)
    {
        $companyNow = $company ? $company->now() : now();
        $query = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereMonth('date_of_birth', $companyNow->month);

        // Use database-agnostic ordering
        if (config('database.default') === 'sqlite') {
            $query->orderByRaw("strftime('%d', date_of_birth)");
        } else {
            $query->orderByRaw('DAY(date_of_birth)');
        }

        return $query->get();
    }

    private function getExpiringContracts(int $companyId)
    {
        return Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->contractExpiringSoon(30)
            ->orderBy('contract_end_date')
            ->get();
    }
}
