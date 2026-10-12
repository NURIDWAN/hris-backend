<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LeaveController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        $company = $user->company;
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $leaveRequests = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $company->now()->year)
            ->get();

        return view('portal.leave.index', compact('employee', 'leaveRequests', 'leaveBalances'));
    }

    public function create(): View
    {
        $user = auth()->user();
        $company = $user->company;
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $leaveTypes = LeaveType::where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get();

        $leaveBalances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $company->now()->year)
            ->get()
            ->keyBy('leave_type_id');

        return view('portal.leave.create', compact('employee', 'leaveTypes', 'leaveBalances'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        // Calculate days
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $days = $startDate->diffInDays($endDate) + 1;

        // Check leave balance
        $company = $user->company;
        $leaveBalance = LeaveBalance::where('employee_id', $employee->id)
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $company->now()->year)
            ->first();

        if ($leaveBalance) {
            $remainingBalance = $leaveBalance->remaining_days;
            if ($days > $remainingBalance) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['leave_type_id' => "Sisa cuti tidak mencukupi. Tersisa {$remainingBalance} hari."]);
            }
        }

        LeaveRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'leave_type_id' => $validated['leave_type_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'total_days' => $days,
            'reason' => $validated['reason'],
            'status' => 'pending',
        ]);

        // Add to pending balance
        if ($leaveBalance) {
            $leaveBalance->addPendingDays($days);
        }

        return redirect()->route('portal.leave.index')
            ->with('success', 'Pengajuan cuti berhasil dikirim.');
    }

    public function cancel(LeaveRequest $leaveRequest): RedirectResponse
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($leaveRequest->employee_id !== $employee->id) {
            abort(403);
        }

        if (! $leaveRequest->canBeCancelled()) {
            return redirect()->route('portal.leave.index')
                ->with('error', 'Pengajuan cuti ini tidak dapat dibatalkan.');
        }

        $leaveRequest->cancel(auth()->id());

        return redirect()->route('portal.leave.index')
            ->with('success', 'Pengajuan cuti berhasil dibatalkan.');
    }
}
