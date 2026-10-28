<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\SalaryComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeSalaryController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = EmployeeSalary::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'employee.position:id,name',
            'components.salaryComponent:id,name,code,type,category',
        ])->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->whereHas('employee', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $salaries = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->paginatedResponse($salaries, 'Daftar pengaturan gaji karyawan berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'effective_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:effective_date'],
            'payment_method' => ['required', 'in:transfer,cash'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'components' => ['nullable', 'array'],
            'components.*.salary_component_id' => [
                'required_with:components',
                Rule::exists('salary_components', 'id')->where('company_id', $companyId),
            ],
            'components.*.amount' => ['required_with:components', 'numeric', 'min:0'],
            'components.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $salary = DB::transaction(function () use ($validated, $companyId, $request) {
            $isActive = $request->has('is_active') ? $request->boolean('is_active') : true;

            if ($isActive) {
                EmployeeSalary::where('company_id', $companyId)
                    ->where('employee_id', $validated['employee_id'])
                    ->where('is_active', true)
                    ->update([
                        'is_active' => false,
                        'end_date' => $validated['effective_date'],
                    ]);
            }

            $empSalary = EmployeeSalary::create([
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'basic_salary' => $validated['basic_salary'],
                'effective_date' => $validated['effective_date'],
                'end_date' => $validated['end_date'] ?? null,
                'payment_method' => $validated['payment_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'is_active' => $isActive,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Auto-create or sync BASIC component
            $basicComponent = SalaryComponent::where('company_id', $companyId)
                ->where('code', 'BASIC')
                ->first();

            if ($basicComponent) {
                EmployeeSalaryComponent::create([
                    'employee_salary_id' => $empSalary->id,
                    'salary_component_id' => $basicComponent->id,
                    'amount' => $validated['basic_salary'],
                ]);
            }

            if (! empty($validated['components'])) {
                foreach ($validated['components'] as $item) {
                    if ((int) $item['salary_component_id'] === (int) $basicComponent?->id) {
                        continue;
                    }
                    if (! empty($item['amount']) && $item['amount'] > 0) {
                        EmployeeSalaryComponent::create([
                            'employee_salary_id' => $empSalary->id,
                            'salary_component_id' => $item['salary_component_id'],
                            'amount' => $item['amount'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }
            }

            return $empSalary;
        });

        return $this->successResponse(
            $salary->load(['employee', 'components.salaryComponent']),
            'Pengaturan gaji karyawan berhasil disimpan',
            201
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $salary = EmployeeSalary::with([
            'employee',
            'components.salaryComponent',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($salary, 'Detail pengaturan gaji karyawan berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $salary = EmployeeSalary::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'basic_salary' => ['sometimes', 'required', 'numeric', 'min:0'],
            'effective_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['nullable', 'date'],
            'payment_method' => ['sometimes', 'required', 'in:transfer,cash'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'components' => ['nullable', 'array'],
            'components.*.salary_component_id' => [
                'required_with:components',
                Rule::exists('salary_components', 'id')->where('company_id', $companyId),
            ],
            'components.*.amount' => ['required_with:components', 'numeric', 'min:0'],
            'components.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($validated, $salary, $companyId, $request) {
            $updateData = $validated;
            unset($updateData['components']);

            if ($request->has('is_active')) {
                $updateData['is_active'] = $request->boolean('is_active');
            }

            $salary->update($updateData);

            if ($request->has('components')) {
                // Delete non-basic existing components
                $basicComponent = SalaryComponent::where('company_id', $companyId)
                    ->where('code', 'BASIC')
                    ->first();

                EmployeeSalaryComponent::where('employee_salary_id', $salary->id)
                    ->where('salary_component_id', '!=', $basicComponent?->id)
                    ->delete();

                // If basic salary changed, update BASIC component
                if (isset($validated['basic_salary']) && $basicComponent) {
                    EmployeeSalaryComponent::updateOrCreate(
                        [
                            'employee_salary_id' => $salary->id,
                            'salary_component_id' => $basicComponent->id,
                        ],
                        ['amount' => $validated['basic_salary']]
                    );
                }

                foreach ($validated['components'] as $item) {
                    if ((int) $item['salary_component_id'] === (int) $basicComponent?->id) {
                        continue;
                    }
                    if (! empty($item['amount']) && $item['amount'] > 0) {
                        EmployeeSalaryComponent::create([
                            'employee_salary_id' => $salary->id,
                            'salary_component_id' => $item['salary_component_id'],
                            'amount' => $item['amount'],
                            'notes' => $item['notes'] ?? null,
                        ]);
                    }
                }
            }
        });

        return $this->successResponse(
            $salary->fresh(['employee', 'components.salaryComponent']),
            'Pengaturan gaji karyawan berhasil diperbarui'
        );
    }
}
