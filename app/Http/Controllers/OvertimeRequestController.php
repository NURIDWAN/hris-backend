<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Services\OvertimeCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OvertimeRequestController extends Controller
{
    public function __construct(
        protected OvertimeCalculationService $calculationService
    ) {}

    public function index(Request $request): View
    {
        $tenant = app('tenant');

        // Optimized eager loading - include soft-deleted employees for historical data
        $query = OvertimeRequest::with([
            'employee' => fn ($q) => $q->withTrashed()->with(['department', 'position']),
            'approver',
        ])->where('company_id', $tenant->id);

        // Search by employee name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->forDateRange($request->start_date, $request->end_date);
        }

        // Filter by overtime type
        if ($request->filled('overtime_type')) {
            $query->where('overtime_type', $request->overtime_type);
        }

        $requests = $query->latest('date')->paginate(20)->withQueryString();

        return view('overtime-requests.index', [
            'requests' => $requests,
        ]);
    }

    public function create(): View
    {
        $tenant = app('tenant');

        $employees = Employee::where('company_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $setting = OvertimeSetting::where('company_id', $tenant->id)->first();

        return view('overtime-requests.create', [
            'employees' => $employees,
            'setting' => $setting,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'overtime_type' => 'required|in:weekday,weekend,holiday',
            'reason' => 'required|string|max:1000',
            'notes' => 'nullable|string|max:1000',
        ], [
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        // Check for duplicate overtime request
        $exists = OvertimeRequest::where('company_id', $tenant->id)
            ->where('employee_id', $validated['employee_id'])
            ->whereDate('date', $validated['date'])
            ->exists();

        if ($exists) {
            return back()
                ->withInput()
                ->withErrors(['employee_id' => 'Karyawan sudah memiliki pengajuan lembur untuk tanggal tersebut.']);
        }

        $employee = Employee::findOrFail($validated['employee_id']);
        $setting = OvertimeSetting::where('company_id', $tenant->id)->first();

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
            'company_id' => $tenant->id,
            'employee_id' => $validated['employee_id'],
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'overtime_hours' => $overtimeHours,
            'overtime_type' => $validated['overtime_type'],
            'overtime_amount' => $overtimeAmount,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        return redirect()->route('overtime-requests.index')
            ->with('success', 'Pengajuan lembur berhasil dibuat.');
    }

    public function show(OvertimeRequest $overtimeRequest): View
    {
        $tenant = app('tenant');

        if ($overtimeRequest->company_id !== $tenant->id) {
            abort(404);
        }

        $overtimeRequest->load(['employee', 'employee.department', 'employee.position', 'approver']);

        return view('overtime-requests.show', [
            'overtimeRequest' => $overtimeRequest,
        ]);
    }

    public function approve(OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $tenant = app('tenant');

        if ($overtimeRequest->company_id !== $tenant->id) {
            abort(404);
        }

        if (! $overtimeRequest->isPending()) {
            return back()->with('error', 'Hanya pengajuan dengan status pending yang dapat disetujui.');
        }

        $overtimeRequest->approve(auth()->id());

        return back()->with('success', 'Pengajuan lembur berhasil disetujui.');
    }

    public function reject(Request $request, OvertimeRequest $overtimeRequest): RedirectResponse
    {
        $tenant = app('tenant');

        if ($overtimeRequest->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if (! $overtimeRequest->isPending()) {
            return back()->with('error', 'Hanya pengajuan dengan status pending yang dapat ditolak.');
        }

        $overtimeRequest->reject($validated['rejection_reason']);

        return back()->with('success', 'Pengajuan lembur berhasil ditolak.');
    }
}
