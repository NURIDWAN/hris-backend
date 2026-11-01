<?php

namespace App\Http\Controllers\Api\V1\Admin\Superadmin;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends AdminBaseController
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
     * Menampilkan daftar seluruh langganan tenant.
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $query = Subscription::with(['company', 'plan'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('company', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $subscriptions = $query->paginate($perPage);

        return $this->paginatedResponse($subscriptions, 'Daftar subscription berhasil diambil');
    }

    /**
     * Menampilkan detail satu langganan.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $subscription = Subscription::with(['company', 'plan', 'payments'])
            ->findOrFail($id);

        return $this->successResponse($subscription, 'Detail subscription berhasil diambil');
    }

    /**
     * Mengaktifkan subscription tenant secara manual.
     */
    public function activate(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $subscription = Subscription::findOrFail($id);
        $subscription->update([
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => $subscription->started_at ?? now(),
        ]);

        return $this->successResponse($subscription->fresh(['company', 'plan']), 'Subscription berhasil diaktifkan');
    }

    /**
     * Menghentikan / suspend subscription tenant.
     */
    public function suspend(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $subscription = Subscription::findOrFail($id);
        $subscription->update(['status' => Subscription::STATUS_CANCELLED]);

        return $this->successResponse($subscription->fresh(['company', 'plan']), 'Subscription berhasil disuspend');
    }

    /**
     * Memperpanjang masa aktif subscription tenant sejumlah n bulan.
     */
    public function extend(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'months' => ['required', 'integer', 'min:1', 'max:60'],
        ]);

        $subscription = Subscription::findOrFail($id);
        $currentEndDate = $subscription->ends_at ?? now();
        $subscription->update([
            'ends_at' => $currentEndDate->addMonths($validated['months']),
        ]);

        return $this->successResponse($subscription->fresh(['company', 'plan']), 'Subscription berhasil diperpanjang');
    }

    /**
     * Mengubah paket langganan tenant secara manual.
     */
    public function changePlan(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $subscription = Subscription::findOrFail($id);
        $subscription->update([
            'subscription_plan_id' => $validated['subscription_plan_id'],
        ]);

        return $this->successResponse($subscription->fresh(['company', 'plan']), 'Plan subscription berhasil diubah');
    }
}
