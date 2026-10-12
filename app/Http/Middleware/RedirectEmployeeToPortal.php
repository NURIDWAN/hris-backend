<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectEmployeeToPortal
{
    /**
     * Handle an incoming request.
     * Redirect users with only 'employee' role to the portal.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admin should use superadmin dashboard, not company dashboard
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard')
                ->with('info', 'Gunakan dashboard Super Admin.');
        }

        // User must belong to a company to access company dashboard
        if (! $user->company_id) {
            abort(403, 'Access denied. You are not assigned to any company.');
        }

        // If user only has 'employee' role (not admin, hr, payroll, manager), redirect to portal
        $adminRoles = ['admin', 'hr-manager', 'payroll-manager', 'manager'];
        if ($user->hasRole('employee') && ! $user->hasAnyRole($adminRoles)) {
            return redirect()->route('portal.dashboard');
        }

        // Check if user has at least one admin role
        if (! $user->hasAnyRole($adminRoles)) {
            abort(403, 'Access denied. Admin role required.');
        }

        return $next($request);
    }
}
