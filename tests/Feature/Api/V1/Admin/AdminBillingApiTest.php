<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Admin Billing API', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create([
            'name' => 'PT Solusi Teknologi',
            'email' => 'admin@solusitek.id',
        ]);

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        Sanctum::actingAs($this->adminUser);

        // Seed Plans
        $this->basicPlan = SubscriptionPlan::factory()->create([
            'name' => 'Starter',
            'slug' => 'starter',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'max_employees' => 20,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $this->proPlan = SubscriptionPlan::factory()->create([
            'name' => 'Professional',
            'slug' => 'professional',
            'price_monthly' => 350000,
            'price_yearly' => 3500000,
            'max_employees' => 50,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Seed Gateway Setting
        $this->manualGateway = PaymentGatewaySetting::factory()->create([
            'gateway' => PaymentGatewaySetting::GATEWAY_MANUAL,
            'name' => 'Transfer Bank Manual',
            'is_active' => true,
            'sort_order' => 1,
            'credentials' => [
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'PT GajiPro HRIS',
            ],
        ]);
    });

    it('can retrieve billing summary including active subscription and usage', function () {
        // Create an active subscription
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        // Create 5 employees
        Employee::factory()->count(5)->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/api/v1/admin/settings/billing');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.subscription.plan.name', 'Starter')
            ->assertJsonPath('data.subscription.status', 'active')
            ->assertJsonPath('data.usage.current_employees', 5)
            ->assertJsonPath('data.usage.max_employees', 20)
            ->assertJsonPath('data.usage.employee_percentage', 25)
            ->assertJsonCount(2, 'data.plans')
            ->assertJsonCount(1, 'data.gateways');
    });

    it('can initiate upgrade plan with manual transfer', function () {
        $response = $this->postJson('/api/v1/admin/settings/billing/upgrade', [
            'plan_id' => $this->proPlan->id,
            'billing_cycle' => 'yearly',
            'gateway' => 'manual',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gateway', 'manual')
            ->assertJsonPath('data.amount', 3500000)
            ->assertJsonPath('data.billing_cycle', 'yearly')
            ->assertJsonPath('data.plan_name', 'Professional')
            ->assertJsonPath('data.bank_info.bank_name', 'BCA');

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->proPlan->id,
            'billing_cycle' => 'yearly',
        ]);

        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'amount' => 3500000,
            'gateway' => 'manual',
            'status' => Payment::STATUS_PENDING,
        ]);
    });

    it('can cancel active subscription', function () {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->basicPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'started_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $response = $this->postJson('/api/v1/admin/settings/billing/cancel');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('subscriptions', [
            'id' => $subscription->id,
            'status' => Subscription::STATUS_CANCELLED,
        ]);
    });

    it('can retrieve invoice and payment details', function () {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->proPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'status' => Invoice::STATUS_PAID,
            'total' => 3500000,
            'subtotal' => 3500000,
        ]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'amount' => 3500000,
            'gateway' => 'manual',
            'status' => Payment::STATUS_SUCCESS,
        ]);

        $response = $this->getJson("/api/v1/admin/settings/billing/invoices/{$payment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.payment_number', $payment->payment_number)
            ->assertJsonPath('data.amount', 3500000)
            ->assertJsonPath('data.company.name', 'PT Solusi Teknologi');
    });

    it('denies accessing invoice of another tenant', function () {
        $otherCompany = Company::factory()->create(['name' => 'PT Lain']);

        $otherPayment = Payment::factory()->create([
            'company_id' => $otherCompany->id,
            'amount' => 100000,
        ]);

        $response = $this->getJson("/api/v1/admin/settings/billing/invoices/{$otherPayment->id}");

        $response->assertStatus(404);
    });
});
