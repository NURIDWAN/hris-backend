<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = LeaveType::where('company_id', $companyId);

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
        $leaveTypes = $query->orderBy('name', 'asc')->paginate($perPage);

        return $this->paginatedResponse($leaveTypes, 'Daftar jenis cuti berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'code')->where('company_id', $companyId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_days' => ['required', 'integer', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'requires_attachment' => ['nullable', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'min_notice_days' => ['nullable', 'integer', 'min:0'],
            'is_carry_forward' => ['nullable', 'boolean'],
            'max_carry_forward_days' => ['nullable', 'integer', 'min:0'],
            'is_annual' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $leaveType = LeaveType::create([
            ...$validated,
            'company_id' => $companyId,
            'is_paid' => $request->boolean('is_paid', true),
            'requires_approval' => $request->boolean('requires_approval', true),
            'requires_attachment' => $request->boolean('requires_attachment'),
            'is_carry_forward' => $request->boolean('is_carry_forward'),
            'is_annual' => $request->boolean('is_annual'),
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return $this->successResponse($leaveType, 'Jenis cuti berhasil ditambahkan', 201);
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $leaveType = LeaveType::where('company_id', $companyId)->findOrFail($id);

        return $this->successResponse($leaveType, 'Detail jenis cuti berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $leaveType = LeaveType::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('leave_types', 'code')->where('company_id', $companyId)->ignore($leaveType->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'default_days' => ['sometimes', 'required', 'integer', 'min:0'],
            'is_paid' => ['nullable', 'boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'requires_attachment' => ['nullable', 'boolean'],
            'max_consecutive_days' => ['nullable', 'integer', 'min:1'],
            'min_notice_days' => ['nullable', 'integer', 'min:0'],
            'is_carry_forward' => ['nullable', 'boolean'],
            'max_carry_forward_days' => ['nullable', 'integer', 'min:0'],
            'is_annual' => ['nullable', 'boolean'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $booleanFields = ['is_paid', 'requires_approval', 'requires_attachment', 'is_carry_forward', 'is_annual', 'is_active'];
        foreach ($booleanFields as $field) {
            if ($request->has($field)) {
                $validated[$field] = $request->boolean($field);
            }
        }

        $leaveType->update($validated);

        return $this->successResponse($leaveType->fresh(), 'Jenis cuti berhasil diperbarui');
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $leaveType = LeaveType::where('company_id', $companyId)->findOrFail($id);

        if ($leaveType->leaveRequests()->whereIn('status', ['pending', 'approved'])->count() > 0) {
            return $this->errorResponse('Jenis cuti tidak dapat dihapus karena memiliki riwayat permohonan aktif', 422);
        }

        $leaveType->delete();

        return $this->successResponse(null, 'Jenis cuti berhasil dihapus');
    }

    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $leaveType = LeaveType::where('company_id', $companyId)->findOrFail($id);
        $leaveType->update(['is_active' => ! $leaveType->is_active]);

        return $this->successResponse($leaveType, 'Status jenis cuti berhasil diubah');
    }
}
