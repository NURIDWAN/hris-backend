<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Services\PaymentGatewayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $paymentService
    ) {}

    public function xendit(Request $request): JsonResponse
    {
        Log::info('Xendit webhook received', [
            'ip' => $request->ip(),
            'external_id' => $request->input('external_id'),
        ]);

        // Verify callback token - REQUIRED for security
        $callbackToken = $request->header('X-CALLBACK-TOKEN');
        $gateway = PaymentGatewaySetting::where('gateway', 'xendit')
            ->where('is_active', true)
            ->first();

        if (! $gateway) {
            Log::error('Xendit gateway not configured');

            return response()->json(['message' => 'Gateway not configured'], 503);
        }

        $expectedToken = $gateway->credentials['callback_token'] ?? null;
        if (! $expectedToken) {
            Log::error('Xendit callback token not configured');

            return response()->json(['message' => 'Gateway misconfigured'], 503);
        }

        if (! hash_equals($expectedToken, $callbackToken ?? '')) {
            Log::warning('Xendit callback token mismatch', [
                'ip' => $request->ip(),
                'external_id' => $request->input('external_id'),
            ]);

            return response()->json(['message' => 'Invalid callback token'], 401);
        }

        $externalId = $request->input('external_id');

        // Check if payment exists
        $payment = Payment::where('payment_number', $externalId)->first();
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $result = $this->paymentService->handleCallback($request->all(), 'xendit');

        return response()->json($result);
    }

    public function midtrans(Request $request): JsonResponse
    {
        Log::info('Midtrans webhook received', [
            'ip' => $request->ip(),
            'order_id' => $request->input('order_id'),
        ]);

        // Verify signature - REQUIRED for security
        $gateway = PaymentGatewaySetting::where('gateway', 'midtrans')
            ->where('is_active', true)
            ->first();

        if (! $gateway) {
            Log::error('Midtrans gateway not configured');

            return response()->json(['message' => 'Gateway not configured'], 503);
        }

        $serverKey = $gateway->credentials['server_key'] ?? '';
        if (! $serverKey) {
            Log::error('Midtrans server key not configured');

            return response()->json(['message' => 'Gateway misconfigured'], 503);
        }

        $signatureKey = $request->input('signature_key');
        $orderId = $request->input('order_id');
        $statusCode = $request->input('status_code', '200');
        $grossAmount = $request->input('gross_amount');

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        if (! $signatureKey || ! hash_equals($expectedSignature, $signatureKey)) {
            Log::warning('Midtrans signature mismatch', [
                'ip' => $request->ip(),
                'order_id' => $orderId,
            ]);

            return response()->json(['message' => 'Invalid signature'], 401);
        }

        // Check if payment exists
        $payment = Payment::where('payment_number', $orderId)->first();
        if (! $payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        $result = $this->paymentService->handleCallback($request->all(), 'midtrans');

        return response()->json($result);
    }
}
