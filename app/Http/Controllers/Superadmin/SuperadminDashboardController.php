<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\View\View;

class SuperadminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_companies' => Company::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'total_revenue' => Payment::where('status', 'success')->sum('amount'),
            'pending_payments' => Payment::where('status', 'pending')->count(),
        ];

        $recentPayments = Payment::with(['company'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $recentSubscriptions = Subscription::with(['company', 'plan'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('superadmin.dashboard', compact('stats', 'recentPayments', 'recentSubscriptions'));
    }
}
