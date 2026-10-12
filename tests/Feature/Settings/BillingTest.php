<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create subscription plans
    $this->freePlan = SubscriptionPlan::factory()->free()->create(['sort_order' => 1]);
    $this->starterPlan = SubscriptionPlan::factory()->starter()->create(['sort_order' => 2]);
    $this->proPlan = SubscriptionPlan::factory()->professional()->create(['sort_order' => 3]);

    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');

    // Create active subscription
    $this->subscription = Subscription::factory()->create([
        'company_id' => $this->company->id,
        'subscription_plan_id' => $this->freePlan->id,
        'status' => Subscription::STATUS_ACTIVE,
    ]);
});

describe('Billing Index', function () {
    it('displays billing page for admin', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/billing');

        $response->assertStatus(200);
        $response->assertViewIs('settings.billing.index');
    });

    it('shows current subscription info', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/billing');

        $response->assertStatus(200);
        $response->assertSee('Free');
    });

    it('shows available plans', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/billing');

        $response->assertStatus(200);
        $response->assertSee('Starter');
        $response->assertSee('Professional');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/settings/billing');

        $response->assertRedirect('/login');
    });
});

describe('Invoice History', function () {
    it('shows invoice history', function () {
        $this->actingAs($this->admin);

        $invoice = Invoice::factory()->paid()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $this->subscription->id,
        ]);

        $response = $this->get('/settings/billing');

        $response->assertStatus(200);
        $response->assertSee($invoice->invoice_number);
    });

    it('can view single invoice', function () {
        $this->actingAs($this->admin);

        $payment = Payment::factory()->create([
            'company_id' => $this->company->id,
            'subscription_id' => $this->subscription->id,
        ]);

        $response = $this->get("/settings/billing/invoices/{$payment->id}");

        $response->assertStatus(200);
        $response->assertViewIs('settings.billing.invoice');
        $response->assertSee($payment->payment_number);
    });

    it('cannot view invoice from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherSubscription = Subscription::factory()->create([
            'company_id' => $otherCompany->id,
            'subscription_plan_id' => $this->freePlan->id,
        ]);
        $payment = Payment::factory()->create([
            'company_id' => $otherCompany->id,
            'subscription_id' => $otherSubscription->id,
        ]);

        $response = $this->get("/settings/billing/invoices/{$payment->id}");

        $response->assertStatus(404);
    });
});

describe('Plan Upgrade', function () {
    it('displays plan upgrade page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/billing/upgrade');

        $response->assertStatus(200);
        $response->assertViewIs('settings.billing.upgrade');
        $response->assertSee('Starter');
        $response->assertSee('Professional');
    });

    it('can select a plan to upgrade', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/billing/upgrade', [
            'plan_id' => $this->starterPlan->id,
            'billing_cycle' => 'monthly',
            'gateway' => 'manual',
        ]);

        $response->assertRedirect();

        // Verify payment was created
        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'gateway' => 'manual',
            'status' => Payment::STATUS_PENDING,
        ]);
    });

    it('creates payment when upgrading to paid plan', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/billing/upgrade', [
            'plan_id' => $this->starterPlan->id,
            'billing_cycle' => 'monthly',
            'gateway' => 'manual',
        ]);

        $response->assertRedirect();

        // Check payment was created
        $this->assertDatabaseHas('payments', [
            'company_id' => $this->company->id,
            'status' => Payment::STATUS_PENDING,
        ]);
    });

    it('validates plan exists', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/billing/upgrade', [
            'plan_id' => 99999,
            'billing_cycle' => 'monthly',
        ]);

        $response->assertSessionHasErrors(['plan_id']);
    });

    it('validates billing cycle', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/billing/upgrade', [
            'plan_id' => $this->starterPlan->id,
            'billing_cycle' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['billing_cycle']);
    });
});

describe('Cancel Subscription', function () {
    it('can cancel subscription', function () {
        $this->actingAs($this->admin);

        // Update the existing subscription to active with starter plan
        $this->subscription->update([
            'subscription_plan_id' => $this->starterPlan->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);

        $response = $this->post('/settings/billing/cancel');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->subscription->refresh();
        expect($this->subscription->status)->toBe(Subscription::STATUS_CANCELLED);
        expect($this->subscription->cancelled_at)->not->toBeNull();
    });
});

describe('Subscription Model', function () {
    it('correctly identifies active subscription', function () {
        $subscription = Subscription::factory()->active()->create([
            'subscription_plan_id' => $this->freePlan->id,
        ]);

        expect($subscription->isActive())->toBeTrue();
    });

    it('correctly identifies expired subscription', function () {
        $subscription = Subscription::factory()->expired()->create([
            'subscription_plan_id' => $this->freePlan->id,
        ]);

        expect($subscription->isExpired())->toBeTrue();
    });

    it('correctly identifies trial period', function () {
        $subscription = Subscription::factory()->onTrial()->create([
            'subscription_plan_id' => $this->freePlan->id,
        ]);

        expect($subscription->onTrial())->toBeTrue();
    });
});

describe('Invoice Model', function () {
    it('generates unique invoice number', function () {
        $invoice1 = Invoice::factory()->create();
        $invoice2 = Invoice::factory()->create();

        expect($invoice1->invoice_number)->not->toBe($invoice2->invoice_number);
    });

    it('correctly identifies overdue invoice', function () {
        $invoice = Invoice::factory()->overdue()->create();

        expect($invoice->isOverdue())->toBeTrue();
    });

    it('correctly identifies paid invoice', function () {
        $invoice = Invoice::factory()->paid()->create();

        expect($invoice->isPaid())->toBeTrue();
    });
});
