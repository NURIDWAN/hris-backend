<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\ApprovalWorkflow;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Services\ApprovalService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveRequestController extends AdminBaseController
{
    public function index(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $query = LeaveRequest::with([
            'employee:id,first_name,last_name,employee_id,department_id,position_id',
            'employee.department:id,name',
            'leaveType:id,name,code,color',
            'approvedBy:id,name',
            'rejectedBy:id,name',
        ])->where('company_id', $companyId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($employeeId = $request->input('employee_id')) {
            $query->where('employee_id', $employeeId);
        }

        if ($leaveTypeId = $request->input('leave_type_id')) {
            $query->where('leave_type_id', $leaveTypeId);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('start_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('end_date', '<=', $dateTo);
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $requests = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->paginatedResponse($requests, 'Daftar pengajuan cuti berhasil diambil');
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
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'is_half_day' => ['nullable', 'boolean'],
            'half_day_type' => ['nullable', 'in:morning,afternoon'],
            'reason' => ['required', 'string', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:100'],
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $isHalfDay = $request->boolean('is_half_day');
        $totalDays = $isHalfDay ? 0.5 : (float) ($startDate->diffInDays($endDate) + 1);

        $leaveRequest = $this->createLeaveRequestRecord(
            $companyId,
            $validated,
            $startDate,
            $endDate,
            $isHalfDay,
            $totalDays
        );

        return $this->successResponse(
            $leaveRequest->load(['employee:id,first_name,last_name,employee_id', 'leaveType:id,name,code']),
            'Pengajuan cuti berhasil dibuat',
            201
        );
    }

    public function show(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);

        $leaveRequest = LeaveRequest::with([
            'employee',
            'leaveType',
            'approvedBy:id,name,email',
            'rejectedBy:id,name,email',
            'cancelledBy:id,name,email',
        ])
            ->where('company_id', $companyId)
            ->findOrFail($id);

        return $this->successResponse($leaveRequest, 'Detail pengajuan cuti berhasil diambil');
    }

    public function approve(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $leaveRequest = LeaveRequest::where('company_id', $companyId)->findOrFail($id);

        if (! $leaveRequest->canBeApproved()) {
            return $this->errorResponse('Pengajuan cuti ini tidak dapat disetujui (status saat ini: '.$leaveRequest->status.')', 422);
        }

        return $this->processWorkflowOrDirectAction(
            $leaveRequest,
            $request->user(),
            'approve',
            $request->input('notes'),
            'Pengajuan cuti berhasil disetujui',
            'Gagal memproses approval cuti'
        );
    }

    public function reject(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $leaveRequest = LeaveRequest::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        if (! $leaveRequest->canBeRejected()) {
            return $this->errorResponse('Pengajuan cuti ini tidak dapat ditolak (status saat ini: '.$leaveRequest->status.')', 422);
        }

        return $this->processWorkflowOrDirectAction(
            $leaveRequest,
            $request->user(),
            'reject',
            $validated['reason'],
            'Pengajuan cuti berhasil ditolak',
            'Gagal memproses penolakan cuti'
        );
    }

    public function cancel(Request $request, int|string $id): JsonResponse
    {
        $companyId = $this->getCompanyId($request);
        $leaveRequest = LeaveRequest::where('company_id', $companyId)->findOrFail($id);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $cannotCancel = (! method_exists($leaveRequest, 'canBeCancelled') || ! $leaveRequest->canBeCancelled())
            && ! in_array($leaveRequest->status, ['pending', 'approved'], true);

        if ($cannotCancel) {
            return $this->errorResponse('Pengajuan cuti tidak dapat dibatalkan', 422);
        }

        $user = $request->user();
        $leaveRequest->cancel($user->id, $validated['reason'] ?? null);

        return $this->successResponse($leaveRequest->fresh(), 'Pengajuan cuti berhasil dibatalkan');
    }

    /**
     * Persist leave request record in transaction and update related balance/workflow
     *
     * @param  array<string, mixed>  $validated
     */
    private function createLeaveRequestRecord(
        int|string $companyId,
        array $validated,
        Carbon $startDate,
        Carbon $endDate,
        bool $isHalfDay,
        float $totalDays
    ): LeaveRequest {
        return DB::transaction(function () use ($companyId, $validated, $startDate, $endDate, $isHalfDay, $totalDays) {
            $leaveReq = LeaveRequest::create([
                'company_id' => $companyId,
                'employee_id' => $validated['employee_id'],
                'leave_type_id' => $validated['leave_type_id'],
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'total_days' => $totalDays,
                'is_half_day' => $isHalfDay,
                'half_day_type' => $isHalfDay ? ($validated['half_day_type'] ?? null) : null,
                'reason' => $validated['reason'],
                'emergency_contact' => $validated['emergency_contact'] ?? null,
                'status' => 'pending',
            ]);

            $this->adjustPendingBalance($validated['employee_id'], $validated['leave_type_id'], $startDate->year, $totalDays);
            $this->initializeApprovalWorkflow($leaveReq, $companyId);

            return $leaveReq;
        });
    }

    /**
     * Adjust pending days in employee's leave balance if balance record exists
     */
    private function adjustPendingBalance(int|string $employeeId, int|string $leaveTypeId, int $year, float $totalDays): void
    {
        $balance = LeaveBalance::where('employee_id', $employeeId)
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', $year)
            ->first();

        if ($balance && method_exists($balance, 'addPendingDays')) {
            $balance->addPendingDays($totalDays);
        }
    }

    /**
     * Initialize approval workflow if approval service is available
     */
    private function initializeApprovalWorkflow(LeaveRequest $leaveReq, int|string $companyId): void
    {
        if (class_exists(ApprovalService::class)) {
            $workflowType = defined(ApprovalWorkflow::class.'::TYPE_LEAVE_REQUEST')
                ? ApprovalWorkflow::TYPE_LEAVE_REQUEST
                : 'leave_request';

            app(ApprovalService::class)->initializeWorkflow($leaveReq, $workflowType, $companyId);
        }
    }

    /**
     * Process workflow approval/rejection or fallback to direct model transition
     */
    private function processWorkflowOrDirectAction(
        LeaveRequest $leaveRequest,
        mixed $user,
        string $action,
        ?string $notes,
        string $defaultSuccessMessage,
        string $defaultErrorMessage
    ): JsonResponse {
        $hasWorkflow = method_exists($leaveRequest, 'hasWorkflow')
            && $leaveRequest->hasWorkflow()
            && class_exists(ApprovalService::class);

        if ($hasWorkflow) {
            $result = app(ApprovalService::class)->processApproval($leaveRequest, $user, $action, $notes);

            if (! empty($result['success'])) {
                return $this->successResponse($leaveRequest->fresh(), $result['message'] ?? $defaultSuccessMessage);
            }

            return $this->errorResponse($result['message'] ?? $defaultErrorMessage, 422);
        }

        if ($action === 'approve') {
            $leaveRequest->approve($user->id, $notes);
        } else {
            $leaveRequest->reject($user->id, $notes);
        }

        return $this->successResponse($leaveRequest->fresh(), $defaultSuccessMessage);
    }
}
