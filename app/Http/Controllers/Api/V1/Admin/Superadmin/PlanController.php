<?php

namespace App\Http\Controllers\Api\V1\Admin\Superadmin;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\SubscriptionPlan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlanController extends AdminBaseController
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
     * Menampilkan seluruh paket langganan.
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $plans = SubscriptionPlan::withCount(['subscriptions', 'companies'])
            ->orderBy('sort_order', 'asc')
            ->get();

        return $this->successResponse($plans, 'Daftar paket langganan berhasil diambil');
    }

    /**
     * Membuat paket langganan baru.
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:subscription_plans,slug'],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0'],
            'max_employees' => ['required', 'integer', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['slug'] = ! empty($validated['slug']) ? Str::slug($validated['slug']) : Str::slug($validated['name']);
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['max_users'] = $validated['max_users'] ?? 0;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $plan = SubscriptionPlan::create($validated);

        return $this->successResponse($plan, 'Paket langganan berhasil dibuat', 201);
    }

    /**
     * Menampilkan detail satu paket langganan.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $plan = SubscriptionPlan::withCount(['subscriptions', 'companies'])
            ->findOrFail($id);

        return $this->successResponse($plan, 'Detail paket langganan berhasil diambil');
    }

    /**
     * Memperbarui data paket langganan.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $plan = SubscriptionPlan::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('subscription_plans', 'slug')->ignore($plan->id),
            ],
            'description' => ['nullable', 'string'],
            'price_monthly' => ['sometimes', 'required', 'numeric', 'min:0'],
            'price_yearly' => ['sometimes', 'required', 'numeric', 'min:0'],
            'max_employees' => ['sometimes', 'required', 'integer', 'min:0'],
            'max_users' => ['nullable', 'integer', 'min:0'],
            'features' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if (isset($validated['slug']) && ! empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['slug']);
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $plan->update($validated);

        return $this->successResponse($plan->fresh(), 'Paket langganan berhasil diperbarui');
    }

    /**
     * Menghapus paket langganan.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $plan = SubscriptionPlan::findOrFail($id);

        // Jangan hapus jika ada subscription aktif
        if ($plan->subscriptions()->exists()) {
            return $this->errorResponse('Paket tidak dapat dihapus karena masih digunakan oleh subscription.', 422);
        }

        $plan->delete();

        return $this->successResponse(null, 'Paket langganan berhasil dihapus');
    }

    /**
     * Mengubah status aktif / nonaktif paket langganan.
     */
    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $plan = SubscriptionPlan::findOrFail($id);
        $plan->is_active = ! $plan->is_active;
        $plan->save();

        $statusText = $plan->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->successResponse($plan, "Paket {$plan->name} berhasil {$statusText}");
    }
}
