<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminXenditBillingApiTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $adminUser;
    protected SubscriptionPlan $starterPlan;
    protected SubscriptionPlan $proPlan;
    protected PaymentGatewaySetting $xenditGateway;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create([
            'name' => 'PT Solusi Bangsa',
            'is_active' => true,
        ]);

        createStandardRoles($this->company->id);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Budi Santoso',
            'email' => 'admin@solusibangsa.com',
        ]);
        $this->adminUser->assignRole('admin');

        $this->starterPlan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'description' => 'Paket pemula',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_employees' => 20,
            'max_users' => 3,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->proPlan = SubscriptionPlan::create([
            'name' => 'Pro Enterprise',
            'slug' => 'pro-enterprise',
            'description' => 'Paket profesional',
            'price_monthly' => 500000,
            'price_yearly' => 5000000,
            'max_employees' => 100,
            'max_users' => 10,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $this->xenditGateway = PaymentGatewaySetting::create([
            'gateway' => PaymentGatewaySetting::GATEWAY_XENDIT,
            'name' => 'Xendit Gateway',
            'environment' => PaymentGatewaySetting::ENV_SANDBOX,
            'credentials' => [
                'secret_key' => 'xnd_development_dummy_key_123',
                'callback_token' => 'xendit_test_callback_token',
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    public function test_it_initiates_upgrade_via_xendit_and_returns_invoice_url(): void
    {
        Http::fake([
            'https://api.xendit.co/v2/invoices' => Http::response([
                'id' => 'xendit-inv-998877',
                'external_id' => 'PAY-TEST-001',
                'invoice_url' => 'https://checkout.xendit.co/web/xendit-inv-998877',
                'status' => 'PENDING',
                'amount' => 500000,
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/admin/settings/billing/upgrade', [
                'plan_id' => $this->proPlan->id,
                'billing_cycle' => 'monthly',
                'gateway' => 'xendit',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'gateway' => 'xendit',
                    'amount' => 500000,
                    'invoice_url' => 'https://checkout.xendit.co/web/xendit-inv-998877',
                ],
            ]);

        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'gateway' => 'xendit',
            'amount' => 500000,
            'status' => Payment::STATUS_PENDING,
            'gateway_transaction_id' => 'xendit-inv-998877',
        ]);
    }

    public function test_it_syncs_xendit_status_instantly_when_paid(): void
    {
        $subscription = Subscription::create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDays(20),
            'ends_at' => now()->addDays(10),
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV202611020001',
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PENDING,
            'subtotal' => 500000,
            'tax' => 0,
            'discount' => 0,
            'total' => 500000,
            'currency' => 'IDR',
            'description' => 'Langganan Pro Enterprise',
            'issued_at' => now(),
            'due_at' => now()->addDay(),
            'payment_method' => 'xendit',
        ]);

        $payment = Payment::create([
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'payment_number' => 'PAY-XENDIT-SYNC-1',
            'gateway' => 'xendit',
            'gateway_transaction_id' => 'xendit-inv-sync-123',
            'amount' => 500000,
            'net_amount' => 500000,
            'status' => Payment::STATUS_PENDING,
            'billing_cycle' => 'monthly',
        ]);

        // Mock Xendit API query response indicating PAID
        Http::fake([
            'https://api.xendit.co/v2/invoices/xendit-inv-sync-123' => Http::response([
                'id' => 'xendit-inv-sync-123',
                'external_id' => 'PAY-XENDIT-SYNC-1',
                'status' => 'PAID',
                'amount' => 500000,
                'payment_method' => 'BANK_TRANSFER',
                'payment_channel' => 'BCA',
            ], 200),
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/settings/billing/invoices/{$invoice->id}/sync-xendit");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'status' => 'success',
                    'is_paid' => true,
                ],
            ]);

        $payment->refresh();
        $invoice->refresh();
        $subscription->refresh();

        $this->assertEquals(Payment::STATUS_SUCCESS, $payment->status);
        $this->assertEquals(Invoice::STATUS_PAID, $invoice->status);
        $this->assertEquals(Subscription::STATUS_ACTIVE, $subscription->status);
        $this->assertNotNull($payment->paid_at);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_it_denies_syncing_xendit_for_another_tenant_invoice(): void
    {
        $otherCompany = Company::factory()->create();
        $otherPayment = Payment::factory()->pending()->create([
            'company_id' => $otherCompany->id,
            'gateway' => 'xendit',
            'gateway_transaction_id' => 'other-xendit-id',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/settings/billing/invoices/{$otherPayment->id}/sync-xendit");

        $response->assertStatus(404);
    }

    public function test_it_denies_syncing_xendit_for_manual_transfer_payment(): void
    {
        $manualPayment = Payment::create([
            'company_id' => $this->company->id,
            'payment_number' => 'PAY-MANUAL-001',
            'gateway' => 'manual',
            'amount' => 150000,
            'net_amount' => 150000,
            'status' => Payment::STATUS_PENDING,
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/admin/settings/billing/invoices/{$manualPayment->id}/sync-xendit");

        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
                'message' => 'Metode pembayaran transaksi ini bukan Xendit',
            ]);
    }
}
