<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Employee;
use App\Models\OfficeLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfficeLocationController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = OfficeLocation::withCount('employees')
            ->where('company_id', $companyId);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->has('is_active') && $request->input('is_active') !== '') {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $locations = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($locations, 'Daftar lokasi kantor berhasil diambil');
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
                Rule::unique('office_locations', 'code')->where('company_id', $companyId),
            ],
            'address' => ['required', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['required', 'integer', 'min:10', 'max:50000'],
            'is_headquarters' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_headquarters'])) {
            OfficeLocation::where('company_id', $companyId)
                ->where('is_headquarters', true)
                ->update(['is_headquarters' => false]);
        }

        if (empty($validated['code'])) {
            $validated['code'] = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $validated['name']), 0, 4)).'-'.rand(100, 999);
        }

        $location = OfficeLocation::create([
            ...$validated,
            'company_id' => $companyId,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
            'is_headquarters' => $request->boolean('is_headquarters'),
        ]);

        return $this->successResponse($location, 'Lokasi kantor berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $location = OfficeLocation::with(['employees:id,first_name,last_name,employee_id,department_id,position_id'])
            ->withCount('employees')
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($location, 'Detail lokasi kantor berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $location = OfficeLocation::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('office_locations', 'code')->where('company_id', $companyId)->ignore($location->id),
            ],
            'address' => ['sometimes', 'required', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'latitude' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'radius' => ['sometimes', 'required', 'integer', 'min:10', 'max:50000'],
            'is_headquarters' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if (! empty($validated['is_headquarters'])) {
            OfficeLocation::where('company_id', $companyId)
                ->where('id', '!=', $location->id)
                ->where('is_headquarters', true)
                ->update(['is_headquarters' => false]);
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $location->update($validated);

        return $this->successResponse($location->fresh(), 'Lokasi kantor berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $location = OfficeLocation::where('company_id', $companyId)->findOrFail($id);

        $location->employees()->detach();
        $location->delete();

        return $this->successResponse(null, 'Lokasi kantor berhasil dihapus');
    }

    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $location = OfficeLocation::where('company_id', $companyId)->findOrFail($id);
        $location->update(['is_active' => ! $location->is_active]);

        return $this->successResponse($location, 'Status lokasi kantor berhasil diubah');
    }

    public function assignEmployees(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $location = OfficeLocation::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'employee_ids' => ['required', 'array'],
            'employee_ids.*' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'primary_employee_id' => ['nullable', 'integer'],
        ]);

        $syncData = [];
        $primaryId = $validated['primary_employee_id'] ?? null;
        foreach ($validated['employee_ids'] as $empId) {
            $syncData[$empId] = [
                'is_primary' => ((int) $empId === (int) $primaryId),
            ];
        }

        $location->employees()->syncWithoutDetaching($syncData);

        return $this->successResponse(
            $location->load('employees:id,first_name,last_name,employee_id'),
            'Karyawan berhasil di-assign ke lokasi kantor'
        );
    }

    public function removeEmployee(Request $request, int|string $id, int|string $employeeId): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $location = OfficeLocation::where('company_id', $companyId)->findOrFail($id);
        $employee = Employee::where('company_id', $companyId)->findOrFail($employeeId);

        $location->employees()->detach($employee->id);

        return $this->successResponse(null, 'Karyawan berhasil dilepas dari lokasi kantor');
    }
}
