<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PositionController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Position::with(['department:id,name,code'])
            ->withCount('employees')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($departmentId = $request->input('department_id')) {
            $query->where('department_id', $departmentId);
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $positions = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($positions, 'Daftar jabatan berhasil diambil');
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
                Rule::unique('positions', 'code')->where('company_id', $companyId),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['nullable', 'integer', 'min:1'],
            'base_salary' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $position = Position::create([
            ...$validated,
            'company_id' => $companyId,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return $this->successResponse($position->load(['department:id,name,code']), 'Jabatan berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $position = Position::with(['department:id,name,code', 'employees:id,position_id,department_id,first_name,last_name,employee_id'])
            ->withCount('employees')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($position, 'Detail jabatan berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $position = Position::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('positions', 'code')->where('company_id', $companyId)->ignore($position->id),
            ],
            'department_id' => [
                'nullable',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['nullable', 'integer', 'min:1'],
            'base_salary' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $position->update($validated);

        return $this->successResponse($position->fresh(['department:id,name,code']), 'Jabatan berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $position = Position::where('company_id', $companyId)->findOrFail($id);

        if ($position->employees()->count() > 0) {
            return $this->errorResponse('Jabatan tidak dapat dihapus karena masih digunakan oleh karyawan', 422);
        }

        $position->delete();

        return $this->successResponse(null, 'Jabatan berhasil dihapus');
    }
}
