<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');

    $this->plan = SubscriptionPlan::factory()->create([
        'name' => 'Professional',
        'price_monthly' => 299000,
        'price_yearly' => 2990000,
    ]);

    $this->subscription = Subscription::factory()->create([
        'company_id' => $this->company->id,
        'subscription_plan_id' => $this->plan->id,
        'status' => 'active',
    ]);
});

describe('Payment Gateway Service - Xendit', function () {
    it('creates xendit invoice', function () {
        // Mock Xendit API response
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv-xendit-12345',
                'external_id' => 'PAY-'.time(),
                'invoice_url' => 'https://checkout.xendit.co/web/inv-xendit-12345',
                'status' => 'PENDING',
                'amount' => 299000,
            ], 200),
        ]);

        PaymentGatewaySetting::factory()->xendit()->create();

        $service = app(PaymentGatewayService::class);
        $result = $service->createTransaction([
            'order_id' => 'PAY-'.time(),
            'amount' => 299000,
            'customer_name' => 'Test User',
            'customer_email' => 'test@example.com',
            'description' => 'Subscription Professional',
        ]);

        expect($result)->toHaveKeys(['invoice_id', 'invoice_url']);
        expect($result['invoice_id'])->toBe('inv-xendit-12345');
    });

    it('handles xendit callback notification', function () {
        PaymentGatewaySetting::factory()->xendit()->create();

        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $this->subscription->id,
            'payment_number' => 'PAY-XENDIT-123',
            'gateway' => 'xendit',
            'amount' => 299000,
        ]);

        // Mock notification payload from Xendit
        $notificationPayload = [
            'external_id' => 'PAY-XENDIT-123',
            'status' => 'PAID',
            'amount' => 299000,
            'payment_method' => 'BANK_TRANSFER',
            'payment_channel' => 'BCA',
            'id' => 'xendit-inv-123',
        ];

        $service = app(PaymentGatewayService::class);
        $result = $service->handleCallback($notificationPayload, 'xendit');

        expect($result['success'])->toBeTrue();

        $payment->refresh();
        expect($payment->status)->toBe('success');
        expect($payment->gateway_transaction_id)->toBe('xendit-inv-123');
    });

    it('marks payment as failed on expired callback', function () {
        PaymentGatewaySetting::factory()->xendit()->create();

        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'payment_number' => 'PAY-EXPIRED-123',
            'gateway' => 'xendit',
        ]);

        $notificationPayload = [
            'external_id' => 'PAY-EXPIRED-123',
            'status' => 'EXPIRED',
            'amount' => 299000,
            'id' => 'xendit-inv-expired',
        ];

        $service = app(PaymentGatewayService::class);
        $result = $service->handleCallback($notificationPayload, 'xendit');

        $payment->refresh();
        expect($payment->status)->toBe('failed');
    });

    it('extends subscription on successful payment', function () {
        PaymentGatewaySetting::factory()->xendit()->create();

        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addDays(5),
        ]);

        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'payment_number' => 'PAY-EXTEND-123',
            'gateway' => 'xendit',
            'billing_cycle' => 'monthly',
        ]);

        $notificationPayload = [
            'external_id' => 'PAY-EXTEND-123',
            'status' => 'PAID',
            'amount' => 299000,
            'payment_method' => 'EWALLET',
            'id' => 'xendit-inv-extend',
        ];

        $service = app(PaymentGatewayService::class);
        $service->handleCallback($notificationPayload, 'xendit');

        $subscription->refresh();
        // Subscription should be extended by 1 month from current ends_at
        expect($subscription->ends_at->gt(now()->addDays(30)))->toBeTrue();
    });
});

