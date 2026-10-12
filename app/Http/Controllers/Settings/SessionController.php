<?php

namespace App\Http\Controllers\Settings;

use App\Helpers\UserAgentParser;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(Request $request): View
    {
        $currentSessionId = $request->session()->getId();

        $sessions = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) {
                $parsed = UserAgentParser::parse($session->user_agent);

                return (object) [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'browser' => $parsed['browser'],
                    'os' => $parsed['os'],
                    'last_activity' => \Carbon\Carbon::createFromTimestamp($session->last_activity),
                ];
            });

        return view('settings.sessions.index', compact('sessions', 'currentSessionId'));
    }

    public function destroy(Request $request, string $id): RedirectResponse
    {
        $currentSessionId = $request->session()->getId();

        if ($id === $currentSessionId) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus sesi yang sedang aktif.');
        }

        $session = DB::table('sessions')
            ->where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $session) {
            abort(404);
        }

        DB::table('sessions')->where('id', $id)->delete();

        return redirect()->route('settings.sessions.index')
            ->with('success', 'Sesi berhasil dihapus.');
    }
}
