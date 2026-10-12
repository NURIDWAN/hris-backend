<?php

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

describe('Plan Index', function () {
    it('displays plans list page', function () {
        $this->actingAs($this->superadmin);

        SubscriptionPlan::factory()->count(3)->create();

        $response = $this->get('/superadmin/plans');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.plans.index');
    });

    it('shows existing plans in the list', function () {
        $this->actingAs($this->superadmin);

        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Pro Plan',
            'price_monthly' => 199000,
        ]);

        $response = $this->get('/superadmin/plans');

        $response->assertStatus(200);
        $response->assertSee('Pro Plan');
    });
});

describe('Plan Create', function () {
    it('displays plan create form', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin/plans/create');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.plans.create');
    });

    it('can create a new plan', function () {
        $this->actingAs($this->superadmin);

        $response = $this->post('/superadmin/plans', [
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'description' => 'Best for large companies',
            'price_monthly' => 499000,
            'price_yearly' => 4990000,
            'max_employees' => 100,
            'max_users' => 20,
            'features' => ['payroll', 'attendance', 'leave', 'reports'],
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $response->assertRedirect('/superadmin/plans');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'name' => 'Enterprise Plan',
            'slug' => 'enterprise',
            'price_monthly' => 499000,
        ]);
    });

    it('validates required fields on create', function () {
        $this->actingAs($this->superadmin);

        $response = $this->post('/superadmin/plans', []);

        $response->assertSessionHasErrors(['name', 'slug', 'price_monthly', 'price_yearly']);
    });

    it('validates unique slug', function () {
        $this->actingAs($this->superadmin);

        SubscriptionPlan::factory()->create(['slug' => 'existing-slug']);

        $response = $this->post('/superadmin/plans', [
            'name' => 'New Plan',
            'slug' => 'existing-slug',
            'price_monthly' => 99000,
            'price_yearly' => 990000,
            'max_employees' => 10,
            'max_users' => 3,
        ]);

        $response->assertSessionHasErrors('slug');
    });
});

describe('Plan Edit', function () {
    it('displays plan edit form', function () {
        $this->actingAs($this->superadmin);

        $plan = SubscriptionPlan::factory()->create();

        $response = $this->get("/superadmin/plans/{$plan->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.plans.edit');
        $response->assertSee($plan->name);
    });

    it('can update a plan', function () {
        $this->actingAs($this->superadmin);

        $plan = SubscriptionPlan::factory()->create([
            'name' => 'Old Name',
            'price_monthly' => 99000,
        ]);

        $response = $this->put("/superadmin/plans/{$plan->id}", [
            'name' => 'New Name',
            'slug' => $plan->slug,
            'description' => 'Updated description',
            'price_monthly' => 149000,
            'price_yearly' => 1490000,
            'max_employees' => 25,
            'max_users' => 5,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertRedirect('/superadmin/plans');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('subscription_plans', [
            'id' => $plan->id,
            'name' => 'New Name',
            'price_monthly' => 149000,
        ]);
    });
});

describe('Plan Delete', function () {
    it('can delete a plan without subscriptions', function () {
        $this->actingAs($this->superadmin);

        $plan = SubscriptionPlan::factory()->create();

        $response = $this->delete("/superadmin/plans/{$plan->id}");

        $response->assertRedirect('/superadmin/plans');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('subscription_plans', [
            'id' => $plan->id,
        ]);
    });
});

describe('Plan Toggle Status', function () {
    it('can toggle plan active status', function () {
        $this->actingAs($this->superadmin);

        $plan = SubscriptionPlan::factory()->create(['is_active' => true]);

        $response = $this->patch("/superadmin/plans/{$plan->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $plan->refresh();
        expect($plan->is_active)->toBeFalse();
    });
});
