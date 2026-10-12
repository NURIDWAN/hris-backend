<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view payroll');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payroll $payroll): bool
    {
        if (! $this->belongsToSameCompany($user, $payroll)) {
            return false;
        }

        return $user->can('view payroll');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage payroll');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Payroll $payroll): bool
    {
        if (! $this->belongsToSameCompany($user, $payroll)) {
            return false;
        }

        // Cannot edit paid/processed payroll
        if (in_array($payroll->status, ['paid', 'processed'])) {
            return false;
        }

        return $user->can('manage payroll');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        if (! $this->belongsToSameCompany($user, $payroll)) {
            return false;
        }

        // Cannot delete paid/processed payroll
        if (in_array($payroll->status, ['paid', 'processed'])) {
            return false;
        }

        return $user->can('manage payroll');
    }

    /**
     * Determine whether the user can process payroll.
     */
    public function process(User $user, Payroll $payroll): bool
    {
        if (! $this->belongsToSameCompany($user, $payroll)) {
            return false;
        }

        return $user->can('process payroll');
    }

    /**
     * Determine whether the user can approve payroll.
     */
    public function approve(User $user, Payroll $payroll): bool
    {
        if (! $this->belongsToSameCompany($user, $payroll)) {
            return false;
        }

        return $user->can('approve payroll');
    }
}
