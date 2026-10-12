<?php

namespace App\Permission;

use Spatie\Permission\Contracts\PermissionsTeamResolver;

class CompanyTeamResolver implements PermissionsTeamResolver
{
    protected int|string|null $teamId = null;

    /**
     * Get the current team ID from the tenant context.
     * Returns the company_id of the currently authenticated user.
     */
    public function getPermissionsTeamId(): int|string|null
    {
        // First check if team ID was explicitly set
        if ($this->teamId !== null) {
            return $this->teamId;
        }

        // Try to get from bound tenant
        if (app()->bound('tenant') && app('tenant')) {
            return app('tenant')->id;
        }

        // Fallback to authenticated user's company_id
        if (auth()->check() && auth()->user()->company_id) {
            return auth()->user()->company_id;
        }

        return null;
    }

    /**
     * Set the team id for teams/groups support.
     *
     * @param  int|string|\Illuminate\Database\Eloquent\Model|null  $id
     */
    public function setPermissionsTeamId($id): void
    {
        if ($id instanceof \Illuminate\Database\Eloquent\Model) {
            $this->teamId = $id->getKey();
        } else {
            $this->teamId = $id;
        }

        // Also update the global helper
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
