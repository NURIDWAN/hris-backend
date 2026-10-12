<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveBalanceController extends Controller
{
    public function index(Request $request)
    {
        $company = auth()->user()->company;
        $companyId = $company->id;
        $year = $request->get('year', $company->now()->year);

        // Optimized: Use join for search instead of whereHas
        $query = LeaveBalance::with(['employee', 'leaveType'])
            ->where('leave_balances.company_id', $companyId)
            ->where('leave_balances.year', $year);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->join('employees', 'leave_balances.employee_id', '=', 'employees.id')
                ->where(function ($q) use ($search) {
                    $q->where('employees.first_name', 'like', "%{$search}%")
                        ->orWhere('employees.last_name', 'like', "%{$search}%")
                        ->orWhere('employees.employee_id', 'like', "%{$search}%");
                })
                ->select('leave_balances.*');
        }

        if ($request->filled('leave_type_id')) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $leaveBalances = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('leave-balances.index', compact('leaveBalances', 'leaveTypes', 'employees', 'year'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;

        $employees = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $leaveTypes = LeaveType::where('company_id', $companyId)
            ->active()
            ->orderBy('name')
            ->get();

        return view('leave-balances.create', compact('employees', 'leaveTypes'));
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where('company_id', $companyId),
            ],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'entitled_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'carried_forward_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'adjustment_days' => ['nullable', 'numeric', 'min:-365', 'max:365'],
            'adjustment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        // Check if balance already exists
        $exists = LeaveBalance::where('company_id', $companyId)
            ->where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $validated['year'])
            ->exists();

        if ($exists) {
            return back()->withErrors(['employee_id' => 'Saldo cuti untuk karyawan, jenis cuti, dan tahun ini sudah ada.'])->withInput();
        }

        LeaveBalance::create([
            'company_id' => $companyId,
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'year' => $validated['year'],
            'entitled_days' => $validated['entitled_days'],
            'used_days' => 0,
            'pending_days' => 0,
            'carried_forward_days' => $validated['carried_forward_days'] ?? 0,
            'adjustment_days' => $validated['adjustment_days'] ?? 0,
            'adjustment_notes' => $validated['adjustment_notes'],
        ]);

        return redirect()->route('leave-balances.index')
            ->with('success', 'Saldo cuti berhasil ditambahkan.');
    }

    public function edit(LeaveBalance $leaveBalance)
    {
        if ($leaveBalance->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $leaveBalance->load(['employee', 'leaveType']);

        return view('leave-balances.edit', compact('leaveBalance'));
    }

    public function update(Request $request, LeaveBalance $leaveBalance)
    {
        if ($leaveBalance->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $validated = $request->validate([
            'entitled_days' => ['required', 'numeric', 'min:0', 'max:365'],
            'carried_forward_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'adjustment_days' => ['nullable', 'numeric', 'min:-365', 'max:365'],
            'adjustment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $leaveBalance->update([
            'entitled_days' => $validated['entitled_days'],
            'carried_forward_days' => $validated['carried_forward_days'] ?? 0,
            'adjustment_days' => $validated['adjustment_days'] ?? 0,
            'adjustment_notes' => $validated['adjustment_notes'],
        ]);

        return redirect()->route('leave-balances.index')
            ->with('success', 'Saldo cuti berhasil diperbarui.');
    }

    public function destroy(LeaveBalance $leaveBalance)
    {
        if ($leaveBalance->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        // Don't allow deletion if there are used or pending days
        if ($leaveBalance->used_days > 0 || $leaveBalance->pending_days > 0) {
            return back()->with('error', 'Tidak dapat menghapus saldo cuti yang sudah digunakan atau memiliki pengajuan pending.');
        }

        $leaveBalance->delete();

        return redirect()->route('leave-balances.index')
            ->with('success', 'Saldo cuti berhasil dihapus.');
    }

    public function generateBulk(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where('company_id', $companyId),
            ],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);

        // Get all active employees who don't have balance for this type and year
        $employeeIds = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotIn('id', function ($query) use ($validated) {
                $query->select('employee_id')
                    ->from('leave_balances')
                    ->where('leave_type_id', $validated['leave_type_id'])
                    ->where('year', $validated['year'])
                    ->whereNull('deleted_at');
            })
            ->pluck('id');

        if ($employeeIds->isEmpty()) {
            return back()->with('info', 'Semua karyawan aktif sudah memiliki saldo cuti untuk jenis dan tahun ini.');
        }

        $created = 0;
        DB::transaction(function () use ($employeeIds, $companyId, $leaveType, $validated, &$created) {
            foreach ($employeeIds as $employeeId) {
                LeaveBalance::create([
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                    'leave_type_id' => $validated['leave_type_id'],
                    'year' => $validated['year'],
                    'entitled_days' => $leaveType->default_days,
                    'used_days' => 0,
                    'pending_days' => 0,
                    'carried_forward_days' => 0,
                    'adjustment_days' => 0,
                ]);
                $created++;
            }
        });

        return redirect()->route('leave-balances.index', ['year' => $validated['year']])
            ->with('success', "Berhasil membuat {$created} saldo cuti untuk karyawan.");
    }
}
