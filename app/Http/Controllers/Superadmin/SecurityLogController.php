<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecurityLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = SecurityLog::with('user')
            ->orderBy('created_at', 'desc');

        // Filter by event type
        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        // Filter by severity
        if ($request->filled('severity')) {
            $query->where('severity', $request->severity);
        }

        // Filter by IP
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%'.$request->ip.'%');
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(50)->withQueryString();

        // Get unique event types for filter dropdown
        $eventTypes = SecurityLog::distinct()->pluck('event_type')->sort();

        // Stats
        $stats = [
            'total_24h' => SecurityLog::recent(24)->count(),
            'critical_24h' => SecurityLog::recent(24)->where('severity', 'critical')->count(),
            'warning_24h' => SecurityLog::recent(24)->where('severity', 'warning')->count(),
            'blocked_ips' => BlockedIp::active()->count(),
        ];

        return view('superadmin.security.logs.index', compact('logs', 'eventTypes', 'stats'));
    }

    public function show(SecurityLog $securityLog): View
    {
        return view('superadmin.security.logs.show', compact('securityLog'));
    }

    public function blockIp(Request $request, SecurityLog $securityLog): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
            'duration' => 'nullable|integer|min:1',
        ]);

        $reason = $request->input('reason', 'Blocked from security log #'.$securityLog->id);
        $duration = $request->input('duration'); // null = permanent

        BlockedIp::block(
            $securityLog->ip_address,
            $reason,
            auth()->id(),
            $duration
        );

        return redirect()->back()->with('success', "IP {$securityLog->ip_address} telah diblokir.");
    }

    public function destroy(SecurityLog $securityLog): RedirectResponse
    {
        $securityLog->delete();

        return redirect()->route('superadmin.security.logs.index')
            ->with('success', 'Log berhasil dihapus.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->validate([
            'older_than' => 'required|integer|min:1',
        ]);

        $days = $request->input('older_than');
        $deleted = SecurityLog::where('created_at', '<', now()->subDays($days))->delete();

        return redirect()->back()
            ->with('success', "{$deleted} log yang lebih dari {$days} hari telah dihapus.");
    }
}
