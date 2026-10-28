<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveBalanceController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = LeaveBalance::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'leaveType:id,name,code',
        ])->where('company_id', $companyId);

        if ($year = $request->input('year')) {
            $query->where('year', (int) $year);
        } else {
            $query->where('year', now()->year);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($leaveTypeId = $request->input('leave_type_id')) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($departmentId = $request->input('department_id')) {
            $query->whereHas('employee', function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId);
            });
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $balances = $query->paginate($perPage);

        return $this->paginatedResponse($balances, 'Daftar saldo cuti berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

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

        $exists = LeaveBalance::where('company_id', $companyId)
            ->where('employee_id', $validated['employee_id'])
            ->where('leave_type_id', $validated['leave_type_id'])
            ->where('year', $validated['year'])
            ->first();

        if ($exists) {
            return $this->errorResponse('Saldo cuti untuk karyawan, jenis cuti, dan tahun ini sudah ada', 422);
        }

        $balance = LeaveBalance::create([
            'company_id' => $companyId,
            'employee_id' => $validated['employee_id'],
            'leave_type_id' => $validated['leave_type_id'],
            'year' => $validated['year'],
            'entitled_days' => $validated['entitled_days'],
            'used_days' => 0,
            'pending_days' => 0,
            'carried_forward_days' => $validated['carried_forward_days'] ?? 0,
            'adjustment_days' => $validated['adjustment_days'] ?? 0,
            'adjustment_notes' => $validated['adjustment_notes'] ?? null,
        ]);

        return $this->successResponse(
            $balance->load(['employee:id,first_name,last_name,employee_id', 'leaveType:id,name,code']),
            'Saldo cuti berhasil ditambahkan',
            201
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $balance = LeaveBalance::with(['employee', 'leaveType'])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($balance, 'Detail saldo cuti berhasil diambil');
    }

    public function update(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $balance = LeaveBalance::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'entitled_days' => ['sometimes', 'required', 'numeric', 'min:0', 'max:365'],
            'carried_forward_days' => ['nullable', 'numeric', 'min:0', 'max:365'],
            'adjustment_days' => ['nullable', 'numeric', 'min:-365', 'max:365'],
            'adjustment_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $balance->update([
            'entitled_days' => $validated['entitled_days'] ?? $balance->entitled_days,
            'carried_forward_days' => $validated['carried_forward_days'] ?? $balance->carried_forward_days,
            'adjustment_days' => $validated['adjustment_days'] ?? $balance->adjustment_days,
            'adjustment_notes' => array_key_exists('adjustment_notes', $validated) ? $validated['adjustment_notes'] : $balance->adjustment_notes,
        ]);

        return $this->successResponse(
            $balance->fresh(['employee', 'leaveType']),
            'Saldo cuti berhasil diperbarui'
        );
    }

    public function destroy(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $balance = LeaveBalance::where('company_id', $companyId)->findOrFail($id);

        if ($balance->used_days > 0 || $balance->pending_days > 0) {
            return $this->errorResponse('Tidak dapat menghapus saldo cuti yang sudah digunakan atau memiliki pengajuan pending', 422);
        }

        $balance->delete();

        return $this->successResponse(null, 'Saldo cuti berhasil dihapus');
    }

    public function generateBulk(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'leave_type_id' => [
                'required',
                Rule::exists('leave_types', 'id')->where('company_id', $companyId),
            ],
            'year' => ['required', 'integer', 'min:2020', 'max:2099'],
        ]);

        $leaveType = LeaveType::where('company_id', $companyId)->findOrFail($validated['leave_type_id']);

        $employeeIds = Employee::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereNotIn('id', function ($query) use ($validated, $companyId) {
                $query->select('employee_id')
                    ->from('leave_balances')
                    ->where('company_id', $companyId)
                    ->where('leave_type_id', $validated['leave_type_id'])
                    ->where('year', $validated['year'])
                    ->whereNull('deleted_at');
            })
            ->pluck('id');

        if ($employeeIds->isEmpty()) {
            return $this->successResponse(['created' => 0], 'Semua karyawan aktif sudah memiliki saldo cuti untuk jenis dan tahun ini');
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

        return $this->successResponse([
            'created' => $created,
            'year' => $validated['year'],
            'leave_type' => $leaveType->name,
        ], "Berhasil membuat {$created} saldo cuti karyawan");
    }
}
