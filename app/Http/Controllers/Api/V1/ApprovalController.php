<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\LeaveRequest;
use App\Models\OvertimeRequest;
use App\Models\Reimbursement;
use App\Services\ApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ApprovalController extends Controller
{
    #[OA\Get(
        path: '/approvals/pending',
        summary: 'Get pending approval items',
        description: 'Returns all pending leave requests, overtime requests, and reimbursements for manager approval',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Pending approvals retrieved successfully',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'leave_requests', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'overtime_requests', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'reimbursements', type: 'array', items: new OA\Items(type: 'object')),
                                new OA\Property(property: 'total_pending', type: 'integer', example: 5),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee data not found',
            ], 404);
        }

        // Get subordinate IDs
        $subordinateIds = $employee->subordinates->pluck('id')->toArray();
        $approvalService = app(ApprovalService::class);

        // Pending leave requests: subordinates (legacy) + workflow-based where user is current approver
        $leaveRequests = LeaveRequest::with(['employee', 'leaveType'])
            ->where('company_id', $user->company_id)
            ->where('status', 'pending')
            ->where(function ($q) use ($subordinateIds) {
                // Legacy: subordinate-based
                $q->where(function ($q2) use ($subordinateIds) {
                    $q2->whereIn('employee_id', $subordinateIds)
                        ->whereNull('approval_workflow_id');
                })
                // Workflow-based: user is authorized for current step
                    ->orWhereNotNull('approval_workflow_id');
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->filter(function ($leave) use ($approvalService, $user, $subordinateIds) {
                if ($leave->hasWorkflow()) {
                    return $approvalService->canUserApprove($leave, $user);
                }

                // Legacy: must be subordinate
                return in_array($leave->employee_id, $subordinateIds);
            })
            ->map(fn ($leave) => [
                'id' => $leave->id,
                'employee_id' => $leave->employee_id,
                'employee_name' => $leave->employee->full_name,
                'leave_type' => $leave->leaveType->name,
                'start_date' => $leave->start_date->toDateString(),
                'end_date' => $leave->end_date->toDateString(),
                'days' => $leave->days,
                'reason' => $leave->reason,
                'has_workflow' => $leave->hasWorkflow(),
                'current_step' => $leave->current_approval_step,
                'created_at' => $leave->created_at->toIso8601String(),
            ])
            ->values();

        // Pending overtime requests from subordinates
        $overtimeRequests = OvertimeRequest::with(['employee'])
            ->where('company_id', $user->company_id)
            ->whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($overtime) => [
                'id' => $overtime->id,
                'employee_id' => $overtime->employee_id,
                'employee_name' => $overtime->employee->full_name,
                'date' => $overtime->date->toDateString(),
                'start_time' => $overtime->start_time,
                'end_time' => $overtime->end_time,
                'hours' => $overtime->overtime_hours,
                'reason' => $overtime->reason,
                'created_at' => $overtime->created_at->toIso8601String(),
            ]);

        // Pending reimbursements from subordinates
        $reimbursements = Reimbursement::with(['employee'])
            ->where('company_id', $user->company_id)
            ->whereIn('employee_id', $subordinateIds)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($reimbursement) => [
                'id' => $reimbursement->id,
                'employee_id' => $reimbursement->employee_id,
                'employee_name' => $reimbursement->employee->full_name,
                'category' => $reimbursement->category,
                'amount' => $reimbursement->amount,
                'description' => $reimbursement->description,
                'created_at' => $reimbursement->created_at->toIso8601String(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'leave_requests' => $leaveRequests,
                'overtime_requests' => $overtimeRequests,
                'reimbursements' => $reimbursements,
                'total_pending' => $leaveRequests->count() + $overtimeRequests->count() + $reimbursements->count(),
            ],
        ]);
    }

    #[OA\Post(
        path: '/approvals/leave/{id}/approve',
        summary: 'Approve leave request',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'notes', type: 'string', example: 'Approved'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Leave request approved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Leave request not found'),
        ]
    )]
    public function approveLeave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $leave = LeaveRequest::where('company_id', $user->company_id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (! $leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found',
            ], 404);
        }

        // Workflow-based approval
        if ($leave->hasWorkflow()) {
            $result = app(ApprovalService::class)->processApproval(
                $leave, $user, 'approve', $request->input('notes')
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 403);
        }

        // Legacy: check subordinate
        if (! $this->canApprove($employee, $leave->employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $leave->approve($user->id, $request->input('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Leave request approved successfully',
        ]);
    }

    #[OA\Post(
        path: '/approvals/leave/{id}/reject',
        summary: 'Reject leave request',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['notes'],
                properties: [
                    new OA\Property(property: 'notes', type: 'string', example: 'Insufficient leave balance'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Leave request rejected'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 422, description: 'Validation error'),
        ]
    )]
    public function rejectLeave(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $leave = LeaveRequest::where('company_id', $user->company_id)
            ->where('id', $id)
            ->where('status', 'pending')
            ->first();

        if (! $leave) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found',
            ], 404);
        }

        // Workflow-based rejection
        if ($leave->hasWorkflow()) {
            $result = app(ApprovalService::class)->processApproval(
                $leave, $user, 'reject', $request->input('notes')
            );

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
            ], $result['success'] ? 200 : 403);
        }

        // Legacy: check subordinate
        if (! $this->canApprove($employee, $leave->employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $leave->reject($user->id, $request->input('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Leave request rejected successfully',
        ]);
    }

    #[OA\Post(
        path: '/approvals/overtime/{id}/approve',
        summary: 'Approve overtime request',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Overtime request approved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Overtime request not found'),
        ]
    )]
    public function approveOvertime(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $overtime = OvertimeRequest::where('company_id', $user->company_id)
            ->where('id', $id)
            ->first();

        if (! $overtime || ! $this->canApprove($employee, $overtime->employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Overtime request not found or unauthorized',
            ], 404);
        }

        $overtime->update([
            'status' => 'approved',
            'approved_by' => $employee->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Overtime request approved successfully',
        ]);
    }

    #[OA\Post(
        path: '/approvals/overtime/{id}/reject',
        summary: 'Reject overtime request',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['notes'],
                properties: [
                    new OA\Property(property: 'notes', type: 'string', example: 'Not required'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Overtime request rejected'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function rejectOvertime(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $overtime = OvertimeRequest::where('company_id', $user->company_id)
            ->where('id', $id)
            ->first();

        if (! $overtime || ! $this->canApprove($employee, $overtime->employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Overtime request not found or unauthorized',
            ], 404);
        }

        $overtime->update([
            'status' => 'rejected',
            'approved_by' => $employee->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Overtime request rejected successfully',
        ]);
    }

    #[OA\Post(
        path: '/approvals/reimbursement/{id}/approve',
        summary: 'Approve reimbursement',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reimbursement approved'),
            new OA\Response(response: 401, description: 'Unauthorized'),
            new OA\Response(response: 404, description: 'Reimbursement not found'),
        ]
    )]
    public function approveReimbursement(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        $reimbursement = Reimbursement::where('company_id', $user->company_id)
            ->where('id', $id)
            ->first();

        if (! $reimbursement || ! $this->canApprove($employee, $reimbursement->employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement not found or unauthorized',
            ], 404);
        }

        $reimbursement->update([
            'status' => 'approved',
            'approved_by' => $employee->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement approved successfully',
        ]);
    }

    #[OA\Post(
        path: '/approvals/reimbursement/{id}/reject',
        summary: 'Reject reimbursement',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['notes'],
                properties: [
                    new OA\Property(property: 'notes', type: 'string', example: 'Invalid receipt'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Reimbursement rejected'),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function rejectReimbursement(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $user = $request->user();
        $employee = $user->employee;

        $reimbursement = Reimbursement::where('company_id', $user->company_id)
            ->where('id', $id)
            ->first();

        if (! $reimbursement || ! $this->canApprove($employee, $reimbursement->employee_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Reimbursement not found or unauthorized',
            ], 404);
        }

        $reimbursement->update([
            'status' => 'rejected',
            'approved_by' => $employee->id,
            'approved_at' => now(),
            'approval_notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reimbursement rejected successfully',
        ]);
    }

    #[OA\Get(
        path: '/approvals/history',
        summary: 'Get approval history',
        description: 'Returns history of approved/rejected requests by the manager',
        tags: ['Approvals'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Approval history retrieved',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'type', type: 'string', example: 'leave'),
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'employee_name', type: 'string', example: 'John Doe'),
                                    new OA\Property(property: 'status', type: 'string', example: 'approved'),
                                    new OA\Property(property: 'approved_at', type: 'string', format: 'date-time'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Unauthorized'),
        ]
    )]
    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $employee = $user->employee;

        if (! $employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee data not found',
            ], 404);
        }

        $history = collect();

        // Leave requests approved/rejected by this manager
        $leaveHistory = LeaveRequest::with(['employee'])
            ->where('company_id', $user->company_id)
            ->where('approved_by', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('approved_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($leave) => [
                'type' => 'leave',
                'id' => $leave->id,
                'employee_name' => $leave->employee->full_name,
                'status' => $leave->status,
                'approved_at' => $leave->approved_at?->toIso8601String(),
            ]);

        $history = $history->merge($leaveHistory);

        // Overtime requests approved/rejected by this manager
        $overtimeHistory = OvertimeRequest::with(['employee'])
            ->where('company_id', $user->company_id)
            ->where('approved_by', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('approved_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($overtime) => [
                'type' => 'overtime',
                'id' => $overtime->id,
                'employee_name' => $overtime->employee->full_name,
                'status' => $overtime->status,
                'approved_at' => $overtime->approved_at?->toIso8601String(),
            ]);

        $history = $history->merge($overtimeHistory);

        // Reimbursements approved/rejected by this manager
        $reimbursementHistory = Reimbursement::with(['employee'])
            ->where('company_id', $user->company_id)
            ->where('approved_by', $employee->id)
            ->whereIn('status', ['approved', 'rejected'])
            ->orderBy('approved_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn ($reimbursement) => [
                'type' => 'reimbursement',
                'id' => $reimbursement->id,
                'employee_name' => $reimbursement->employee->full_name,
                'status' => $reimbursement->status,
                'approved_at' => $reimbursement->approved_at?->toIso8601String(),
            ]);

        $history = $history->merge($reimbursementHistory);

        // Sort by approved_at
        $history = $history->sortByDesc('approved_at')->values();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Check if approver can approve request from given employee
     */
    private function canApprove($approver, int $employeeId): bool
    {
        if (! $approver) {
            return false;
        }

        // Check if employee is a subordinate
        return $approver->subordinates->contains('id', $employeeId);
    }
}
