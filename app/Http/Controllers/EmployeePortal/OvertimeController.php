<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Services\OvertimeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeController extends Controller
{
    public function __construct(
        protected OvertimeCalculationService $calculationService
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $requests = OvertimeRequest::where('employee_id', $employee->id)
            ->latest('date')
            ->paginate(15);

        return view('portal.overtime.index', [
            'requests' => $requests,
            'employee' => $employee,
        ]);
    }

    public function create(): View
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $setting = OvertimeSetting::where('company_id', $employee->company_id)->first();

        return view('portal.overtime.create', [
            'employee' => $employee,
            'setting' => $setting,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'overtime_type' => 'required|in:weekday,weekend,holiday',
            'reason' => 'required|string|max:1000',
        ], [
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        // Check for duplicate
        $exists = OvertimeRequest::where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('date', $validated['date'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['date' => 'Anda sudah memiliki pengajuan lembur untuk tanggal tersebut.']);
        }

        $setting = OvertimeSetting::where('company_id', $employee->company_id)->first();

        // Calculate overtime hours
        $start = \Carbon\Carbon::parse($validated['start_time']);
        $end = \Carbon\Carbon::parse($validated['end_time']);
        $overtimeHours = round($start->diffInMinutes($end) / 60, 2);

        // Calculate overtime amount
        $overtimeAmount = 0;
        if ($setting && $employee->currentSalary) {
            $calculation = $this->calculationService->calculate(
                $employee,
                $overtimeHours,
                $validated['overtime_type'],
                $setting
            );
            $overtimeAmount = $calculation['overtime_amount'];
        }

        OvertimeRequest::create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'overtime_hours' => $overtimeHours,
            'overtime_type' => $validated['overtime_type'],
            'overtime_amount' => $overtimeAmount,
            'reason' => $validated['reason'],
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        return redirect()->route('portal.overtime.index')
            ->with('success', 'Pengajuan lembur berhasil diajukan.');
    }

    public function cancel(OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $user = auth()->user();
        $employee = Employee::where('user_id', $user->id)->firstOrFail();

        if ($overtimeRequest->employee_id !== $employee->id) {
            abort(403);
        }

        if (! $overtimeRequest->isPending()) {
            return back()->with('error', 'Hanya pengajuan dengan status pending yang dapat dibatalkan.');
        }

        $overtimeRequest->cancel();

        return back()->with('success', 'Pengajuan lembur berhasil dibatalkan.');
    }
}
