<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployee
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Super admin cannot access employee portal
        if ($user->isSuperAdmin()) {
            return redirect()->route('superadmin.dashboard');
        }

        // Check if user has employee role
        if (! $user->hasRole('employee')) {
            abort(403, 'Access denied. Employee role required.');
        }

        // If user has admin/hr/manager roles, redirect them to admin dashboard
        $adminRoles = ['admin', 'hr-manager', 'payroll-manager', 'manager'];
        if ($user->hasAnyRole($adminRoles)) {
            return redirect()->route('dashboard')
                ->with('info', 'Anda memiliki akses admin. Gunakan dashboard admin.');
        }

        return $next($request);
    }
}
