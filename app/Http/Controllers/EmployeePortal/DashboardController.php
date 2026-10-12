<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\PayrollItem;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $company = $user->company;
        $employee = Employee::where('user_id', $user->id)->first();

        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        // Today's attendance (using company timezone)
        $today = $company->today();
        $todayAttendance = Attendance::where('employee_id', $employee->id)
            ->where('date', $today)
            ->first();

        // Check for active overnight shift from yesterday
        if (! $todayAttendance) {
            $todayAttendance = Attendance::where('employee_id', $employee->id)
                ->whereDate('date', $today->copy()->subDay())
                ->whereNotNull('clock_in')
                ->whereNull('clock_out')
                ->whereHas('workSchedule', fn ($q) => $q->where('is_overnight', true))
                ->first();
        }

        // Leave balances
        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $company->now()->year)
            ->get();

        // Recent payslips
        $recentPayslips = PayrollItem::with('payroll')
            ->where('employee_id', $employee->id)
            ->whereHas('payroll', function ($query) {
                $query->where('status', 'paid');
            })
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();

        return view('portal.dashboard', compact(
            'employee',
            'todayAttendance',
            'leaveBalances',
            'recentPayslips'
        ));
    }
}
