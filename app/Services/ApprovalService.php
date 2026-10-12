<?php

namespace App\Services;

use App\Models\ApprovalRecord;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ApprovalService
{
    public function __construct(
        protected PushNotificationService $pushNotificationService
    ) {}

    /**
     * Initialize approval workflow after a request is created.
     * Returns true if workflow was initialized, false if no workflow found (legacy mode).
     */
    public function initializeWorkflow(Model $approvable, string $type, int $companyId): bool
    {
        $workflow = ApprovalWorkflow::where('company_id', $companyId)
            ->where('type', $type)
            ->where('is_active', true)
            ->first();

        if (! $workflow) {
            return false;
        }

        $steps = $workflow->activeSteps;

        if ($steps->isEmpty()) {
            return false;
        }

        DB::transaction(function () use ($approvable, $workflow, $steps) {
            foreach ($steps as $step) {
                ApprovalRecord::create([
                    'approvable_type' => get_class($approvable),
                    'approvable_id' => $approvable->id,
                    'approval_workflow_id' => $workflow->id,
                    'approval_workflow_step_id' => $step->id,
                    'step_order' => $step->step_order,
                    'status' => 'pending',
                ]);
            }

            $approvable->update([
                'approval_workflow_id' => $workflow->id,
                'current_approval_step' => $steps->first()->step_order,
            ]);
        });

        // Auto-skip first step if needed
        $this->autoSkipIfNeeded($approvable);

        // Notify approvers for the current step
        $this->notifyApprovers($approvable);

        return true;
    }

    /**
     * Process an approval or rejection for the current step.
     */
    public function processApproval(Model $approvable, User $user, string $action, ?string $notes = null): array
    {
        $pendingRecords = ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->get();

        $record = null;
        $recordsToSkip = collect();

        foreach ($pendingRecords as $pendingRecord) {
            if ($this->isAuthorizedApprover($pendingRecord->step, $user, $approvable)) {
                $record = $pendingRecord;
                break;
            }

            // If user is not authorized for this step, can we skip it?
            if ($pendingRecord->step->can_skip) {
                $recordsToSkip->push($pendingRecord);
            } else {
                // Found a mandatory step the user cannot approve, so we can't 'jump' further
                break;
            }
        }

        if (! $record) {
            $currentRecord = $pendingRecords->first();
            if ($currentRecord && ! $this->isAuthorizedApprover($currentRecord->step, $user, $approvable)) {
                return ['success' => false, 'message' => 'Anda tidak berwenang untuk menyetujui langkah ini.'];
            }

            return ['success' => false, 'message' => 'Tidak ada langkah persetujuan yang pending atau Anda tidak berwenang.'];
        }

        return DB::transaction(function () use ($approvable, $user, $action, $notes, $record, $recordsToSkip) {
            // Skip previous steps if we jumped
            foreach ($recordsToSkip as $skipRecord) {
                $skipRecord->update([
                    'status' => 'skipped',
                    'notes' => 'Dilompati oleh persetujuan langkah berikutnya',
                    'acted_at' => now(),
                ]);
            }

            if ($action === 'reject') {
                return $this->handleRejection($approvable, $user, $notes, $record);
            }

            return $this->handleApproval($approvable, $user, $notes, $record);
        });
    }

    /**
     * Check if a user can approve the current step.
     */
    public function canUserApprove(Model $approvable, User $user): bool
    {
        $pendingRecords = ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->get();

        foreach ($pendingRecords as $record) {
            if ($this->isAuthorizedApprover($record->step, $user, $approvable)) {
                return true;
            }

            // If user is not authorized for this step, can we skip it?
            if (! $record->step->can_skip) {
                // Found a mandatory step the user cannot approve, so we can't 'jump' further
                return false;
            }
        }

        return false;
    }

    /**
     * Get the current pending approval record.
     */
    public function getCurrentPendingRecord(Model $approvable): ?ApprovalRecord
    {
        return ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();
    }

    /**
     * Get all approval records for display/timeline.
     */
    public function getApprovalRecords(Model $approvable)
    {
        return ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->with(['step', 'actor'])
            ->orderBy('step_order')
            ->get();
    }

    /**
     * Check if user is authorized approver for the given step.
     */
    public function isAuthorizedApprover(ApprovalWorkflowStep $step, User $user, Model $approvable): bool
    {
        return match ($step->approver_type) {
            ApprovalWorkflowStep::APPROVER_TYPE_ROLE => $this->checkRoleApprover($step, $user),
            ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER => $step->approver_user_id === $user->id,
            ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD => $this->checkDepartmentHead($user, $approvable),
            ApprovalWorkflowStep::APPROVER_TYPE_DIRECT_MANAGER => $this->checkDepartmentHead($user, $approvable),
            default => false,
        };
    }

    /**
     * Auto-skip steps where can_skip=true and no approver is available.
     */
    public function autoSkipIfNeeded(Model $approvable): void
    {
        $record = $this->getCurrentPendingRecord($approvable);

        if (! $record) {
            return;
        }

        $step = $record->step;

        if (! $step->can_skip) {
            return;
        }

        // Check if there's any possible approver for this step
        $hasApprover = $this->hasAvailableApprover($step, $approvable);

        if (! $hasApprover) {
            $record->update([
                'status' => 'skipped',
                'notes' => 'Auto-skipped: tidak ada approver tersedia',
                'acted_at' => now(),
            ]);

            // Advance to next step
            $this->advanceToNextStep($approvable);

            // Recursively check next step
            $this->autoSkipIfNeeded($approvable);
        }
    }

    /**
     * Finalize the approval — call the model's native approve() method.
     */
    public function finalizeApproval(Model $approvable, User $user, ?string $notes = null): void
    {
        $approvable->approve($user->id, $notes);
    }

    /**
     * Skip all remaining pending records (e.g. when request is cancelled).
     */
    public function skipAllPending(Model $approvable): void
    {
        ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'skipped',
                'notes' => 'Request dibatalkan/ditolak',
                'acted_at' => now(),
            ]);
    }

    private function handleApproval(Model $approvable, User $user, ?string $notes, ApprovalRecord $record): array
    {
        $record->update([
            'status' => 'approved',
            'acted_by' => $user->id,
            'notes' => $notes,
            'acted_at' => now(),
        ]);

        // Check if there's a next step
        $nextRecord = $this->getNextPendingRecord($approvable, $record->step_order);

        if ($nextRecord) {
            // Advance to next step
            $approvable->update([
                'current_approval_step' => $nextRecord->step_order,
            ]);

            // Auto-skip next step if needed
            $this->autoSkipIfNeeded($approvable);

            // Notify approvers for the new current step
            $this->notifyApprovers($approvable);

            // After auto-skip, check if all done
            $stillPending = $this->getCurrentPendingRecord($approvable);
            if (! $stillPending) {
                $this->finalizeApproval($approvable, $user, $notes);

                return ['success' => true, 'message' => 'Semua langkah persetujuan selesai. Pengajuan disetujui.', 'finalized' => true];
            }

            return ['success' => true, 'message' => 'Langkah persetujuan berhasil. Menunggu langkah berikutnya.', 'finalized' => false];
        }

        // No more steps — finalize
        $this->finalizeApproval($approvable, $user, $notes);
        $approvable->update(['current_approval_step' => null]);

        return ['success' => true, 'message' => 'Semua langkah persetujuan selesai. Pengajuan disetujui.', 'finalized' => true];
    }

    private function handleRejection(Model $approvable, User $user, ?string $notes, ApprovalRecord $record): array
    {
        $record->update([
            'status' => 'rejected',
            'acted_by' => $user->id,
            'notes' => $notes,
            'acted_at' => now(),
        ]);

        // Skip all remaining steps
        $this->skipAllPending($approvable);

        // Reject the request
        $approvable->reject($user->id, $notes);

        return ['success' => true, 'message' => 'Pengajuan ditolak.', 'finalized' => true];
    }

    private function advanceToNextStep(Model $approvable): void
    {
        $nextRecord = ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->orderBy('step_order')
            ->first();

        if ($nextRecord) {
            $approvable->update(['current_approval_step' => $nextRecord->step_order]);
        } else {
            // All steps processed — auto-approve if all were skipped/approved
            $hasRejection = ApprovalRecord::where('approvable_type', get_class($approvable))
                ->where('approvable_id', $approvable->id)
                ->where('status', 'rejected')
                ->exists();

            if (! $hasRejection) {
                // All skipped/approved — finalize
                $approvable->approve($approvable->employee->user_id ?? $approvable->approved_by, 'Auto-approved: semua langkah di-skip');
            }

            $approvable->update(['current_approval_step' => null]);
        }
    }

    private function getNextPendingRecord(Model $approvable, int $afterStepOrder): ?ApprovalRecord
    {
        return ApprovalRecord::where('approvable_type', get_class($approvable))
            ->where('approvable_id', $approvable->id)
            ->where('status', 'pending')
            ->where('step_order', '>', $afterStepOrder)
            ->orderBy('step_order')
            ->first();
    }

    private function checkRoleApprover(ApprovalWorkflowStep $step, User $user): bool
    {
        if (! $step->approver_role) {
            return false;
        }

        return $user->hasRole($step->approver_role);
    }

    private function checkDepartmentHead(User $user, Model $approvable): bool
    {
        // The approvable's employee must have this user's employee as their manager
        $employee = $approvable->employee ?? null;

        if (! $employee) {
            return false;
        }

        $approverEmployee = $user->employee;

        if (! $approverEmployee) {
            return false;
        }

        return $employee->manager_id === $approverEmployee->id;
    }

    private function hasAvailableApprover(ApprovalWorkflowStep $step, Model $approvable): bool
    {
        return match ($step->approver_type) {
            ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER => $step->approver_user_id !== null && User::where('id', $step->approver_user_id)->exists(),
            ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD => $approvable->employee?->manager_id !== null,
            ApprovalWorkflowStep::APPROVER_TYPE_DIRECT_MANAGER => $approvable->employee?->manager_id !== null,
            ApprovalWorkflowStep::APPROVER_TYPE_ROLE => User::role($step->approver_role)->where('company_id', $approvable->company_id)->exists(),
            default => false,
        };
    }

    /**
     * Notify approvers for the current step.
     */
    public function notifyApprovers(Model $approvable): void
    {
        $record = $this->getCurrentPendingRecord($approvable);

        if (! $record) {
            return;
        }

        $step = $record->step;
        $approvers = collect();

        if ($step->approver_type === ApprovalWorkflowStep::APPROVER_TYPE_ROLE) {
            $approvers = User::role($step->approver_role)
                ->where('company_id', $approvable->company_id)
                ->where('is_active', true)
                ->get();
        } elseif ($step->approver_type === ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER) {
            $user = User::find($step->approver_user_id);
            if ($user && $user->is_active) {
                $approvers->push($user);
            }
        } elseif ($step->approver_type === ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD || $step->approver_type === ApprovalWorkflowStep::APPROVER_TYPE_DIRECT_MANAGER) {
            $manager = $approvable->employee?->manager?->user;
            if ($manager && $manager->is_active) {
                $approvers->push($manager);
            }
        }

        foreach ($approvers as $approver) {
            if ($approvable instanceof LeaveRequest) {
                $this->pushNotificationService->notifyLeaveRequested($approvable, $approver);
            }
        }
    }
}
