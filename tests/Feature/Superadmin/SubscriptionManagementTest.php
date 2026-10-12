<?php

use App\Models\Company;
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

    $this->plan = SubscriptionPlan::factory()->create([
        'name' => 'Pro Plan',
        'price_monthly' => 199000,
    ]);
});

describe('Subscription Index', function () {
    it('displays subscriptions list page', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin/subscriptions');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.subscriptions.index');
    });

    it('shows existing subscriptions in the list', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create(['name' => 'PT Test Company']);
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->get('/superadmin/subscriptions');

        $response->assertStatus(200);
        $response->assertSee('PT Test Company');
    });

    it('can filter subscriptions by status', function () {
        $this->actingAs($this->superadmin);

        $activeCompany = Company::factory()->create(['name' => 'Active Company']);
        Subscription::factory()->create([
            'company_id' => $activeCompany->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $expiredCompany = Company::factory()->create(['name' => 'Expired Company']);
        Subscription::factory()->create([
            'company_id' => $expiredCompany->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'expired',
        ]);

        $response = $this->get('/superadmin/subscriptions?status=active');

        $response->assertStatus(200);
        $response->assertSee('Active Company');
        $response->assertDontSee('Expired Company');
    });
});

describe('Subscription Show', function () {
    it('displays subscription details', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create(['name' => 'PT Detail Company']);
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $this->plan->id,
        ]);

        $response = $this->get("/superadmin/subscriptions/{$subscription->id}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.subscriptions.show');
        $response->assertSee('PT Detail Company');
    });
});

describe('Subscription Status Update', function () {
    it('can activate a subscription', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'pending',
        ]);

        $response = $this->patch("/superadmin/subscriptions/{$subscription->id}/activate");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subscription->refresh();
        expect($subscription->status)->toBe('active');
    });

    it('can suspend a subscription', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
        ]);

        $response = $this->patch("/superadmin/subscriptions/{$subscription->id}/suspend");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subscription->refresh();
        expect($subscription->status)->toBe('cancelled');
    });

    it('can extend subscription period', function () {
        $this->actingAs($this->superadmin);

        $company = Company::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $this->plan->id,
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        $originalEndDate = $subscription->ends_at;

        $response = $this->patch("/superadmin/subscriptions/{$subscription->id}/extend", [
            'months' => 3,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subscription->refresh();
        expect($subscription->ends_at->gt($originalEndDate))->toBeTrue();
    });
});

describe('Subscription Change Plan', function () {
    it('can change subscription plan', function () {
        $this->actingAs($this->superadmin);

        $newPlan = SubscriptionPlan::factory()->create(['name' => 'Enterprise Plan']);

        $company = Company::factory()->create();
        $subscription = Subscription::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => $this->plan->id,
        ]);

        $response = $this->patch("/superadmin/subscriptions/{$subscription->id}/change-plan", [
            'subscription_plan_id' => $newPlan->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $subscription->refresh();
        expect($subscription->subscription_plan_id)->toBe($newPlan->id);
    });
});