describe('Payment Checkout Flow', function () {
    it('displays checkout page', function () {
        $this->actingAs($this->admin);

        PaymentGatewaySetting::factory()->xendit()->create();

        $response = $this->get('/settings/billing/upgrade');

        $response->assertStatus(200);
        $response->assertViewIs('settings.billing.upgrade');
    });

    it('creates payment and returns invoice url', function () {
        $this->actingAs($this->admin);

        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'inv-checkout-123',
                'invoice_url' => 'https://checkout.xendit.co/web/inv-checkout-123',
                'status' => 'PENDING',
                'amount' => 299000,
            ], 200),
        ]);

        PaymentGatewaySetting::factory()->xendit()->create();

        $response = $this->postJson('/settings/billing/upgrade', [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'monthly',
            'gateway' => 'xendit',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['invoice_url', 'payment_id']);

        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'gateway' => 'xendit',
            'status' => 'pending',
        ]);
    });

    it('validates checkout request', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/billing/upgrade', []);

        $response->assertSessionHasErrors(['plan_id', 'billing_cycle', 'gateway']);
    });
});

describe('Payment Webhook', function () {
    it('receives xendit notification webhook', function () {
        PaymentGatewaySetting::factory()->xendit()->create();

        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'payment_number' => 'PAY-WEBHOOK-123',
            'gateway' => 'xendit',
            'amount' => 299000,
        ]);

        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => 'PAY-WEBHOOK-123',
            'status' => 'PAID',
            'amount' => 299000,
            'payment_method' => 'QRIS',
            'id' => 'xendit-webhook-inv',
        ]);

        $response->assertStatus(200);

        $payment->refresh();
        expect($payment->status)->toBe('success');
    });

    it('returns 404 for invalid external_id', function () {
        PaymentGatewaySetting::factory()->xendit()->create();

        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => 'INVALID-ORDER-ID',
            'status' => 'PAID',
            'amount' => 299000,
        ]);

        $response->assertStatus(404);
    });

    it('verifies xendit callback token', function () {
        $gatewaySetting = PaymentGatewaySetting::factory()->xendit()->create();

        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'payment_number' => 'PAY-VERIFY-123',
            'gateway' => 'xendit',
        ]);

        // Request with valid callback token
        $response = $this->postJson('/api/webhooks/xendit', [
            'external_id' => 'PAY-VERIFY-123',
            'status' => 'PAID',
            'amount' => 299000,
            'id' => 'xendit-verify-inv',
        ], [
            'X-CALLBACK-TOKEN' => 'test-callback-token',
        ]);

        $response->assertStatus(200);
    });
});

describe('Manual Payment', function () {
    it('creates manual payment request', function () {
        $this->actingAs($this->admin);

        PaymentGatewaySetting::factory()->manual()->create();

        $response = $this->post('/settings/billing/upgrade', [
            'plan_id' => $this->plan->id,
            'billing_cycle' => 'yearly',
            'gateway' => 'manual',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'gateway' => 'manual',
            'status' => 'pending',
            'billing_cycle' => 'yearly',
        ]);
    });

    it('shows bank transfer instructions for manual payment', function () {
        $this->actingAs($this->admin);

        PaymentGatewaySetting::factory()->manual()->create([
            'credentials' => [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'PT GajiPro Indonesia',
            ],
        ]);

        $payment = Payment::factory()->pending()->create([
            'company_id' => $this->company->id,
            'gateway' => 'manual',
        ]);

        $response = $this->get('/settings/billing/invoices/'.$payment->id);

        $response->assertStatus(200);
        $response->assertSee('BCA');
        $response->assertSee('1234567890');
    });
});

describe('Payment Model', function () {
    it('generates unique payment number', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
        ]);

        expect($payment->payment_number)->toStartWith('PAY');
        expect(strlen($payment->payment_number))->toBeGreaterThan(10);
    });

    it('has correct status badge', function () {
        $pending = Payment::factory()->pending()->create(['company_id' => $this->company->id]);
        $success = Payment::factory()->success()->create(['company_id' => $this->company->id]);
        $failed = Payment::factory()->failed()->create(['company_id' => $this->company->id]);

        expect($pending->status_label)->toBe('Menunggu');
        expect($success->status_label)->toBe('Berhasil');
        expect($failed->status_label)->toBe('Gagal');
    });

    it('formats amount correctly', function () {
        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'amount' => 299000,
        ]);

        expect($payment->formatted_amount)->toBe('Rp 299.000');
    });
});
