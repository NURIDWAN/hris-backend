<?php

namespace App\Http\Middleware;

use App\Models\RateLimitLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class LogRateLimitHit
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if ($response->getStatusCode() !== 429) {
            return;
        }

        if (! Schema::hasTable('rate_limit_logs')) {
            return;
        }

        RateLimitLog::create([
            'ip_address' => $request->ip(),
            'user_id' => $request->user()?->id,
            'route' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
