<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view employees');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Employee $employee): bool
    {
        if (! $this->belongsToSameCompany($user, $employee)) {
            return false;
        }

        // User can view if they have permission OR it's their own profile
        return $user->can('view employees') || $this->isOwnProfile($user, $employee);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create employees');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Employee $employee): bool
    {
        if (! $this->belongsToSameCompany($user, $employee)) {
            return false;
        }

        return $user->can('edit employees');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Employee $employee): bool
    {
        if (! $this->belongsToSameCompany($user, $employee)) {
            return false;
        }

        return $user->can('delete employees');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Employee $employee): bool
    {
        if (! $this->belongsToSameCompany($user, $employee)) {
            return false;
        }

        return $user->can('delete employees');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        if (! $this->belongsToSameCompany($user, $employee)) {
            return false;
        }

        return $user->can('delete employees');
    }

    /**
     * Check if the employee profile belongs to the user.
     */
    protected function isOwnProfile(User $user, Employee $employee): bool
    {
        return $employee->user_id === $user->id;
    }
}
