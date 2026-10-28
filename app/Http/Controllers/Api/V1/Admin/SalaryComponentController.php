<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\SalaryComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryComponentController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = SalaryComponent::where('company_id', $companyId);

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $components = $query->orderBy('sort_order', 'asc')->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($components, 'Daftar komponen gaji berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('salary_components', 'code')->where('company_id', $companyId),
            ],
            'type' => ['required', 'in:earning,deduction'],
            'category' => ['required', 'in:fixed,variable,benefit,tax,insurance,loan,other'],
            'calculation_type' => ['nullable', 'in:fixed,percentage,formula'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'percentage_base' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_attendance_based' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $component = SalaryComponent::create([
            ...$validated,
            'company_id' => $companyId,
            'calculation_type' => $validated['calculation_type'] ?? 'fixed',
            'is_taxable' => $request->boolean('is_taxable', true),
            'is_mandatory' => $request->boolean('is_mandatory'),
            'is_attendance_based' => $request->boolean('is_attendance_based'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return $this->successResponse($component, 'Komponen gaji berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $component = SalaryComponent::where('company_id', $companyId)->findOrFail($id);

        return $this->successResponse($component, 'Detail komponen gaji berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $component = SalaryComponent::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('salary_components', 'code')->where('company_id', $companyId)->ignore($component->id),
            ],
            'type' => ['sometimes', 'required', 'in:earning,deduction'],
            'category' => ['sometimes', 'required', 'in:fixed,variable,benefit,tax,insurance,loan,other'],
            'calculation_type' => ['nullable', 'in:fixed,percentage,formula'],
            'default_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'percentage_base' => ['nullable', 'string'],
            'formula' => ['nullable', 'string'],
            'is_taxable' => ['nullable', 'boolean'],
            'is_mandatory' => ['nullable', 'boolean'],
            'is_attendance_based' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $booleans = ['is_taxable', 'is_mandatory', 'is_attendance_based', 'is_active'];
        foreach ($booleans as $field) {
            if ($request->has($field)) {
                $validated[$field] = $request->boolean($field);
            }
        }

        $component->update($validated);

        return $this->successResponse($component->fresh(), 'Komponen gaji berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $component = SalaryComponent::where('company_id', $companyId)->findOrFail($id);

        if ($component->code === 'BASIC') {
            return $this->errorResponse('Komponen Gaji Pokok (BASIC) adalah komponen sistem dan tidak dapat dihapus', 422);
        }

        $component->delete();

        return $this->successResponse(null, 'Komponen gaji berhasil dihapus');
    }

    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $component = SalaryComponent::where('company_id', $companyId)->findOrFail($id);

        if ($component->code === 'BASIC') {
            return $this->errorResponse('Komponen Gaji Pokok tidak dapat dinonaktifkan', 422);
        }

        $component->update(['is_active' => ! $component->is_active]);

        return $this->successResponse($component, 'Status komponen gaji berhasil diubah');
    }
}
