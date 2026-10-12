<?php

namespace App\Observers;

use App\Models\Company;
use RuntimeException;

/**
 * Enforce single-tenant invariant at the model level.
 *
 * When running in single-tenant (on-premise) mode, the system must host
 * exactly one company. Any attempt to create a second company is rejected
 * regardless of the source (web, CLI, seeder).
 */
class CompanyObserver
{
    public function creating(Company $company): void
    {
        if (! single_tenant_mode()) {
            return;
        }

        $lockedId = locked_company_id();

        $existing = Company::query()
            ->withTrashed()
            ->first();

        if ($existing === null) {
            return;
        }

        if ((int) $existing->id === $lockedId) {
            throw new RuntimeException(
                'Cannot create additional company: running in single-tenant mode.'
            );
        }
    }
}
