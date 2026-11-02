<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Department;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Department::with(['parent:id,name,code'])
            ->withCount('employees')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $departments = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($departments, 'Daftar departemen berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where('company_id', $companyId),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $department = Department::create([
            ...$validated,
            'company_id' => $companyId,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return $this->successResponse($department->load(['parent:id,name,code']), 'Departemen berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $department = Department::with(['parent:id,name,code', 'positions:id,department_id,name,code', 'employees:id,department_id,first_name,last_name,employee_id'])
            ->withCount('employees')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($department, 'Detail departemen berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $department = Department::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->where('company_id', $companyId)->ignore($department->id),
            ],
            'parent_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $companyId)->whereNot('id', $department->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $department->update($validated);

        return $this->successResponse($department->fresh(['parent:id,name,code']), 'Departemen berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $department = Department::where('company_id', $companyId)->findOrFail($id);

        if ($department->employees()->count() > 0) {
            return $this->errorResponse('Departemen tidak dapat dihapus karena masih memiliki karyawan terkait', 422);
        }

        if ($department->children()->count() > 0) {
            return $this->errorResponse('Departemen tidak dapat dihapus karena masih memiliki sub-departemen turunan', 422);
        }

        $department->delete();

        return $this->successResponse(null, 'Departemen berhasil dihapus');
    }
}
