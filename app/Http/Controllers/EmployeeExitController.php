<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeExit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeExitController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = app('tenant');

        $query = EmployeeExit::with(['employee.department', 'employee.position', 'approvedBy'])
            ->where('company_id', $tenant->id);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by exit type
        if ($request->filled('exit_type')) {
            $query->where('exit_type', $request->exit_type);
        }

        // Search by employee name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        $employeeExits = $query->latest()->paginate(15)->withQueryString();

        // Stats
        $stats = [
            'pending' => EmployeeExit::where('company_id', $tenant->id)->pending()->count(),
            'approved' => EmployeeExit::where('company_id', $tenant->id)->approved()->count(),
            'completed' => EmployeeExit::where('company_id', $tenant->id)->completed()->count(),
        ];

        return view('employee-exits.index', compact('employeeExits', 'stats'));
    }

    public function create(Request $request): View
    {
        $tenant = app('tenant');

        $employees = Employee::where('company_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $selectedEmployee = null;
        if ($request->filled('employee_id')) {
            $selectedEmployee = Employee::where('company_id', $tenant->id)
                ->where('id', $request->employee_id)
                ->first();
        }

        return view('employee-exits.create', compact('employees', 'selectedEmployee'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = app('tenant');

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'exit_type' => ['required', 'in:resignation,termination,retirement,contract_end,mutual_agreement,death'],
            'request_date' => ['required', 'date'],
            'last_working_day' => ['required', 'date', 'after_or_equal:request_date'],
            'effective_date' => ['required', 'date', 'after_or_equal:last_working_day'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_rehire_eligible' => ['nullable', 'boolean'],
            'rehire_notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'employee_id.required' => 'Karyawan wajib dipilih.',
            'exit_type.required' => 'Jenis keluar wajib dipilih.',
            'request_date.required' => 'Tanggal pengajuan wajib diisi.',
            'last_working_day.required' => 'Hari kerja terakhir wajib diisi.',
            'last_working_day.after_or_equal' => 'Hari kerja terakhir harus setelah atau sama dengan tanggal pengajuan.',
            'effective_date.required' => 'Tanggal efektif wajib diisi.',
            'effective_date.after_or_equal' => 'Tanggal efektif harus setelah atau sama dengan hari kerja terakhir.',
        ]);

        // Verify employee belongs to tenant
        $employee = Employee::where('company_id', $tenant->id)
            ->where('id', $validated['employee_id'])
            ->firstOrFail();

        EmployeeExit::create([
            'company_id' => $tenant->id,
            'employee_id' => $employee->id,
            'exit_type' => $validated['exit_type'],
            'request_date' => $validated['request_date'],
            'last_working_day' => $validated['last_working_day'],
            'effective_date' => $validated['effective_date'],
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_rehire_eligible' => $validated['is_rehire_eligible'] ?? true,
            'rehire_notes' => $validated['rehire_notes'] ?? null,
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('employee-exits.index')
            ->with('success', 'Data exit karyawan berhasil ditambahkan.');
    }

    public function show(EmployeeExit $employeeExit): View
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        $employeeExit->load(['employee.department', 'employee.position', 'approvedBy', 'createdBy']);

        return view('employee-exits.show', compact('employeeExit'));
    }

    public function edit(EmployeeExit $employeeExit): View|RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if (in_array($employeeExit->status, ['completed', 'cancelled'])) {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Data exit yang sudah selesai atau dibatalkan tidak dapat diedit.');
        }

        $employees = Employee::where('company_id', $tenant->id)
            ->where('is_active', true)
            ->orWhere('id', $employeeExit->employee_id)
            ->orderBy('first_name')
            ->get();

        return view('employee-exits.edit', compact('employeeExit', 'employees'));
    }

    public function update(Request $request, EmployeeExit $employeeExit): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if (in_array($employeeExit->status, ['completed', 'cancelled'])) {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Data exit yang sudah selesai atau dibatalkan tidak dapat diedit.');
        }

        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'exit_type' => ['required', 'in:resignation,termination,retirement,contract_end,mutual_agreement,death'],
            'request_date' => ['required', 'date'],
            'last_working_day' => ['required', 'date', 'after_or_equal:request_date'],
            'effective_date' => ['required', 'date', 'after_or_equal:last_working_day'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_rehire_eligible' => ['nullable', 'boolean'],
            'rehire_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $employeeExit->update([
            'exit_type' => $validated['exit_type'],
            'request_date' => $validated['request_date'],
            'last_working_day' => $validated['last_working_day'],
            'effective_date' => $validated['effective_date'],
            'reason' => $validated['reason'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_rehire_eligible' => $validated['is_rehire_eligible'] ?? true,
            'rehire_notes' => $validated['rehire_notes'] ?? null,
        ]);

        return redirect()->route('employee-exits.show', $employeeExit)
            ->with('success', 'Data exit karyawan berhasil diperbarui.');
    }

    public function approve(Request $request, EmployeeExit $employeeExit): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if ($employeeExit->status !== 'pending') {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Hanya exit dengan status menunggu yang dapat disetujui.');
        }

        $employeeExit->approve(auth()->user(), $request->approval_notes);

        return redirect()->route('employee-exits.show', $employeeExit)
            ->with('success', 'Exit karyawan berhasil disetujui.');
    }

    public function reject(Request $request, EmployeeExit $employeeExit): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if ($employeeExit->status !== 'pending') {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Hanya exit dengan status menunggu yang dapat ditolak.');
        }

        $employeeExit->reject(auth()->user(), $request->approval_notes);

        return redirect()->route('employee-exits.show', $employeeExit)
            ->with('success', 'Exit karyawan berhasil ditolak.');
    }

    public function complete(EmployeeExit $employeeExit): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if ($employeeExit->status !== 'approved') {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Hanya exit yang sudah disetujui yang dapat diselesaikan.');
        }

        if (! $employeeExit->isChecklistComplete()) {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Semua checklist harus diselesaikan sebelum menyelesaikan proses exit.');
        }

        $employeeExit->complete();

        return redirect()->route('employee-exits.show', $employeeExit)
            ->with('success', 'Proses exit karyawan berhasil diselesaikan. Karyawan telah dinonaktifkan.');
    }

    public function updateChecklist(Request $request, EmployeeExit $employeeExit): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if (! in_array($employeeExit->status, ['pending', 'approved'])) {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Checklist tidak dapat diubah untuk status ini.');
        }

        $employeeExit->update([
            'assets_returned' => $request->boolean('assets_returned'),
            'knowledge_transfer_done' => $request->boolean('knowledge_transfer_done'),
            'final_settlement_done' => $request->boolean('final_settlement_done'),
            'exit_interview_done' => $request->boolean('exit_interview_done'),
            'exit_interview_notes' => $request->exit_interview_notes,
        ]);

        return redirect()->route('employee-exits.show', $employeeExit)
            ->with('success', 'Checklist berhasil diperbarui.');
    }

    public function destroy(EmployeeExit $employeeExit): RedirectResponse
    {
        $tenant = app('tenant');

        if ($employeeExit->company_id !== $tenant->id) {
            abort(404);
        }

        if ($employeeExit->status === 'completed') {
            return redirect()->route('employee-exits.show', $employeeExit)
                ->with('error', 'Data exit yang sudah selesai tidak dapat dihapus.');
        }

        $employeeExit->delete();

        return redirect()->route('employee-exits.index')
            ->with('success', 'Data exit karyawan berhasil dihapus.');
    }
}
