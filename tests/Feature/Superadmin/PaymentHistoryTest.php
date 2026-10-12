<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'is_superadmin' => true,
        'company_id' => null,
        'is_active' => true,
    ]);
});

describe('Payment Index', function () {
    it('displays payments list page', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin/payments');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.payments.index');
    });

    it('shows existing payments in the list', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create(['name' => 'PT Test Company']);
        $payment = Payment::factory()->success()->create([
            'company_id' => $company->id,
            'payment_number' => 'PAY202602120001',
        ]);

        $response = $this->get('/superadmin/payments');

        $response->assertStatus(200);
        $response->assertSee('PT Test Company');
        $response->assertSee('PAY202602120001');
    });

    it('can filter payments by status', function () {
        $this->actingAs($this->superadmin);

        $successCompany = Company::factory()->create(['name' => 'Success Company']);
        Payment::factory()->success()->create([
            'company_id' => $successCompany->id,
        ]);

        $pendingCompany = Company::factory()->create(['name' => 'Pending Company']);
        Payment::factory()->pending()->create([
            'company_id' => $pendingCompany->id,
        ]);

        $response = $this->get('/superadmin/payments?status=success');

        $response->assertStatus(200);
        $response->assertSee('Success Company');
        $response->assertDontSee('Pending Company');
    });

    it('can filter payments by date range', function () {
        $this->actingAs($this->superadmin);

        $recentCompany = Company::factory()->create(['name' => 'Recent Company']);
        Payment::factory()->success()->create([
            'company_id' => $recentCompany->id,
            'created_at' => now(),
        ]);

        $oldCompany = Company::factory()->create(['name' => 'Old Company']);
        Payment::factory()->success()->create([
            'company_id' => $oldCompany->id,
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->get('/superadmin/payments?date_from='.now()->subWeek()->format('Y-m-d').'&date_to='.now()->format('Y-m-d'));

        $response->assertStatus(200);
        $response->assertSee('Recent Company');
        $response->assertDontSee('Old Company');
    });

    it('can filter payments by gateway', function () {
        $this->actingAs($this->superadmin);

        $midtransCompany = Company::factory()->create(['name' => 'Midtrans Company']);
        Payment::factory()->create([
            'company_id' => $midtransCompany->id,
            'gateway' => 'midtrans',
        ]);

        $manualCompany = Company::factory()->create(['name' => 'Manual Company']);
        Payment::factory()->manual()->create([
            'company_id' => $manualCompany->id,
        ]);

        $response = $this->get('/superadmin/payments?gateway=midtrans');

        $response->assertStatus(200);
        $response->assertSee('Midtrans Company');
        $response->assertDontSee('Manual Company');
    });
});

describe('Payment Show', function () {
    it('displays payment details', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create(['name' => 'PT Detail Company']);
        $payment = Payment::factory()->success()->create([
            'company_id' => $company->id,
            'amount' => 199000,
        ]);

        $response = $this->get("/superadmin/payments/{$payment->id}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.payments.show');
        $response->assertSee('PT Detail Company');
    });

    it('shows payment with subscription details', function () {
        $this->actingAs($this->superadmin);

        $plan = SubscriptionPlan::factory()->create(['name' => 'Pro Plan']);
        $company = Company::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $plan->id,
        ]);

        $payment = Payment::factory()->success()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
        ]);

        $response = $this->get("/superadmin/payments/{$payment->id}");

        $response->assertStatus(200);
        $response->assertSee('Pro Plan');
    });
});

describe('Manual Payment Confirmation', function () {
    it('can confirm a pending manual payment', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $payment = Payment::factory()->manual()->pending()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->patch("/superadmin/payments/{$payment->id}/confirm");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        expect($payment->status)->toBe('success');
        expect($payment->paid_at)->not->toBeNull();
    });

    it('cannot confirm non-pending payment', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $payment = Payment::factory()->success()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->patch("/superadmin/payments/{$payment->id}/confirm");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Payment Refund', function () {
    it('can refund a successful payment', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $payment = Payment::factory()->success()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->patch("/superadmin/payments/{$payment->id}/refund", [
            'reason' => 'Customer requested refund',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $payment->refresh();
        expect($payment->status)->toBe('refunded');
    });

    it('cannot refund non-success payment', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $payment = Payment::factory()->pending()->create([
            'company_id' => $company->id,
        ]);

        $response = $this->patch("/superadmin/payments/{$payment->id}/refund", [
            'reason' => 'Test refund',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Payment Statistics', function () {
    it('displays payment statistics on dashboard', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();

        Payment::factory()->success()->count(5)->create([
            'company_id' => $company->id,
            'amount' => 100000,
        ]);

        Payment::factory()->pending()->count(3)->create([
            'company_id' => $company->id,
        ]);

        $response = $this->get('/superadmin/payments');

        $response->assertStatus(200);
        // Statistics should be visible in the view
    });
});
