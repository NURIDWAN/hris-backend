<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailLogController extends Controller
{
    public function index(Request $request): View
    {
        $query = EmailLog::query()->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('to', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $emailLogs = $query->paginate(50)->withQueryString();

        $stats = [
            'total' => EmailLog::count(),
            'sent' => EmailLog::sent()->count(),
            'failed' => EmailLog::failed()->count(),
            'today' => EmailLog::whereDate('created_at', today())->count(),
        ];

        return view('superadmin.system.email-logs.index', compact('emailLogs', 'stats'));
    }

    public function show(EmailLog $emailLog): View
    {
        return view('superadmin.system.email-logs.show', compact('emailLog'));
    }

    public function resend(EmailLog $emailLog): RedirectResponse
    {
        try {
            Mail::raw($emailLog->body ?? '', function ($message) use ($emailLog) {
                $message->to($emailLog->to)
                    ->subject('[Resend] '.$emailLog->subject);

                if ($emailLog->from) {
                    $message->from($emailLog->from);
                }
            });

            return redirect()->back()
                ->with('success', 'Email berhasil dikirim ulang ke '.$emailLog->to);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Gagal mengirim ulang email: '.$e->getMessage());
        }
    }
}
