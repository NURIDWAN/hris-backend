<?php

namespace App\Http\Middleware;

use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class CheckBlockedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if table doesn't exist (migrations not run yet)
        if (! Schema::hasTable('blocked_ips')) {
            return $next($request);
        }

        $ip = $request->ip();

        try {
            if (BlockedIp::isBlocked($ip)) {
                // Log blocked access attempt
                if (Schema::hasTable('security_logs')) {
                    SecurityLog::log(
                        'blocked_ip_access',
                        'warning',
                        "Blocked IP {$ip} attempted to access: {$request->path()}",
                        null,
                        $request
                    );
                }

                // Return 403 for web requests, JSON for API
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Access denied. Your IP has been blocked.',
                    ], Response::HTTP_FORBIDDEN);
                }

                abort(Response::HTTP_FORBIDDEN, 'Access denied. Your IP has been blocked.');
            }
        } catch (\Exception $e) {
            // If any error occurs, allow request to continue
            return $next($request);
        }

        return $next($request);
    }
}
