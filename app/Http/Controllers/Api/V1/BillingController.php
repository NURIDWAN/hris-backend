<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpgradeSubscriptionRequest;
use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $paymentService
    ) {}

    /**
     * Dapatkan company context dari user yang sedang login.
     */
    protected function getCompany(Request $request): ?Company
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        return $user->company ?? Company::find($user->company_id);
    }

    /**
     * Mengambil informasi paket langganan saat ini dan kuota karyawan.
     */
    public function subscription(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $subscription = Subscription::where('company_id', $company->id)
            ->with('plan')
            ->latest()
            ->first();

        $currentEmployees = $company->users()->count();
        $maxEmployees = $company->max_employees;
        $canAddEmployee = $company->canAddEmployee();

        $endsAt = $subscription?->ends_at ?? $company->subscription_ends_at;
        $daysRemaining = $endsAt ? max(0, (int) now()->diffInDays($endsAt, false)) : null;
        $isExpired = $endsAt ? $endsAt->isPast() : false;

        $availableGateways = PaymentGatewaySetting::where('is_active', true)
            ->orderBy('sort_order')
            ->get(['gateway', 'name', 'environment']);

        return response()->json([
            'success' => true,
            'message' => 'Informasi langganan berhasil diambil.',
            'data' => [
                'company' => [
                    'id' => $company->id,
                    'name' => $company->name,
                    'subscription_plan' => $company->subscription_plan,
                    'is_active' => (bool) $company->is_active,
                    'is_demo_mode' => (bool) $company->is_demo_mode,
                ],
                'subscription' => $subscription ? [
                    'id' => $subscription->id,
                    'status' => $subscription->status,
                    'billing_cycle' => $subscription->billing_cycle,
                    'started_at' => $subscription->started_at?->toIso8601String(),
                    'ends_at' => $subscription->ends_at?->toIso8601String(),
                    'trial_ends_at' => $subscription->trial_ends_at?->toIso8601String(),
                    'days_remaining' => $daysRemaining,
                    'is_expired' => $isExpired,
                    'plan' => $subscription->plan,
                ] : null,
                'quota' => [
                    'current_employees' => $currentEmployees,
                    'max_employees' => $maxEmployees,
                    'can_add_employee' => $canAddEmployee,
                ],
                'available_gateways' => $availableGateways,
            ],
        ]);
    }

    /**
     * Memproses permohonan upgrade / perpanjangan paket langganan.
     */
    public function upgrade(UpgradeSubscriptionRequest $request): JsonResponse
    {
        $company = $this->getCompany($request);
        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $user = $request->user();
        $validated = $request->validated();

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $billingCycle = $validated['billing_cycle'];
        $gateway = $validated['gateway'];

        $price = $billingCycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;

        $subscription = Subscription::where('company_id', $company->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        if (! $subscription) {
            $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();
            $subscription = Subscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'billing_cycle' => $billingCycle,
                'started_at' => now(),
                'ends_at' => $endsAt,
            ]);
        }

        $payment = Payment::create([
            'company_id' => $company->id,
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

        // Pembayaran transfer manual
        if ($gateway === 'manual') {
            $gatewaySetting = PaymentGatewaySetting::where('gateway', 'manual')->first();

            return response()->json([
                'success' => true,
                'message' => 'Tagihan berhasil dibuat. Silakan lakukan transfer bank dan konfirmasi kepada admin.',
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) $payment->amount,
                    'gateway' => 'manual',
                    'bank_details' => $gatewaySetting?->credentials ?? [
                        'bank_name' => 'BCA',
                        'account_number' => '1234567890',
                        'account_name' => 'PT GajiPro Indonesia',
                    ],
                    'expired_at' => $payment->expired_at?->toIso8601String(),
                ],
            ], 201);
        }

        // Pembayaran online payment gateway (Xendit / Midtrans)
        try {
            $this->paymentService->setGateway($gateway);
            $result = $this->paymentService->createTransaction([
                'order_id' => $payment->payment_number,
                'amount' => (int) $price,
                'customer_name' => $user->name,
                'customer_email' => $user->email,
                'description' => "Langganan {$plan->name} ({$billingCycle})",
            ]);

            $payment->update([
                'gateway_order_id' => $payment->payment_number,
                'gateway_transaction_id' => $result['invoice_id'] ?? $result['token'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibuat. Silakan selesaikan pembayaran.',
                'data' => [
                    'payment_id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'amount' => (float) $payment->amount,
                    'gateway' => $gateway,
                    'invoice_url' => $result['invoice_url'] ?? $result['redirect_url'] ?? null,
                    'token' => $result['token'] ?? null,
                    'expired_at' => $payment->expired_at?->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembayaran ke payment gateway: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mengambil riwayat invoice / pembayaran tenant.
     */
    public function invoices(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        $payments = Payment::where('company_id', $company->id)
            ->with('subscription.plan')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat invoice berhasil diambil.',
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Mengambil detail satu tagihan / invoice.
     */
    public function invoiceDetail(Request $request, int|string $id): JsonResponse
    {
        $company = $this->getCompany($request);
        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $payment = Payment::where('company_id', $company->id)
            ->with('subscription.plan')
            ->find($id);

        if (! $payment) {
            return response()->json([
                'success' => false,
                'message' => 'Invoice pembayaran tidak ditemukan.',
            ], 404);
        }

        $gatewaySetting = PaymentGatewaySetting::where('gateway', $payment->gateway)->first();

        return response()->json([
            'success' => true,
            'message' => 'Detail invoice berhasil diambil.',
            'data' => [
                'payment' => $payment,
                'gateway_setting' => $gatewaySetting ? [
                    'gateway' => $gatewaySetting->gateway,
                    'name' => $gatewaySetting->name,
                    'instructions' => $gatewaySetting->credentials,
                ] : null,
            ],
        ]);
    }

    /**
     * Membatalkan langganan yang sedang aktif.
     */
    public function cancel(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);
        if (! $company) {
            return response()->json([
                'success' => false,
                'message' => 'Perusahaan tidak ditemukan.',
            ], 404);
        }

        $subscription = Subscription::where('company_id', $company->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada langganan aktif yang dapat dibatalkan.',
            ], 404);
        }

        $subscription->update([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Langganan berhasil dibatalkan.',
            'data' => $subscription->fresh(),
        ]);
    }
}
