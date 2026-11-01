<?php

namespace App\Http\Controllers\Api\V1\Admin\Superadmin;

use App\Http\Controllers\Api\V1\Admin\AdminBaseController;
use App\Models\PaymentGatewaySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends AdminBaseController
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
     * Menampilkan daftar konfigurasi payment gateway.
     */
    public function index(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $gateways = PaymentGatewaySetting::orderBy('sort_order')->get();

        return $this->successResponse($gateways, 'Daftar payment gateway berhasil diambil');
    }

    /**
     * Menambahkan konfigurasi gateway baru.
     */
    public function store(Request $request): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $validated = $request->validate([
            'gateway' => ['required', 'string', 'max:50', 'unique:payment_gateway_settings,gateway'],
            'name' => ['required', 'string', 'max:255'],
            'environment' => ['nullable', 'in:sandbox,production'],
            'credentials' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['environment'] = $validated['environment'] ?? 'sandbox';
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $gateway = PaymentGatewaySetting::create($validated);

        return $this->successResponse($gateway, 'Payment gateway berhasil ditambahkan', 201);
    }

    /**
     * Menampilkan detail konfigurasi gateway.
     */
    public function show(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $gateway = PaymentGatewaySetting::findOrFail($id);

        return $this->successResponse($gateway, 'Detail payment gateway berhasil diambil');
    }

    /**
     * Memperbarui konfigurasi gateway.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $gateway = PaymentGatewaySetting::findOrFail($id);

        $validated = $request->validate([
            'gateway' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('payment_gateway_settings', 'gateway')->ignore($gateway->id),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'environment' => ['nullable', 'in:sandbox,production'],
            'credentials' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $gateway->update($validated);

        return $this->successResponse($gateway->fresh(), 'Payment gateway berhasil diperbarui');
    }

    /**
     * Menghapus konfigurasi gateway.
     */
    public function destroy(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $gateway = PaymentGatewaySetting::findOrFail($id);
        $gateway->delete();

        return $this->successResponse(null, 'Payment gateway berhasil dihapus');
    }

    /**
     * Mengubah status aktif / nonaktif gateway.
     */
    public function toggleStatus(Request $request, int|string $id): JsonResponse
    {
        if ($deny = $this->authorizeSuperAdmin($request)) {
            return $deny;
        }

        $gateway = PaymentGatewaySetting::findOrFail($id);
        $gateway->is_active = ! $gateway->is_active;
        $gateway->save();

        $statusText = $gateway->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return $this->successResponse($gateway, "Payment gateway {$gateway->name} berhasil {$statusText}");
    }
}
