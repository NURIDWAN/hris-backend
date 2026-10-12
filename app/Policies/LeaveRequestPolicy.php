<?php

namespace App\Policies;

use App\Models\LeaveRequest;
use App\Models\User;

class LeaveRequestPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view leave requests');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToSameCompany($user, $leaveRequest)) {
            return false;
        }

        // User can view if they have permission OR it's their own request
        return $user->can('view leave requests') || $this->isOwnRequest($user, $leaveRequest);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create leave requests');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToSameCompany($user, $leaveRequest)) {
            return false;
        }

        // Own pending request can be updated
        if ($this->isOwnRequest($user, $leaveRequest) && $leaveRequest->status === 'pending') {
            return true;
        }

        return $user->can('edit leave requests');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToSameCompany($user, $leaveRequest)) {
            return false;
        }

        // Own pending request can be deleted (cancelled)
        if ($this->isOwnRequest($user, $leaveRequest) && $leaveRequest->status === 'pending') {
            return true;
        }

        return $user->can('delete leave requests');
    }

    /**
     * Determine whether the user can approve the leave request.
     */
    public function approve(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToSameCompany($user, $leaveRequest)) {
            return false;
        }

        return $user->can('approve leave requests');
    }

    /**
     * Determine whether the user can reject the leave request.
     */
    public function reject(User $user, LeaveRequest $leaveRequest): bool
    {
        if (! $this->belongsToSameCompany($user, $leaveRequest)) {
            return false;
        }

        return $user->can('approve leave requests');
    }

    /**
     * Check if leave request belongs to user's employee record.
     */
    protected function isOwnRequest(User $user, LeaveRequest $leaveRequest): bool
    {
        return $user->employee && $leaveRequest->employee_id === $user->employee->id;
    }
}
