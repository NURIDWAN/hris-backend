<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\BlockedIp;
use App\Models\SecurityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlockedIpController extends Controller
{
    public function index(Request $request): View
    {
        $query = BlockedIp::with('blockedByUser')
            ->orderBy('created_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by IP
        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%'.$request->ip.'%');
        }

        $blockedIps = $query->paginate(50)->withQueryString();

        // Stats
        $stats = [
            'total_active' => BlockedIp::active()->count(),
            'total_permanent' => BlockedIp::active()->whereNull('blocked_until')->count(),
            'total_temporary' => BlockedIp::active()->whereNotNull('blocked_until')->count(),
        ];

        return view('superadmin.security.blocked-ips.index', compact('blockedIps', 'stats'));
    }

    public function create(): View
    {
        return view('superadmin.security.blocked-ips.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string|max:255',
            'duration' => 'nullable|integer|min:1',
        ]);

        // Check if already blocked
        if (BlockedIp::isBlocked($request->ip_address)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'IP ini sudah diblokir.');
        }

        BlockedIp::block(
            $request->ip_address,
            $request->reason,
            auth()->id(),
            $request->duration
        );

        // Log the action
        SecurityLog::log(
            'ip_blocked_manual',
            'info',
            "IP {$request->ip_address} diblokir manual oleh ".auth()->user()->name,
            ['ip' => $request->ip_address, 'reason' => $request->reason]
        );

        return redirect()->route('superadmin.security.blocked-ips.index')
            ->with('success', "IP {$request->ip_address} berhasil diblokir.");
    }

    public function unblock(BlockedIp $blockedIp): RedirectResponse
    {
        $ip = $blockedIp->ip_address;
        $blockedIp->update(['is_active' => false]);

        // Log the action
        SecurityLog::log(
            'ip_unblocked',
            'info',
            "IP {$ip} di-unblock oleh ".auth()->user()->name,
            ['ip' => $ip]
        );

        return redirect()->back()
            ->with('success', "IP {$ip} berhasil di-unblock.");
    }

    public function destroy(BlockedIp $blockedIp): RedirectResponse
    {
        $ip = $blockedIp->ip_address;
        $blockedIp->delete();

        return redirect()->route('superadmin.security.blocked-ips.index')
            ->with('success', "Record IP {$ip} berhasil dihapus.");
    }
}
