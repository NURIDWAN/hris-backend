<?php

namespace App\Http\Controllers\Api\V1\Admin\Settings;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Http\Requests\Settings\UpgradePlanRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\PaymentGatewayService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BillingController extends AdminBaseController
{
    public function __construct(
        protected PaymentGatewayService $paymentService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $subscription = Subscription::where('company_id', $company->id)
            ->with('plan')
            ->latest()
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'slug' => $plan->slug,
                'description' => $plan->description,
                'price_monthly' => (float) $plan->price_monthly,
                'price_yearly' => (float) $plan->price_yearly,
                'max_employees' => (int) $plan->max_employees,
                'max_users' => (int) $plan->max_users,
                'features' => $plan->features ?? [],
                'sort_order' => (int) $plan->sort_order,
            ]);

        $gateways = PaymentGatewaySetting::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(function ($gw) {
                $item = [
                    'id' => $gw->id,
                    'gateway' => $gw->gateway,
                    'name' => $gw->name,
                    'is_manual' => $gw->gateway === PaymentGatewaySetting::GATEWAY_MANUAL,
                ];

                if ($gw->gateway === PaymentGatewaySetting::GATEWAY_MANUAL) {
                    $item['bank_info'] = [
                        'bank_name' => $gw->getCredential('bank_name', 'BCA'),
                        'account_number' => $gw->getCredential('account_number', '1234567890'),
                        'account_name' => $gw->getCredential('account_name', 'PT GajiPro HRIS'),
                    ];
                }

                return $item;
            });

        // Fallback default manual gateway jika belum ada di database
        if ($gateways->isEmpty()) {
            $gateways = collect([
                [
                    'id' => 1,
                    'gateway' => 'manual',
                    'name' => 'Transfer Bank Manual',
                    'is_manual' => true,
                    'bank_info' => [
                        'bank_name' => 'BCA',
                        'account_number' => '1234567890',
                        'account_name' => 'PT GajiPro HRIS',
                    ],
                ],
            ]);
        }

        $currentEmployees = $company->employees()->count();
        $maxEmployees = $subscription?->plan?->max_employees ?? 25;
        $usage = [
            'current_employees' => $currentEmployees,
            'max_employees' => $maxEmployees,
            'employee_percentage' => $maxEmployees > 0 ? min(100, round(($currentEmployees / $maxEmployees) * 100, 1)) : 0,
            'is_quota_exceeded' => $maxEmployees > 0 && $currentEmployees > $maxEmployees,
        ];

        $recentPayments = Payment::where('company_id', $company->id)
            ->with(['subscription.plan', 'invoice'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'invoice_id' => $p->invoice_id,
                'payment_number' => $p->payment_number,
                'invoice_number' => $p->invoice?->invoice_number ?? $p->payment_number,
                'amount' => (float) $p->amount,
                'fee' => (float) $p->fee,
                'net_amount' => (float) $p->net_amount,
                'currency' => $p->currency ?? 'IDR',
                'status' => $p->status,
                'status_label' => Payment::STATUSES[$p->status] ?? ucfirst($p->status),
                'gateway' => $p->gateway,
                'billing_cycle' => $p->billing_cycle,
                'description' => $p->description,
                'plan_name' => $p->subscription?->plan?->name ?? 'Paket Kustom',
                'created_at' => $p->created_at?->toISOString(),
                'expired_at' => $p->expired_at?->toISOString(),
                'paid_at' => $p->paid_at?->toISOString(),
                'invoice_url' => $p->gateway_response['invoice_url'] ?? null,
                'payment_proof' => $p->gateway_response['payment_proof'] ?? null,
            ]);

        $subscriptionData = null;
        if ($subscription) {
            $daysRemaining = $subscription->ends_at
                ? max(0, (int) now()->diffInDays($subscription->ends_at, false))
                : null;

            $subscriptionData = [
                'id' => $subscription->id,
                'plan_id' => $subscription->subscription_plan_id,
                'status' => $subscription->status,
                'status_label' => Subscription::STATUSES[$subscription->status] ?? ucfirst($subscription->status),
                'billing_cycle' => $subscription->billing_cycle,
                'started_at' => $subscription->started_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'is_trial' => (bool) ($subscription->trial_ends_at && $subscription->trial_ends_at->isFuture()),
                'days_remaining' => $daysRemaining,
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                    'slug' => $subscription->plan->slug,
                    'description' => $subscription->plan->description,
                    'price_monthly' => (float) $subscription->plan->price_monthly,
                    'price_yearly' => (float) $subscription->plan->price_yearly,
                    'max_employees' => (int) $subscription->plan->max_employees,
                    'max_users' => (int) $subscription->plan->max_users,
                    'features' => $subscription->plan->features ?? [],
                ] : null,
            ];
        }

        return $this->successResponse([
            'subscription' => $subscriptionData,
            'usage' => $usage,
            'plans' => $plans,
            'gateways' => $gateways,
            'recent_invoices' => $recentPayments,
        ], 'Data billing dan langganan berhasil diambil');
    }

    public function upgrade(UpgradePlanRequest $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $validated = $request->validated();
        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);
        $billingCycle = $validated['billing_cycle'];
        $gateway = $validated['gateway'];

        $price = $billingCycle === Subscription::CYCLE_YEARLY
            ? (float) $plan->price_yearly
            : (float) $plan->price_monthly;

        // Ambil atau buat subscription
        $subscription = Subscription::where('company_id', $company->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        $endsAt = $billingCycle === Subscription::CYCLE_YEARLY ? now()->addYear() : now()->addMonth();

        if (! $subscription) {
            $subscription = Subscription::create([
                'company_id' => $company->id,
                'subscription_plan_id' => $plan->id,
                'status' => Subscription::STATUS_PENDING,
                'billing_cycle' => $billingCycle,
                'started_at' => now(),
                'ends_at' => $endsAt,
            ]);
        }

        // Buat Invoice
        $sequence = str_pad((string) (Invoice::whereDate('created_at', today())->count() + 1), 4, '0', STR_PAD_LEFT);
        $invoiceNumber = 'INV'.now()->format('Ymd').$sequence;

        $invoice = Invoice::create([
            'invoice_number' => $invoiceNumber,
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => $price,
            'tax' => 0,
            'discount' => 0,
            'total' => $price,
            'currency' => 'IDR',
            'description' => "Langganan {$plan->name} ({$billingCycle})",
            'issued_at' => now(),
            'due_at' => now()->addDay(),
            'payment_method' => $gateway === 'manual' ? 'bank_transfer' : $gateway,
        ]);

        // Buat Payment Record
        $payment = Payment::create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
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

        $gatewaySetting = PaymentGatewaySetting::where('gateway', $gateway)->first();
        $bankInfo = null;

        if ($gateway === 'manual') {
            $bankInfo = [
                'bank_name' => $gatewaySetting?->getCredential('bank_name') ?? 'BCA',
                'account_number' => $gatewaySetting?->getCredential('account_number') ?? '1234567890',
                'account_name' => $gatewaySetting?->getCredential('account_name') ?? 'PT GajiPro HRIS',
            ];

            return $this->successResponse([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'payment_number' => $payment->payment_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => (float) $price,
                'gateway' => $gateway,
                'billing_cycle' => $billingCycle,
                'plan_name' => $plan->name,
                'expired_at' => $payment->expired_at?->toISOString(),
                'invoice_url' => null,
                'bank_info' => $bankInfo,
            ], 'Tagihan pembayaran berhasil dibuat. Silakan lakukan transfer.');
        }

        // Online Gateway (Xendit / Midtrans)
        try {
            $this->paymentService->setGateway($gateway);
            $user = $request->user();
            $result = $this->paymentService->createTransaction([
                'order_id' => $payment->payment_number,
                'amount' => (int) $price,
                'customer_name' => $user?->name ?? $company->name,
                'customer_email' => $user?->email ?? $company->email,
                'description' => "Langganan {$plan->name} ({$billingCycle})",
            ]);

            $payment->update([
                'gateway_order_id' => $payment->payment_number,
                'gateway_transaction_id' => $result['invoice_id'] ?? $result['token'] ?? null,
                'gateway_response' => array_merge($payment->gateway_response ?? [], [
                    'invoice_url' => $result['invoice_url'] ?? $result['redirect_url'] ?? null,
                    'invoice_id' => $result['invoice_id'] ?? null,
                ]),
            ]);

            return $this->successResponse([
                'payment_id' => $payment->id,
                'invoice_id' => $invoice->id,
                'payment_number' => $payment->payment_number,
                'invoice_number' => $invoice->invoice_number,
                'amount' => (float) $price,
                'gateway' => $gateway,
                'billing_cycle' => $billingCycle,
                'plan_name' => $plan->name,
                'expired_at' => $payment->expired_at?->toISOString(),
                'invoice_url' => $result['invoice_url'] ?? $result['redirect_url'] ?? null,
                'bank_info' => null,
            ], 'Pembayaran berhasil diinisiasi. Silakan selesaikan pembayaran.');

        } catch (Exception $e) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            $invoice->update(['status' => Invoice::STATUS_CANCELLED]);

            return $this->errorResponse('Gagal membuat transaksi gateway: '.$e->getMessage(), 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $subscription = Subscription::where('company_id', $company->id)
            ->where('status', Subscription::STATUS_ACTIVE)
            ->first();

        if (! $subscription) {
            return $this->errorResponse('Tidak ada paket langganan aktif untuk dibatalkan', 400);
        }

        $subscription->update([
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return $this->successResponse([
            'subscription_id' => $subscription->id,
            'status' => Subscription::STATUS_CANCELLED,
            'cancelled_at' => $subscription->cancelled_at?->toISOString(),
        ], 'Langganan berhasil dibatalkan');
    }

    public function invoice(Request $request, int $id): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        // Cari berdasarkan Payment ID atau Invoice ID
        $payment = Payment::where('company_id', $company->id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('invoice_id', $id);
            })
            ->with(['subscription.plan', 'invoice', 'company'])
            ->first();

        if (! $payment) {
            return $this->errorResponse('Invoice atau bukti pembayaran tidak ditemukan', 404);
        }

        $gatewaySetting = PaymentGatewaySetting::where('gateway', $payment->gateway)->first();
        $bankInfo = null;
        if ($payment->gateway === 'manual') {
            $bankInfo = [
                'bank_name' => $gatewaySetting?->getCredential('bank_name') ?? 'BCA',
                'account_number' => $gatewaySetting?->getCredential('account_number') ?? '1234567890',
                'account_name' => $gatewaySetting?->getCredential('account_name') ?? 'PT GajiPro HRIS',
            ];
        }

        $plan = $payment->subscription?->plan;

        return $this->successResponse([
            'id' => $payment->id,
            'payment_number' => $payment->payment_number,
            'invoice_number' => $payment->invoice?->invoice_number ?? $payment->payment_number,
            'amount' => (float) $payment->amount,
            'fee' => (float) $payment->fee,
            'net_amount' => (float) $payment->net_amount,
            'currency' => $payment->currency ?? 'IDR',
            'status' => $payment->status,
            'status_label' => Payment::STATUSES[$payment->status] ?? ucfirst($payment->status),
            'gateway' => $payment->gateway,
            'billing_cycle' => $payment->billing_cycle,
            'description' => $payment->description,
            'created_at' => $payment->created_at?->toISOString(),
            'expired_at' => $payment->expired_at?->toISOString(),
            'paid_at' => $payment->paid_at?->toISOString(),
            'bank_info' => $bankInfo,
            'invoice_url' => $payment->gateway_response['invoice_url'] ?? null,
            'payment_proof' => $payment->gateway_response['payment_proof'] ?? null,
            'company' => [
                'name' => $company->name,
                'email' => $company->email,
                'address' => $company->address,
                'phone' => $company->phone,
            ],
            'plan' => $plan ? [
                'id' => $plan->id,
                'name' => $plan->name,
                'max_employees' => $plan->max_employees,
            ] : null,
        ], 'Detail faktur berhasil diambil');
    }

    /**
     * Konfirmasi pembayaran manual dan unggah bukti transfer oleh tenant admin.
     */
    public function confirmPayment(Request $request, int $id): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $payment = Payment::where('company_id', $company->id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('invoice_id', $id);
            })
            ->with(['invoice', 'subscription.plan'])
            ->first();

        if (! $payment) {
            return $this->errorResponse('Faktur atau transaksi pembayaran tidak ditemukan', 404);
        }

        if ($payment->status === Payment::STATUS_SUCCESS) {
            return $this->errorResponse('Pembayaran ini telah terkonfirmasi lunas.', 400);
        }

        $validated = $request->validate([
            'sender_bank' => ['required', 'string', 'max:50'],
            'sender_name' => ['required', 'string', 'max:100'],
            'sender_account' => ['required', 'string', 'max:50'],
            'transfer_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'proof_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $proofFilePath = null;
        $proofFileUrl = null;

        if ($request->hasFile('proof_file')) {
            $path = $request->file('proof_file')->store('payment-proofs/'.$company->id, 'public');
            $proofFilePath = $path;
            $proofFileUrl = asset('storage/'.$path);
        }

        $proofData = [
            'sender_bank' => $validated['sender_bank'],
            'sender_name' => $validated['sender_name'],
            'sender_account' => $validated['sender_account'],
            'transfer_date' => $validated['transfer_date'] ?? now()->toDateString(),
            'notes' => $validated['notes'] ?? null,
            'file_path' => $proofFilePath,
            'file_url' => $proofFileUrl,
            'submitted_at' => now()->toIso8601String(),
        ];

        // Simpan ke gateway_response
        $existingGatewayResponse = $payment->gateway_response ?? [];
        $existingGatewayResponse['payment_proof'] = $proofData;

        $payment->update([
            'gateway_response' => $existingGatewayResponse,
            'payment_method' => Payment::METHOD_MANUAL,
        ]);

        if ($payment->invoice) {
            $invoiceDetails = $payment->invoice->payment_details ?? [];
            $invoiceDetails['payment_proof'] = $proofData;
            $payment->invoice->update([
                'payment_details' => $invoiceDetails,
            ]);
        }

        return $this->successResponse([
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'status' => $payment->status,
            'payment_proof' => $proofData,
        ], 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi dari Superadmin.');
    }

    /**
     * Sinkronisasi status invoice Xendit secara instan dari sisi tenant.
     */
    public function syncXenditStatus(Request $request, int $id): JsonResponse
    {
        $company = $this->getCompany($request);

        if (! $company) {
            return $this->errorResponse('Data perusahaan tidak ditemukan', 404);
        }

        $payment = Payment::where('company_id', $company->id)
            ->where(function ($query) use ($id) {
                $query->where('id', $id)
                    ->orWhere('invoice_id', $id);
            })
            ->first();

        if (! $payment) {
            return $this->errorResponse('Tagihan atau bukti pembayaran tidak ditemukan', 404);
        }

        if ($payment->gateway !== PaymentGatewaySetting::GATEWAY_XENDIT) {
            return $this->errorResponse('Metode pembayaran transaksi ini bukan Xendit', 400);
        }

        $result = $this->paymentService->syncXenditInvoice($payment);

        if (! $result['success']) {
            return $this->errorResponse($result['message'], 400);
        }

        return $this->successResponse([
            'payment_id' => $payment->id,
            'status' => $result['status'],
            'is_paid' => $result['is_paid'] ?? false,
        ], $result['message']);
    }
}
