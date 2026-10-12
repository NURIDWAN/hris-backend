<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\RateLimitLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RateLimitLogController extends Controller
{
    public function index(): View
    {
        $logs = RateLimitLog::with('user')
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $stats = [
            'total_24h' => RateLimitLog::recent(24)->count(),
            'unique_ips_24h' => RateLimitLog::recent(24)->distinct('ip_address')->count('ip_address'),
            'top_routes' => RateLimitLog::recent(24)
                ->selectRaw('route, count(*) as hits')
                ->groupBy('route')
                ->orderByDesc('hits')
                ->limit(5)
                ->pluck('hits', 'route'),
        ];

        return view('superadmin.system.rate-limits.index', compact('logs', 'stats'));
    }

    public function clear(Request $request): RedirectResponse
    {
        $days = (int) ($request->input('days', 30));

        $deleted = RateLimitLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->route('superadmin.system.rate-limits.index')
            ->with('success', "{$deleted} log rate limit yang lebih dari {$days} hari berhasil dihapus.");
    }
}
