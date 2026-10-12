<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function index(Request $request): View
    {
        $query = Subscription::with(['company', 'plan'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('company', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            });
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        return view('superadmin.subscriptions.index', compact('subscriptions'));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['company', 'plan', 'payments']);

        return view('superadmin.subscriptions.show', compact('subscription'));
    }

    public function activate(Subscription $subscription): RedirectResponse
    {
        $subscription->update([
            'status' => 'active',
            'started_at' => $subscription->started_at ?? now(),
        ]);

        return back()->with('success', 'Subscription berhasil diaktifkan.');
    }

    public function suspend(Subscription $subscription): RedirectResponse
    {
        $subscription->update(['status' => 'cancelled']);

        return back()->with('success', 'Subscription berhasil disuspend.');
    }

    public function extend(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:24'],
        ]);

        $currentEndDate = $subscription->ends_at ?? now();
        $subscription->update([
            'ends_at' => $currentEndDate->addMonths($validated['months']),
        ]);

        return back()->with('success', 'Subscription berhasil diperpanjang.');
    }

    public function changePlan(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $subscription->update([
            'subscription_plan_id' => $validated['subscription_plan_id'],
        ]);

        return back()->with('success', 'Plan subscription berhasil diubah.');
    }
}
