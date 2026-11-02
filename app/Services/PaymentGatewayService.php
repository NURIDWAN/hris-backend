<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentGatewayService
{
    protected ?PaymentGatewaySetting $gatewaySetting = null;

    public function __construct()
    {
        $this->loadActiveGateway();
    }

    protected function loadActiveGateway(?string $gateway = null): void
    {
        $query = PaymentGatewaySetting::where('is_active', true);

        if ($gateway) {
            $query->where('gateway', $gateway);
        }

        $this->gatewaySetting = $query->first();

        // Fallback jika belum tersimpan di DB namun ada di env config
        if (! $this->gatewaySetting && ($gateway === 'xendit' || $gateway === null)) {
            $secretKey = config('services.xendit.secret_key');
            if ($secretKey) {
                $this->gatewaySetting = new PaymentGatewaySetting([
                    'gateway' => 'xendit',
                    'name' => 'Xendit',
                    'environment' => 'sandbox',
                    'credentials' => [
                        'secret_key' => $secretKey,
                        'callback_token' => config('services.xendit.callback_token'),
                    ],
                    'is_active' => true,
                ]);
            }
        }
    }

    public function setGateway(string $gateway): self
    {
        $this->loadActiveGateway($gateway);

        return $this;
    }

    public function createTransaction(array $data): array
    {
        if (! $this->gatewaySetting) {
            throw new \Exception('No active payment gateway configured');
        }

        return match ($this->gatewaySetting->gateway) {
            PaymentGatewaySetting::GATEWAY_XENDIT => $this->createXenditInvoice($data),
            PaymentGatewaySetting::GATEWAY_MIDTRANS => $this->createMidtransTransaction($data),
            default => throw new \Exception('Unsupported payment gateway'),
        };
    }

    protected function createXenditInvoice(array $data): array
    {
        $credentials = $this->gatewaySetting?->credentials ?? [];
        $secretKey = $credentials['secret_key'] ?? config('services.xendit.secret_key') ?? '';

        if (empty($secretKey)) {
            throw new \Exception('Xendit Secret Key is not configured');
        }

        $baseUrl = ($this->gatewaySetting && $this->gatewaySetting->environment === 'production')
            ? 'https://api.xendit.co'
            : 'https://api.xendit.co';

        $frontendUrl = rtrim(env('FRONTEND_URL', config('app.frontend_url', 'http://localhost:3000')), '/');

        $payload = [
            'external_id' => $data['order_id'],
            'amount' => (int) $data['amount'],
            'payer_email' => $data['customer_email'],
            'description' => $data['description'] ?? 'Langganan Paket HRIS',
            'invoice_duration' => 86400, // 24 hours
            'currency' => 'IDR',
            'customer' => [
                'given_names' => $data['customer_name'] ?? 'Pelanggan',
                'email' => $data['customer_email'],
            ],
            'items' => [
                [
                    'name' => $data['description'] ?? 'Paket Langganan',
                    'quantity' => 1,
                    'price' => (int) $data['amount'],
                    'category' => 'Subscription',
                ],
            ],
            'success_redirect_url' => $frontendUrl.'/settings?tab=billing&status=success&payment_id='.$data['order_id'],
            'failure_redirect_url' => $frontendUrl.'/settings?tab=billing&status=failed&payment_id='.$data['order_id'],
        ];

        $response = Http::withBasicAuth($secretKey, '')
            ->post($baseUrl.'/v2/invoices', $payload);

        if (! $response->successful()) {
            Log::error('Xendit invoice creation failed', [
                'status' => $response->status(),
                'response' => $response->json(),
                'payload' => $payload,
            ]);
            $errorMessage = $response->json('message') ?? 'Failed to create Xendit invoice';
            throw new \Exception($errorMessage);
        }

        $result = $response->json();

        return [
            'invoice_id' => $result['id'],
            'invoice_url' => $result['invoice_url'],
            'status' => $result['status'],
            'amount' => $result['amount'],
            'expiry_date' => $result['expiry_date'] ?? null,
        ];
    }

    protected function createMidtransTransaction(array $data): array
    {
        $credentials = $this->gatewaySetting->credentials;
        $serverKey = $credentials['server_key'] ?? '';

        $baseUrl = $this->gatewaySetting->environment === 'production'
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $payload = [
            'transaction_details' => [
                'order_id' => $data['order_id'],
                'gross_amount' => $data['amount'],
            ],
            'customer_details' => [
                'first_name' => $data['customer_name'],
                'email' => $data['customer_email'],
            ],
        ];

        $response = Http::withBasicAuth($serverKey, '')
            ->post($baseUrl, $payload);

        if (! $response->successful()) {
            Log::error('Midtrans transaction creation failed', [
                'response' => $response->json(),
                'payload' => $payload,
            ]);
            throw new \Exception('Failed to create Midtrans transaction');
        }

        $result = $response->json();

        return [
            'token' => $result['token'],
            'redirect_url' => $result['redirect_url'],
        ];
    }

    public function handleCallback(array $payload, string $gateway): array
    {
        $this->setGateway($gateway);

        return match ($gateway) {
            'xendit' => $this->handleXenditCallback($payload),
            'midtrans' => $this->handleMidtransCallback($payload),
            default => ['success' => false, 'message' => 'Unknown gateway'],
        };
    }

    protected function handleXenditCallback(array $payload): array
    {
        $externalId = $payload['external_id'] ?? null;
        $status = $payload['status'] ?? null;
        $invoiceId = $payload['id'] ?? null;

        if (! $externalId) {
            return ['success' => false, 'message' => 'Invalid payload'];
        }

        $payment = Payment::where('payment_number', $externalId)->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        // Update payment based on status
        if (in_array($status, ['PAID', 'SETTLED'])) {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'gateway_transaction_id' => $invoiceId,
                'payment_method' => $this->mapXenditPaymentMethod($payload['payment_method'] ?? ''),
                'payment_channel' => $payload['payment_channel'] ?? null,
                'gateway_response' => $payload,
                'paid_at' => now(),
            ]);

            // Update associated invoice
            if ($payment->invoice_id) {
                Invoice::where('id', $payment->invoice_id)->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => now(),
                ]);
            }

            // Extend subscription if applicable
            $this->extendSubscription($payment);

            return ['success' => true, 'message' => 'Payment confirmed'];
        }

        if (in_array($status, ['EXPIRED', 'FAILED'])) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'gateway_transaction_id' => $invoiceId,
                'gateway_response' => $payload,
            ]);

            if ($payment->invoice_id) {
                Invoice::where('id', $payment->invoice_id)->update([
                    'status' => $status === 'EXPIRED' ? Invoice::STATUS_EXPIRED : Invoice::STATUS_CANCELLED,
                ]);
            }

            return ['success' => true, 'message' => 'Payment marked as failed'];
        }

        return ['success' => true, 'message' => 'Status unchanged'];
    }

    protected function handleMidtransCallback(array $payload): array
    {
        $orderId = $payload['order_id'] ?? null;
        $transactionStatus = $payload['transaction_status'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (! $orderId) {
            return ['success' => false, 'message' => 'Invalid payload'];
        }

        $payment = Payment::where('payment_number', $orderId)->first();

        if (! $payment) {
            return ['success' => false, 'message' => 'Payment not found'];
        }

        // Settlement = success
        if ($transactionStatus === 'settlement' || $transactionStatus === 'capture') {
            $payment->update([
                'status' => Payment::STATUS_SUCCESS,
                'gateway_transaction_id' => $transactionId,
                'payment_method' => $this->mapMidtransPaymentMethod($payload['payment_type'] ?? ''),
                'gateway_response' => $payload,
                'paid_at' => now(),
            ]);

            $this->extendSubscription($payment);

            return ['success' => true, 'message' => 'Payment confirmed'];
        }

        // Failed statuses
        if (in_array($transactionStatus, ['deny', 'cancel', 'expire'])) {
            $payment->update([
                'status' => Payment::STATUS_FAILED,
                'gateway_transaction_id' => $transactionId,
                'gateway_response' => $payload,
            ]);

            return ['success' => true, 'message' => 'Payment marked as failed'];
        }

        return ['success' => true, 'message' => 'Status unchanged'];
    }

    protected function extendSubscription(Payment $payment): void
    {
        if (! $payment->subscription_id) {
            return;
        }

        $subscription = Subscription::find($payment->subscription_id);
        if (! $subscription) {
            return;
        }

        $baseDate = ($subscription->ends_at && $subscription->ends_at->isFuture())
            ? $subscription->ends_at->copy()
            : now();

        $newEndsAt = $payment->billing_cycle === 'yearly'
            ? $baseDate->addYear()
            : $baseDate->addMonth();

        $subscription->update([
            'status' => 'active',
            'ends_at' => $newEndsAt,
        ]);

        if ($payment->company_id) {
            $payment->company?->update(['is_active' => true]);
        }

        Log::info('Subscription extended and company activated', [
            'subscription_id' => $subscription->id,
            'payment_id' => $payment->id,
            'new_ends_at' => $newEndsAt,
        ]);
    }

    public function syncXenditInvoice(Payment $payment): array
    {
        $this->loadActiveGateway('xendit');
        $credentials = $this->gatewaySetting?->credentials ?? [];
        $secretKey = $credentials['secret_key'] ?? config('services.xendit.secret_key') ?? '';

        if (empty($secretKey)) {
            return [
                'success' => false,
                'message' => 'Kunci rahasia (Secret Key) Xendit belum dikonfigurasi',
            ];
        }

        $invoiceId = $payment->gateway_transaction_id;

        $baseUrl = ($this->gatewaySetting && $this->gatewaySetting->environment === 'production')
            ? 'https://api.xendit.co'
            : 'https://api.xendit.co';

        $response = null;
        if ($invoiceId) {
            $response = Http::withBasicAuth($secretKey, '')
                ->get($baseUrl."/v2/invoices/{$invoiceId}");
        }

        if (! $response || ! $response->successful()) {
            $response = Http::withBasicAuth($secretKey, '')
                ->get($baseUrl."/v2/invoices?external_id={$payment->payment_number}");

            $invoices = $response->json();
            if (! $response->successful() || empty($invoices) || ! isset($invoices[0])) {
                return [
                    'success' => false,
                    'message' => 'Status invoice tidak ditemukan di Xendit',
                ];
            }
            $invoiceData = $invoices[0];
        } else {
            $invoiceData = $response->json();
        }

        $status = strtoupper($invoiceData['status'] ?? '');

        if (in_array($status, ['PAID', 'SETTLED'])) {
            $this->handleXenditCallback($invoiceData);

            return [
                'success' => true,
                'is_paid' => true,
                'status' => 'success',
                'message' => 'Pembayaran telah diverifikasi sukses dan langganan aktif!',
                'data' => $invoiceData,
            ];
        }

        if ($status === 'EXPIRED') {
            $this->handleXenditCallback($invoiceData);

            return [
                'success' => true,
                'is_paid' => false,
                'status' => 'expired',
                'message' => 'Tagihan Xendit telah kedaluwarsa.',
                'data' => $invoiceData,
            ];
        }

        return [
            'success' => true,
            'is_paid' => false,
            'status' => strtolower($status),
            'message' => 'Menunggu pembayaran dari pelanggan.',
            'data' => $invoiceData,
        ];
    }

    protected function mapXenditPaymentMethod(?string $method): string
    {
        return match ($method) {
            'BANK_TRANSFER' => Payment::METHOD_BANK_TRANSFER,
            'EWALLET' => Payment::METHOD_EWALLET,
            'CREDIT_CARD' => Payment::METHOD_CREDIT_CARD,
            'QR_CODE', 'QRIS' => Payment::METHOD_QRIS,
            default => $method ?? '',
        };
    }

    protected function mapMidtransPaymentMethod(?string $method): string
    {
        return match ($method) {
            'bank_transfer', 'echannel' => Payment::METHOD_BANK_TRANSFER,
            'gopay', 'shopeepay' => Payment::METHOD_EWALLET,
            'credit_card' => Payment::METHOD_CREDIT_CARD,
            'qris' => Payment::METHOD_QRIS,
            default => $method ?? '',
        };
    }
}
