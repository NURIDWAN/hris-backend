<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Payment::with(['company', 'subscription.plan'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('payment_number', 'like', '%'.$request->search.'%')
                    ->orWhereHas('company', function ($q) use ($request) {
                        $q->where('name', 'like', '%'.$request->search.'%');
                    });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        $stats = [
            'total_success' => Payment::where('status', 'success')->sum('amount'),
            'total_pending' => Payment::where('status', 'pending')->count(),
            'total_failed' => Payment::where('status', 'failed')->count(),
        ];

        return view('superadmin.payments.index', compact('payments', 'stats'));
    }

    public function show(Payment $payment): View
    {
        $payment->load(['company', 'subscription.plan']);

        return view('superadmin.payments.show', compact('payment'));
    }

    public function confirm(Payment $payment): RedirectResponse
    {
        if ($payment->status !== Payment::STATUS_PENDING) {
            return back()->with('error', 'Hanya pembayaran pending yang bisa dikonfirmasi.');
        }

        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);

        // Activate subscription if exists
        if ($payment->subscription) {
            $payment->subscription->update([
                'status' => 'active',
                'starts_at' => $payment->subscription->starts_at ?? now(),
            ]);
        }

        return back()->with('success', 'Pembayaran berhasil dikonfirmasi.');
    }

    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        if ($payment->status !== Payment::STATUS_SUCCESS) {
            return back()->with('error', 'Hanya pembayaran sukses yang bisa di-refund.');
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                ['refund_reason' => $validated['reason'] ?? null, 'refunded_at' => now()->toIso8601String()]
            ),
        ]);

        return back()->with('success', 'Pembayaran berhasil di-refund.');
    }
}
