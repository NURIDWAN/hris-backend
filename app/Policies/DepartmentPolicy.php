<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view departments');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Department $department): bool
    {
        if (! $this->belongsToSameCompany($user, $department)) {
            return false;
        }

        return $user->can('view departments');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create departments');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Department $department): bool
    {
        if (! $this->belongsToSameCompany($user, $department)) {
            return false;
        }

        return $user->can('edit departments');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Department $department): bool
    {
        if (! $this->belongsToSameCompany($user, $department)) {
            return false;
        }

        return $user->can('delete departments');
    }
}
