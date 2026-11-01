<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Models\PaymentGatewaySetting;
use Illuminate\Http\JsonResponse;

class PublicPaymentGatewayController extends Controller
{
    /**
     * Get active payment gateway options for tenant checkout.
     */
    public function index(): JsonResponse
    {
        try {
            $gateways = PaymentGatewaySetting::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'gateway', 'name', 'environment', 'is_active', 'sort_order']);

            if ($gateways->isEmpty()) {
                $gateways = collect($this->defaultGateways());
            }
        } catch (\Throwable $e) {
            $gateways = collect($this->defaultGateways());
        }

        return response()->json([
            'success' => true,
            'data' => $gateways,
        ]);
    }

    /**
     * Fallback payment gateway options.
     */
    protected function defaultGateways(): array
    {
        return [
            [
                'id' => 1,
                'gateway' => 'xendit',
                'name' => 'Xendit (Virtual Account, QRIS, E-Wallet)',
                'environment' => 'sandbox',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'id' => 2,
                'gateway' => 'midtrans',
                'name' => 'Midtrans (BCA, Mandiri, BNI, GoPay, ShopeePay)',
                'environment' => 'sandbox',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'id' => 3,
                'gateway' => 'manual',
                'name' => 'Transfer Bank Manual',
                'environment' => 'production',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];
    }
}
