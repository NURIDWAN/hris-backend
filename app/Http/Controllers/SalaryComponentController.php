<?php

namespace App\Http\Controllers;

use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryComponentController extends Controller
{
    public function index(Request $request)
    {
        $query = SalaryComponent::where('company_id', auth()->user()->company_id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $salaryComponents = $query->orderBy('sort_order')->orderBy('name')->paginate(10)->withQueryString();

        return view('salary-components.index', compact('salaryComponents'));
    }

    public function create()
    {
        return view('salary-components.create');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('salary_components')->where('company_id', $companyId),
            ],
            'type' => ['required', 'in:earning,deduction'],
            'category' => ['required', 'in:fixed,variable,benefit,tax,insurance,loan,other'],
            'calculation_type' => ['required', 'in:fixed,percentage,formula'],
            'default_amount' => [
                Rule::requiredIf(fn () => $request->calculation_type === 'fixed'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'percentage' => [
                Rule::requiredIf(fn () => $request->calculation_type === 'percentage'),
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'percentage_base' => ['nullable', 'string', 'max:50'],
            'formula' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_taxable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'is_mandatory' => ['required', 'boolean'],
            'is_attendance_based' => ['boolean'],
            'attendance_calculation' => ['nullable', 'in:daily,monthly'],
            'daily_rate' => [
                Rule::requiredIf(fn () => $request->boolean('is_attendance_based') && $request->attendance_calculation === 'daily'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'deduct_for_late' => ['boolean'],
            'deduct_for_early_leave' => ['boolean'],
            'include_half_days' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Handle boolean defaults
        $validated['is_attendance_based'] = $request->boolean('is_attendance_based');
        $validated['deduct_for_late'] = $request->boolean('deduct_for_late');
        $validated['deduct_for_early_leave'] = $request->boolean('deduct_for_early_leave');
        $validated['include_half_days'] = $request->boolean('include_half_days');

        SalaryComponent::create([
            'company_id' => $companyId,
            ...$validated,
        ]);

        return redirect()->route('salary-components.index')
            ->with('success', 'Komponen gaji berhasil ditambahkan.');
    }

    public function show(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        return view('salary-components.show', compact('salaryComponent'));
    }

    public function edit(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        return view('salary-components.edit', compact('salaryComponent'));
    }

    public function update(Request $request, SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $companyId = auth()->user()->company_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('salary_components')
                    ->where('company_id', $companyId)
                    ->ignore($salaryComponent->id),
            ],
            'type' => ['required', 'in:earning,deduction'],
            'category' => ['required', 'in:fixed,variable,benefit,tax,insurance,loan,other'],
            'calculation_type' => ['required', 'in:fixed,percentage,formula'],
            'default_amount' => [
                Rule::requiredIf(fn () => $request->calculation_type === 'fixed'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'percentage' => [
                Rule::requiredIf(fn () => $request->calculation_type === 'percentage'),
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'percentage_base' => ['nullable', 'string', 'max:50'],
            'formula' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_taxable' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'is_mandatory' => ['required', 'boolean'],
            'is_attendance_based' => ['boolean'],
            'attendance_calculation' => ['nullable', 'in:daily,monthly'],
            'daily_rate' => [
                Rule::requiredIf(fn () => $request->boolean('is_attendance_based') && $request->attendance_calculation === 'daily'),
                'nullable',
                'numeric',
                'min:0',
            ],
            'deduct_for_late' => ['boolean'],
            'deduct_for_early_leave' => ['boolean'],
            'include_half_days' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Handle boolean defaults
        $validated['is_attendance_based'] = $request->boolean('is_attendance_based');
        $validated['deduct_for_late'] = $request->boolean('deduct_for_late');
        $validated['deduct_for_early_leave'] = $request->boolean('deduct_for_early_leave');
        $validated['include_half_days'] = $request->boolean('include_half_days');

        $salaryComponent->update($validated);

        return redirect()->route('salary-components.index')
            ->with('success', 'Komponen gaji berhasil diperbarui.');
    }

    public function destroy(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $salaryComponent->delete();

        return redirect()->route('salary-components.index')
            ->with('success', 'Komponen gaji berhasil dihapus.');
    }

    public function toggleStatus(SalaryComponent $salaryComponent)
    {
        if ($salaryComponent->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        $salaryComponent->update(['is_active' => ! $salaryComponent->is_active]);

        $status = $salaryComponent->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->back()
            ->with('success', "Komponen gaji berhasil {$status}.");
    }
}
