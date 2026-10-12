<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveTypeRequest;
use App\Models\LeaveType;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = LeaveType::where('company_id', auth()->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $leaveTypes = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('leave-types.index', compact('leaveTypes'));
    }

    public function create()
    {
        return view('leave-types.create');
    }

    public function store(LeaveTypeRequest $request)
    {
        LeaveType::create([
            'company_id' => auth()->user()->company_id,
            ...$request->validated(),
        ]);

        return redirect()->route('leave-types.index')
            ->with('success', 'Jenis cuti berhasil ditambahkan.');
    }

    public function show(LeaveType $leaveType)
    {
        if ($leaveType->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        return view('leave-types.show', compact('leaveType'));
    }

    public function edit(LeaveType $leaveType)
    {
        if ($leaveType->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        return view('leave-types.edit', compact('leaveType'));
    }

    public function update(Request $request, LeaveType $leaveType)
    {
        if ($leaveType->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:10',
                \Illuminate\Validation\Rule::unique('leave_types')
                    ->where('company_id', $companyId)
                    ->ignore($leaveType->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_paid' => ['required', 'boolean'],
            'requires_approval' => ['required', 'boolean'],
            'requires_attachment' => ['required', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_notice_days' => ['required', 'integer', 'min:0', 'max:90'],
            'is_carry_forward' => ['required', 'boolean'],
            'max_carry_forward_days' => [
                'nullable',
                \Illuminate\Validation\Rule::requiredIf(fn () => $request->boolean('is_carry_forward')),
                'integer',
                'min:1',
                'max:365',
            ],
            'is_active' => ['required', 'boolean'],
            'color' => ['nullable', 'string', 'max:50'],
        ]);

        $leaveType->update($validated);

        return redirect()->route('leave-types.index')
            ->with('success', 'Jenis cuti berhasil diperbarui.');
    }

    public function destroy(LeaveType $leaveType)
    {
        if ($leaveType->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $leaveType->delete();

        return redirect()->route('leave-types.index')
            ->with('success', 'Jenis cuti berhasil dihapus.');
    }

    public function toggleStatus(LeaveType $leaveType)
    {
        if ($leaveType->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $leaveType->update(['is_active' => !$leaveType->is_active]);

        $status = $leaveType->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Jenis cuti berhasil {$status}.");
    }
}
