<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abort with 404 when the application is running in single-tenant mode.
 *
 * Use on routes that belong exclusively to the multi-tenant SaaS experience
 * (public registration, billing, superadmin panel, payment gateway webhooks).
 */
class AbortIfSingleTenantMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (single_tenant_mode()) {
            abort(404);
        }

        return $next($request);
    }
}
