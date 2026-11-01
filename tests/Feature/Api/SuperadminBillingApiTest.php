<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\PaymentGatewaySetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    // Regular admin
    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'is_superadmin' => false,
    ]);
    $this->admin->assignRole('admin');

    // Superadmin user (company_id null or has is_superadmin = true)
    $this->superadmin = User::factory()->create([
        'company_id' => null,
        'is_superadmin' => true,
    ]);

    $this->plan = SubscriptionPlan::factory()->create([
        'name' => 'Pro Plan',
        'slug' => 'pro-plan',
        'price_monthly' => 200000,
        'price_yearly' => 2000000,
    ]);
});

describe('Superadmin Billing API Authorization', function () {
    it('denies access to regular admin', function () {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/superadmin/subscriptions');
        $response->assertStatus(403);

        $response2 = $this->getJson('/api/v1/admin/superadmin/payments');
        $response2->assertStatus(403);
    });
});

describe('Superadmin Subscriptions Management', function () {
    it('can list all tenant subscriptions', function () {
        Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        Sanctum::actingAs($this->superadmin);

        $response = $this->getJson('/api/v1/admin/superadmin/subscriptions');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'company_id', 'status', 'company', 'plan'],
                ],
            ]);
    });

    it('can extend subscription duration', function () {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        Sanctum::actingAs($this->superadmin);

        $response = $this->postJson("/api/v1/admin/superadmin/subscriptions/{$subscription->id}/extend", [
            'months' => 3,
        ]);

        $response->assertOk();
        expect(now()->diffInMonths($subscription->fresh()->ends_at))->toBeGreaterThanOrEqual(3);
    });
});

describe('Superadmin Payments Management', function () {
    it('can confirm pending payment and activate subscription', function () {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'pending',
            'billing_cycle' => 'monthly',
        ]);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $subscription->id,
            'status' => Payment::STATUS_PENDING,
            'billing_cycle' => 'monthly',
            'amount' => 200000,
        ]);

        Sanctum::actingAs($this->superadmin);

        $response = $this->postJson("/api/v1/admin/superadmin/payments/{$payment->id}/confirm");

        $response->assertOk();
        expect($payment->fresh()->status)->toBe(Payment::STATUS_SUCCESS);
        expect($subscription->fresh()->status)->toBe('active');
        expect($this->company->fresh()->is_active)->toBeTrue();
    });
});

describe('Superadmin Plans & Gateway Settings', function () {
    it('can toggle plan status', function () {
        Sanctum::actingAs($this->superadmin);

        $response = $this->postJson("/api/v1/admin/superadmin/plans/{$this->plan->id}/toggle-status");

        $response->assertOk();
        expect($this->plan->fresh()->is_active)->toBeFalse();
    });

    it('can create and toggle payment gateway setting', function () {
        Sanctum::actingAs($this->superadmin);

        $response = $this->postJson('/api/v1/admin/superadmin/payment-gateways', [
            'gateway' => 'tripay',
            'name' => 'Tripay Indonesia',
            'environment' => 'sandbox',
            'is_active' => true,
        ]);

        $response->assertCreated();

        $gateway = PaymentGatewaySetting::where('gateway', 'tripay')->first();
        expect($gateway)->not->toBeNull();

        $toggleResponse = $this->postJson("/api/v1/admin/superadmin/payment-gateways/{$gateway->id}/toggle-status");
        $toggleResponse->assertOk();
        expect($gateway->fresh()->is_active)->toBeFalse();
    });
});
