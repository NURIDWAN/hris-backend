<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Reimbursement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReimbursementController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = Reimbursement::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'category:id,name',
            'approver:id,name',
        ])->where('company_id', $companyId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('expense_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->where('expense_date', '<=', $endDate);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $reimbursements = $query->orderBy('expense_date', 'desc')->paginate($perPage);

        return $this->paginatedResponse($reimbursements, 'Daftar reimbursement berhasil diambil');
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $validated = $request->validate([
            'employee_id' => [
                'required',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'category_id' => [
                'required',
                Rule::exists('reimbursement_categories', 'id')->where('company_id', $companyId),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['required', 'string', 'max:1000'],
            'expense_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('reimbursements', 'public');
        }

        $reimbursement = Reimbursement::create([
            ...$validated,
            'company_id' => $companyId,
            'receipt_path' => $receiptPath,
            'status' => Reimbursement::STATUS_PENDING,
        ]);

        return $this->successResponse(
            $reimbursement->load(['employee:id,first_name,last_name,employee_id', 'category:id,name']),
            'Pengajuan reimbursement berhasil dibuat',
            201
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $reimbursement = Reimbursement::with([
            'employee',
            'category',
            'approver:id,name,email',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($reimbursement, 'Detail reimbursement berhasil diambil');
    }

    public function approve(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $reimbursement = Reimbursement::where('company_id', $companyId)->findOrFail($id);

        if ($reimbursement->status !== Reimbursement::STATUS_PENDING) {
            return $this->errorResponse('Hanya reimbursement berstatus pending yang dapat disetujui', 422);
        }

        $user = $request->user();
        $reimbursement->update([
            'status' => Reimbursement::STATUS_APPROVED,
            'approved_by' => $user->id,
            'approved_at' => now(),
            'notes' => $request->input('notes') ?? $reimbursement->notes,
        ]);

        return $this->successResponse($reimbursement->fresh(), 'Reimbursement berhasil disetujui');
    }

    public function reject(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $reimbursement = Reimbursement::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if ($reimbursement->status !== Reimbursement::STATUS_PENDING) {
            return $this->errorResponse('Hanya reimbursement berstatus pending yang dapat ditolak', 422);
        }

        $reimbursement->update([
            'status' => Reimbursement::STATUS_REJECTED,
            'rejection_reason' => $validated['reason'],
        ]);

        return $this->successResponse($reimbursement->fresh(), 'Reimbursement berhasil ditolak');
    }

    public function pay(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $reimbursement = Reimbursement::where('company_id', $companyId)->findOrFail($id);

        if ($reimbursement->status !== Reimbursement::STATUS_APPROVED) {
            return $this->errorResponse('Hanya reimbursement berstatus approved yang dapat dibayarkan', 422);
        }

        $validated = $request->validate([
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $reimbursement->update([
            'status' => Reimbursement::STATUS_PAID,
            'paid_at' => now(),
            'payment_method' => $validated['payment_method'] ?? 'transfer',
        ]);

        return $this->successResponse($reimbursement->fresh(), 'Reimbursement berhasil ditandai telah dibayar');
    }
}
