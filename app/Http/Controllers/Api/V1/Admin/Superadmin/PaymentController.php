<?php

namespace App\Http\Controllers\Api\V1\Admin\Superadmin;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends AdminBaseController
{
    protected function authorizeSuperAdmin(Request $request): ?JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->isSuperAdmin()) {
            return $this->errorResponse('Akses ditolak. Fitur ini hanya untuk Superadmin.', 403);
        }

        return null;
    }

    /**
     * Menampilkan daftar transaksi pembayaran seluruh tenant.
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($companyQuery) use ($search) {
                        $companyQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $payments = $query->paginate($perPage);

        $stats = [
            'total_success' => (float) Payment::where('status', Payment::STATUS_SUCCESS)->sum('amount'),
            'total_pending' => Payment::where('status', Payment::STATUS_PENDING)->count(),
            'total_failed' => Payment::where('status', Payment::STATUS_FAILED)->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi pembayaran berhasil diambil',
            'data' => $payments->items(),
            'meta' => array_merge([
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ], $stats),
        ]);
    }

    /**
     * Menampilkan detail satu transaksi pembayaran.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $payment = Payment::with(['company', 'subscription.plan'])
            ->findOrFail($id);

        return $this->successResponse($payment, 'Detail pembayaran berhasil diambil');
    }

    /**
     * Konfirmasi manual pembayaran pending (misal bukti transfer manual telah diverifikasi).
     */
    public function confirm(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $payment = Payment::findOrFail($id);

        if ($payment->status !== Payment::STATUS_PENDING) {
            return $this->errorResponse('Hanya pembayaran pending yang dapat dikonfirmasi.', 400);
        }

        $payment->update([
            'status' => Payment::STATUS_SUCCESS,
            'paid_at' => now(),
        ]);

        // Aktifkan / perpanjang langganan tenant
        if ($payment->subscription_id) {
            $subscription = Subscription::find($payment->subscription_id);
            if ($subscription) {
                $currentEndsAt = $subscription->ends_at ?? now();
                $newEndsAt = $payment->billing_cycle === Subscription::CYCLE_YEARLY
                    ? $currentEndsAt->addYear()
                    : $currentEndsAt->addMonth();

                $subscription->update([
                    'status' => Subscription::STATUS_ACTIVE,
                    'starts_at' => $subscription->starts_at ?? now(),
                    'ends_at' => $newEndsAt,
                ]);

                // Update juga subscription_ends_at di company
                $payment->company?->update([
                    'subscription_ends_at' => $newEndsAt,
                    'is_active' => true,
                ]);
            }
        }

        return $this->successResponse($payment->fresh(['company', 'subscription.plan']), 'Pembayaran berhasil dikonfirmasi dan subscription diaktifkan');
    }

    /**
     * Mencatat refund pembayaran.
     */
    public function refund(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $payment = Payment::findOrFail($id);

        if ($payment->status !== Payment::STATUS_SUCCESS) {
            return $this->errorResponse('Hanya pembayaran sukses yang dapat di-refund.', 400);
        }

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'gateway_response' => array_merge(
                $payment->gateway_response ?? [],
                [
                    'refund_reason' => $validated['reason'] ?? null,
                    'refunded_at' => now()->toIso8601String(),
                ]
            ),
        ]);

        return $this->successResponse($payment->fresh(), 'Pembayaran berhasil di-refund');
    }
}
