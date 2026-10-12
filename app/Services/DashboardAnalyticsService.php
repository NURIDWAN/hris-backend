<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollItem;
use Illuminate\Support\Facades\DB;

class DashboardAnalyticsService
{
    /**
     * Chart colors palette.
     */
    private array $colors = [
        '#3b82f6', // blue
        '#10b981', // green
        '#f59e0b', // amber
        '#ef4444', // red
        '#8b5cf6', // violet
        '#ec4899', // pink
        '#06b6d4', // cyan
        '#84cc16', // lime
        '#f97316', // orange
        '#6366f1', // indigo
    ];

    /**
     * Get attendance chart data for last N days.
     * Optimized: Single query with groupBy instead of N queries in loop.
     */
    public function getAttendanceChartData(int $companyId, int $days = 30, ?Company $company = null): array
    {
        $companyNow = $company ? $company->now() : now();
        $startDate = $companyNow->copy()->subDays($days - 1)->startOfDay();
        $endDate = $companyNow->copy()->endOfDay();

        $totalEmployees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        // Single query to get all attendance data grouped by date and status
        $attendanceData = Attendance::where('company_id', $companyId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('clock_in')
            ->select(
                DB::raw('DATE(date) as attendance_date'),
                'clock_in_status',
                DB::raw('COUNT(*) as count')
            )
            ->groupBy(DB::raw('DATE(date)'), 'clock_in_status')
            ->get()
            ->groupBy('attendance_date');

        $labels = [];
        $present = [];
        $late = [];
        $absent = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = $companyNow->copy()->subDays($i);
            $dateKey = $date->format('Y-m-d');
            $labels[] = $date->format('d M');

            $dayData = $attendanceData->get($dateKey, collect());

            $presentCount = $dayData->where('clock_in_status', 'on_time')->sum('count');
            $lateCount = $dayData->whereIn('clock_in_status', ['late', 'very_late'])->sum('count');
            $absentCount = max(0, $totalEmployees - $presentCount - $lateCount);

            $present[] = (int) $presentCount;
            $late[] = (int) $lateCount;
            $absent[] = (int) $absentCount;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
            ],
        ];
    }

    /**
     * Get employee count by department.
     */
    public function getEmployeeByDepartmentData(int $companyId): array
    {
        $departments = Department::where('company_id', $companyId)
            ->withCount(['employees' => function ($query) {
                $query->where('is_active', true);
            }])
            ->get()
            ->filter(fn ($dept) => $dept->employees_count > 0)
            ->sortByDesc('employees_count')
            ->values();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($departments as $index => $department) {
            $labels[] = $department->name;
            $data[] = $department->employees_count;
            $colors[] = $this->colors[$index % count($this->colors)];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    /**
     * Get payroll trend for last N months.
     * Optimized: Single query with join instead of N queries in loop.
     */
    public function getPayrollTrendData(int $companyId, int $months = 6, ?Company $company = null): array
    {
        $companyNow = $company ? $company->now() : now();

        // Calculate period range
        $periods = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = $companyNow->copy()->subMonths($i);
            $periods[] = [
                'year' => $date->year,
                'month' => $date->month,
                'label' => $date->translatedFormat('M Y'),
            ];
        }

        // Single query with join to get all payroll totals
        $payrollTotals = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
            ->where('payrolls.company_id', $companyId)
            ->where(function ($query) use ($periods) {
                foreach ($periods as $period) {
                    $query->orWhere(function ($q) use ($period) {
                        $q->where('payrolls.period_year', $period['year'])
                            ->where('payrolls.period_month', $period['month']);
                    });
                }
            })
            ->select(
                'payrolls.period_year',
                'payrolls.period_month',
                DB::raw('SUM(payroll_items.net_salary) as total')
            )
            ->groupBy('payrolls.period_year', 'payrolls.period_month')
            ->get()
            ->keyBy(fn ($item) => $item->period_year.'-'.str_pad($item->period_month, 2, '0', STR_PAD_LEFT));

        $labels = [];
        $data = [];

        foreach ($periods as $period) {
            $key = $period['year'].'-'.str_pad($period['month'], 2, '0', STR_PAD_LEFT);
            $labels[] = $period['label'];
            $data[] = (float) ($payrollTotals->get($key)?->total ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get employee distribution by employment status.
     */
    public function getEmployeeStatusDistribution(int $companyId): array
    {
        $statuses = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->select('employment_status', DB::raw('count(*) as count'))
            ->groupBy('employment_status')
            ->pluck('count', 'employment_status')
            ->toArray();

        $statusLabels = [
            'permanent' => 'Permanen',
            'contract' => 'Kontrak',
            'probation' => 'Probation',
            'internship' => 'Magang',
        ];

        $labels = [];
        $data = [];
        $colors = [
            'permanent' => '#10b981',
            'contract' => '#3b82f6',
            'probation' => '#f59e0b',
            'internship' => '#8b5cf6',
        ];
        $colorValues = [];

        foreach ($statuses as $status => $count) {
            $labels[] = $statusLabels[$status] ?? ucfirst($status);
            $data[] = $count;
            $colorValues[] = $colors[$status] ?? '#6b7280';
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colorValues,
        ];
    }

    /**
     * Get leave statistics by type for current year.
     */
    public function getLeaveStatistics(int $companyId, ?Company $company = null): array
    {
        $companyNow = $company ? $company->now() : now();
        $leaves = LeaveRequest::where('company_id', $companyId)
            ->where('status', 'approved')
            ->whereYear('start_date', $companyNow->year)
            ->select('leave_type_id', DB::raw('count(*) as count'))
            ->groupBy('leave_type_id')
            ->with('leaveType')
            ->get();

        $labels = [];
        $data = [];
        $colors = [];

        foreach ($leaves as $index => $leave) {
            $labels[] = $leave->leaveType->name ?? 'Unknown';
            $data[] = $leave->count;
            $colors[] = $this->colors[$index % count($this->colors)];
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'colors' => $colors,
        ];
    }

    /**
     * Get monthly summary statistics.
     */
    public function getMonthlySummary(int $companyId, ?Company $company = null): array
    {
        $companyNow = $company ? $company->now() : now();
        $totalEmployees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $newEmployees = Employee::where('company_id', $companyId)
            ->whereMonth('hire_date', $companyNow->month)
            ->whereYear('hire_date', $companyNow->year)
            ->count();

        $resignedEmployees = Employee::where('company_id', $companyId)
            ->whereMonth('resignation_date', $companyNow->month)
            ->whereYear('resignation_date', $companyNow->year)
            ->count();

        // Turnover rate = (resigned / total) * 100
        $turnoverRate = $totalEmployees > 0
            ? round(($resignedEmployees / $totalEmployees) * 100, 2)
            : 0;

        return [
            'total_employees' => $totalEmployees,
            'new_employees' => $newEmployees,
            'resigned_employees' => $resignedEmployees,
            'turnover_rate' => $turnoverRate,
        ];
    }

    /**
     * Get all analytics data for dashboard.
     */
    public function getAllAnalytics(int $companyId, ?Company $company = null): array
    {
        return [
            'attendance_chart' => $this->getAttendanceChartData($companyId, 14, $company),
            'employee_by_department' => $this->getEmployeeByDepartmentData($companyId),
            'payroll_trend' => $this->getPayrollTrendData($companyId, 6, $company),
            'employee_status' => $this->getEmployeeStatusDistribution($companyId),
            'leave_statistics' => $this->getLeaveStatistics($companyId, $company),
            'monthly_summary' => $this->getMonthlySummary($companyId, $company),
        ];
    }
}
