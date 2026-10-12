<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpgradePlanRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $paymentService
    ) {}

    public function index(): View
    {
        $tenant = app('tenant');

        $currentSubscription = Subscription::where('company_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $invoices = Invoice::where('company_id', $tenant->id)
            ->orderBy('issued_at', 'desc')
            ->take(10)
            ->get();

        return view('settings.billing.index', compact('currentSubscription', 'plans', 'invoices'));
    }

    public function upgrade(): View
    {
        $tenant = app('tenant');

        $currentSubscription = Subscription::where('company_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $gateways = PaymentGatewaySetting::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return view('settings.billing.upgrade', compact('currentSubscription', 'plans', 'gateways'));
    }

    public function processUpgrade(UpgradePlanRequest $request): JsonResponse|RedirectResponse
    {
        $tenant = app('tenant');
        $validated = $request->validated();

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $billingCycle = $validated['billing_cycle'];
        $gateway = $validated['gateway'];

        // Calculate price based on billing cycle
        $price = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        // Get or create subscription
        $subscription = Subscription::where('company_id', $tenant->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        if (! $subscription) {
            $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
            $subscription = Subscription::create([
                'company_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'billing_cycle' => $billingCycle,
                'started_at' => now(),
                'ends_at' => $endsAt,
            ]);
        }

        // Create payment record
        $payment = Payment::create([
            'company_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'gateway' => $gateway,
            'amount' => $price,
            'fee' => 0,
            'net_amount' => $price,
            'currency' => 'IDR',
            'status' => Payment::STATUS_PENDING,
            'description' => "Langganan {$plan->name} ({$billingCycle})",
            'billing_cycle' => $billingCycle,
            'expired_at' => now()->addDay(),
        ]);

        // Handle manual payment
        if ($gateway === 'manual') {
            return redirect()->route('settings.billing.invoice', $payment)
                ->with('success', 'Silakan transfer ke rekening yang tertera dan konfirmasi pembayaran.');
        }

        // Handle online payment gateway
        try {
            $this->paymentService->setGateway($gateway);
            $result = $this->paymentService->createTransaction([
                'order_id' => $payment->payment_number,
                'amount' => (int) $price,
                'customer_name' => auth()->user()->name,
                'customer_email' => auth()->user()->email,
                'description' => "Langganan {$plan->name} ({$billingCycle})",
            ]);

            // Update payment with gateway info
            $payment->update([
                'gateway_order_id' => $payment->payment_number,
                'gateway_transaction_id' => $result['invoice_id'] ?? $result['token'] ?? null,
            ]);

            // Return JSON for AJAX requests
            if ($request->wantsJson()) {
                return response()->json([
                    'invoice_url' => $result['invoice_url'] ?? $result['redirect_url'] ?? null,
                    'payment_id' => $payment->id,
                ]);
            }

            // Redirect for standard requests
            return redirect($result['invoice_url'] ?? $result['redirect_url']);

        } catch (\Exception $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);

            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }

            return redirect()->route('settings.billing.index')
                ->with('error', 'Gagal membuat pembayaran: '.$e->getMessage());
        }
    }

    public function cancel(): RedirectResponse
    {
        $tenant = app('tenant');

        $subscription = Subscription::where('company_id', $tenant->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        if ($subscription) {
            $subscription->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
            ]);

            return redirect()->route('settings.billing.index')
                ->with('success', 'Langganan berhasil dibatalkan.');
        }

        return redirect()->route('settings.billing.index')
            ->with('error', 'Tidak ada langganan aktif.');
    }

    public function invoice(Payment $payment): View
    {
        $tenant = app('tenant');

        if ($payment->company_id !== $tenant->id) {
            abort(404);
        }

        $payment->load('subscription.plan');

        $gatewaySetting = PaymentGatewaySetting::where('gateway', $payment->gateway)
            ->where('is_active', true)
            ->first();

        return view('settings.billing.invoice', compact('payment', 'gatewaySetting'));
    }
}
