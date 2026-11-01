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
    $this->company = Company::factory()->create(['max_employees' => 20]);
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');

    $this->starterPlan = SubscriptionPlan::factory()->create([
        'name' => 'Starter',
        'slug' => 'starter',
        'price_monthly' => 100000,
        'price_yearly' => 1000000,
        'max_employees' => 25,
        'is_active' => true,
        'sort_order' => 1,
    ]);

    $this->proPlan = SubscriptionPlan::factory()->create([
        'name' => 'Professional',
        'slug' => 'pro',
        'price_monthly' => 250000,
        'price_yearly' => 2500000,
        'max_employees' => 100,
        'is_active' => true,
        'sort_order' => 2,
    ]);
});

describe('Public Subscription Plans API', function () {
    it('can list active plans without authentication', function () {
        $response = $this->getJson('/api/v1/plans');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'price_monthly', 'price_yearly', 'max_employees'],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    it('can view single plan by slug', function () {
        $response = $this->getJson('/api/v1/plans/starter');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Starter',
                    'slug' => 'starter',
                ],
            ]);
    });

    it('returns 404 for non-existent plan', function () {
        $response = $this->getJson('/api/v1/plans/non-existent');

        $response->assertStatus(404);
    });
});

describe('Tenant Billing API', function () {
    it('returns current subscription and employee quota', function () {
        Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'billing_cycle' => Subscription::CYCLE_MONTHLY,
            'started_at' => now(),
            'ends_at' => now()->addDays(20),
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/billing/subscription');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'company',
                    'subscription' => ['id', 'status', 'billing_cycle', 'days_remaining', 'plan'],
                    'quota' => ['current_employees', 'max_employees', 'can_add_employee'],
                    'available_gateways',
                ],
            ]);

        expect($response->json('data.subscription.status'))->toBe('active');
        expect($response->json('data.quota.max_employees'))->toBe(20);
    });

    it('can upgrade plan with manual bank transfer', function () {
        PaymentGatewaySetting::factory()->create([
            'gateway' => 'manual',
            'name' => 'Transfer Bank Manual',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/billing/upgrade', [
            'plan_id' => $this->proPlan->id,
            'billing_cycle' => 'yearly',
            'gateway' => 'manual',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'payment_id',
                    'payment_number',
                    'amount',
                    'gateway',
                    'bank_details',
                    'expired_at',
                ],
            ]);

        expect((float) $response->json('data.amount'))->toBe(2500000.0);
        expect($response->json('data.gateway'))->toBe('manual');

        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'amount' => 2500000,
            'status' => Payment::STATUS_PENDING,
            'gateway' => 'manual',
        ]);
    });

    it('can list invoices for authenticated tenant only', function () {
        $otherCompany = Company::factory()->create();

        // Payment for own company
        $ownPayment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'payment_number' => 'PAY-OWN-001',
        ]);

        // Payment for other company
        Payment::factory()->create([
            'company_id' => $otherCompany->id,
            'payment_number' => 'PAY-OTHER-002',
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/billing/invoices');

        $response->assertOk();
        $items = $response->json('data');
        expect($items)->toHaveCount(1);
        expect($items[0]['payment_number'])->toBe('PAY-OWN-001');
    });

    it('can view own invoice detail and denies other company invoice', function () {
        $ownPayment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'payment_number' => 'PAY-OWN-123',
        ]);

        $otherCompany = Company::factory()->create();
        $otherPayment = Payment::factory()->create([
            'company_id' => $otherCompany->id,
            'payment_number' => 'PAY-OTHER-456',
        ]);

        Sanctum::actingAs($this->admin);

        // View own invoice
        $response = $this->getJson("/api/v1/billing/invoices/{$ownPayment->id}");
        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'payment' => [
                        'payment_number' => 'PAY-OWN-123',
                    ],
                ],
            ]);

        // View other company's invoice should 404
        $otherResponse = $this->getJson("/api/v1/billing/invoices/{$otherPayment->id}");
        $otherResponse->assertStatus(404);
    });

    it('can cancel active subscription', function () {
        $subscription = Subscription::factory()->create([
            'company_id' => $this->company->id,
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        Sanctum::actingAs($this->admin);

        $response = $this->postJson('/api/v1/billing/cancel');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Langganan berhasil dibatalkan.',
            ]);

        expect($subscription->fresh()->status)->toBe(Subscription::STATUS_CANCELLED);
    });
});
