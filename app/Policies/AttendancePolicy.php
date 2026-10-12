<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy extends BasePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view attendance');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Attendance $attendance): bool
    {
        if (! $this->belongsToSameCompany($user, $attendance)) {
            return false;
        }

        // User can view if they have permission OR it's their own attendance
        return $user->can('view attendance') || $this->isOwnAttendance($user, $attendance);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create attendance');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Attendance $attendance): bool
    {
        if (! $this->belongsToSameCompany($user, $attendance)) {
            return false;
        }

        return $user->can('edit attendance');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        if (! $this->belongsToSameCompany($user, $attendance)) {
            return false;
        }

        return $user->can('delete attendance');
    }

    /**
     * Determine whether the user can clock in/out.
     */
    public function clockIn(User $user): bool
    {
        // All authenticated users with employee record can clock in
        return $user->employee !== null;
    }

    /**
     * Check if attendance belongs to user's employee record.
     */
    protected function isOwnAttendance(User $user, Attendance $attendance): bool
    {
        return $user->employee && $attendance->employee_id === $user->employee->id;
    }
}
