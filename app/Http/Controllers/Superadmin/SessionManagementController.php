<?php

namespace App\Http\Controllers\Superadmin;

use App\Helpers\UserAgentParser;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionManagementController extends Controller
{
    public function index(): View
    {
        $rawSessions = DB::table('sessions')
            ->leftJoin('users', 'sessions.user_id', '=', 'users.id')
            ->select([
                'sessions.id',
                'sessions.user_id',
                'sessions.ip_address',
                'sessions.user_agent',
                'sessions.last_activity',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->orderByDesc('sessions.last_activity')
            ->paginate(50);

        $sessions = $rawSessions->through(function ($session) {
            $parsed = UserAgentParser::parse($session->user_agent);

            $session->browser = $parsed['browser'];
            $session->os = $parsed['os'];
            $session->last_activity_at = Carbon::createFromTimestamp($session->last_activity);

            return $session;
        });

        $stats = [
            'total' => DB::table('sessions')->count(),
            'authenticated' => DB::table('sessions')->whereNotNull('user_id')->count(),
            'guest' => DB::table('sessions')->whereNull('user_id')->count(),
        ];

        return view('superadmin.system.sessions.index', compact('sessions', 'stats'));
    }

    public function destroy(string $id): RedirectResponse
    {
        $session = DB::table('sessions')->where('id', $id)->first();

        if (! $session) {
            abort(404);
        }

        DB::table('sessions')->where('id', $id)->delete();

        return redirect()->route('superadmin.system.sessions.index')
            ->with('success', 'Sesi berhasil dihapus.');
    }
}
