<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class BasePolicy
{
    /**
     * Check if user belongs to same company as the model.
     */
    protected function belongsToSameCompany(User $user, Model $model): bool
    {
        return $user->company_id === $model->company_id;
    }

    /**
     * Run before any authorization check.
     * Super admins bypass all checks.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admin (no company_id) has full access
        if ($user->is_superadmin && $user->company_id === null) {
            return true;
        }

        return null;
    }
}
