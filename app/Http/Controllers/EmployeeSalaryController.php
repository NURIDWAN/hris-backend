<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeSalaryController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeSalary::with('employee')
            ->where('company_id', app('tenant')->id);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $employeeSalaries = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        return view('employee-salaries.index', compact('employeeSalaries'));
    }

    public function create()
    {
        $employees = Employee::where('company_id', app('tenant')->id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $salaryComponents = SalaryComponent::where('company_id', app('tenant')->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('employee-salaries.create', compact('employees', 'salaryComponents'));
    }

    public function store(Request $request)
    {
        $companyId = app('tenant')->id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:effective_date'],
            'payment_method' => ['required', 'in:transfer,cash'],
            'bank_name' => [
                Rule::requiredIf(fn () => $request->payment_method === 'transfer'),
                'nullable',
                'string',
                'max:100',
            ],
            'bank_account_number' => [
                Rule::requiredIf(fn () => $request->payment_method === 'transfer'),
                'nullable',
                'string',
                'max:50',
            ],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'components' => ['nullable', 'array'],
            'components.*' => ['array'],
            'components.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $companyId, $request) {
            // Deactivate previous active salary for this employee
            if ($validated['is_active']) {
                EmployeeSalary::where('company_id', $companyId)
                    ->where('employee_id', $validated['employee_id'])
                    ->where('is_active', true)
                    ->update(['is_active' => false, 'end_date' => $validated['effective_date']]);
            }

            $salary = EmployeeSalary::create([
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'basic_salary' => $validated['basic_salary'],
                'effective_date' => $validated['effective_date'],
                'end_date' => $validated['end_date'] ?? null,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'is_active' => $validated['is_active'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Auto-create BASIC component from basic_salary
            $basicComponent = SalaryComponent::where('company_id', $companyId)
                ->where('code', 'BASIC')
                ->first();

            if ($basicComponent) {
                EmployeeSalaryComponent::create([
                    'employee_salary_id' => $salary->id,
                    'salary_component_id' => $basicComponent->id,
                    'amount' => $validated['basic_salary'],
                ]);
            }

            // Create salary components
            if ($request->has('components')) {
                foreach ($request->components as $componentId => $componentData) {
                    if (! empty($componentData['amount']) && $componentData['amount'] > 0) {
                        EmployeeSalaryComponent::create([
                            'employee_salary_id' => $salary->id,
                            'salary_component_id' => $componentId,
                            'amount' => $componentData['amount'],
                            'notes' => $componentData['notes'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('employee-salaries.index')
            ->with('success', 'Pengaturan gaji karyawan berhasil disimpan.');
    }

    public function show(EmployeeSalary $employeeSalary)
    {
        if ($employeeSalary->company_id !== app('tenant')->id) {
            abort(404);
        }

        $employeeSalary->load(['employee', 'components.salaryComponent']);

        return view('employee-salaries.show', compact('employeeSalary'));
    }

    public function edit(EmployeeSalary $employeeSalary)
    {
        if ($employeeSalary->company_id !== app('tenant')->id) {
            abort(404);
        }

        $employees = Employee::where('company_id', app('tenant')->id)
            ->where('is_active', true)
            ->orderBy('first_name')
            ->get();

        $salaryComponents = SalaryComponent::where('company_id', app('tenant')->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $employeeSalary->load('components.salaryComponent');

        return view('employee-salaries.edit', compact('employeeSalary', 'employees', 'salaryComponents'));
    }

    public function update(Request $request, EmployeeSalary $employeeSalary)
    {
        if ($employeeSalary->company_id !== app('tenant')->id) {
            abort(404);
        }

        $companyId = app('tenant')->id;

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:effective_date'],
            'payment_method' => ['required', 'in:transfer,cash'],
            'bank_name' => [
                Rule::requiredIf(fn () => $request->payment_method === 'transfer'),
                'nullable',
                'string',
                'max:100',
            ],
            'bank_account_number' => [
                Rule::requiredIf(fn () => $request->payment_method === 'transfer'),
                'nullable',
                'string',
                'max:50',
            ],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'components' => ['nullable', 'array'],
            'components.*' => ['array'],
            'components.*.amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($validated, $companyId, $request, $employeeSalary) {
            // Deactivate other active salaries for this employee if this one is being activated
            if ($validated['is_active'] && ! $employeeSalary->is_active) {
                EmployeeSalary::where('company_id', $companyId)
                    ->where('employee_id', $validated['employee_id'])
                    ->where('is_active', true)
                    ->where('id', '!=', $employeeSalary->id)
                    ->update(['is_active' => false, 'end_date' => $validated['effective_date']]);
            }

            $employeeSalary->update([
                'employee_id' => $validated['employee_id'],
                'basic_salary' => $validated['basic_salary'],
                'effective_date' => $validated['effective_date'],
                'end_date' => $validated['end_date'] ?? null,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'is_active' => $validated['is_active'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // Update salary components
            $employeeSalary->components()->delete();

            // Auto-create BASIC component from basic_salary
            $basicComponent = SalaryComponent::where('company_id', $companyId)
                ->where('code', 'BASIC')
                ->first();

            if ($basicComponent) {
                EmployeeSalaryComponent::create([
                    'employee_salary_id' => $employeeSalary->id,
                    'salary_component_id' => $basicComponent->id,
                    'amount' => $validated['basic_salary'],
                ]);
            }

            if ($request->has('components')) {
                foreach ($request->components as $componentId => $componentData) {
                    if (! empty($componentData['amount']) && $componentData['amount'] > 0) {
                        EmployeeSalaryComponent::create([
                            'employee_salary_id' => $employeeSalary->id,
                            'salary_component_id' => $componentId,
                            'amount' => $componentData['amount'],
                            'notes' => $componentData['notes'] ?? null,
                        ]);
                    }
                }
            }
        });

        return redirect()->route('employee-salaries.index')
            ->with('success', 'Pengaturan gaji karyawan berhasil diperbarui.');
    }

    public function destroy(EmployeeSalary $employeeSalary)
    {
        if ($employeeSalary->company_id !== app('tenant')->id) {
            abort(404);
        }

        $employeeSalary->delete();

        return redirect()->route('employee-salaries.index')
            ->with('success', 'Pengaturan gaji karyawan berhasil dihapus.');
    }
}
