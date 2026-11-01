<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;

uses(RefreshDatabase::class);

describe('POST /api/v1/auth/register', function () {
    it('successfully registers a new tenant with trial plan', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'PT API Tech',
            'name' => 'API Admin',
            'email' => 'admin@apitech.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email', 'phone', 'roles'],
                    'company' => ['id', 'name', 'slug', 'subscription_plan', 'subscription_ends_at', 'max_employees', 'is_demo_mode'],
                    'payment',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect($response->json('data.token'))->not->toBeEmpty();
        expect($response->json('data.company.name'))->toBe('PT API Tech');
        expect($response->json('data.company.subscription_plan'))->toBe('trial');

        // Check company created in database
        $this->assertDatabaseHas('companies', [
            'name' => 'PT API Tech',
            'email' => 'admin@apitech.com',
        ]);

        // Check user created with roles
        $user = User::where('email', 'admin@apitech.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole('admin'))->toBeTrue()
            ->and($user->hasRole('hr-manager'))->toBeTrue()
            ->and($user->hasRole('employee'))->toBeTrue();

        // Check employee record
        $employee = Employee::where('user_id', $user->id)->first();
        expect($employee)->not->toBeNull()
            ->and($employee->first_name)->toBe('API')
            ->and($employee->last_name)->toBe('Admin');

        // Check headquarters office
        $company = Company::where('email', 'admin@apitech.com')->first();
        $hq = OfficeLocation::where('company_id', $company->id)->where('is_headquarters', true)->first();
        expect($hq)->not->toBeNull()
            ->and($employee->officeLocations->contains($hq))->toBeTrue();
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['company_name', 'name', 'email', 'password']);
    });

    it('validates unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'PT Unique Test',
            'name' => 'Admin Test',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });

    it('validates password confirmation', function () {
        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'PT Password Test',
            'name' => 'Admin Test',
            'email' => 'admin@pwdtest.com',
            'password' => 'password123',
            'password_confirmation' => 'mismatch123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    });

    it('creates pending subscription and payment when paid plan is selected', function () {
        $proPlan = SubscriptionPlan::factory()->create([
            'slug' => 'pro',
            'price_monthly' => 150000,
            'price_yearly' => 1500000,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'PT Pro Paid',
            'name' => 'Admin Pro',
            'email' => 'admin@propaid.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan' => 'pro',
            'billing_cycle' => 'yearly',
            'gateway' => 'manual',
        ]);

        $response->assertCreated();

        $company = Company::where('email', 'admin@propaid.com')->first();
        expect($company->subscription_plan)->toBe('pro');

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'subscription_plan_id' => $proPlan->id,
            'status' => 'pending',
            'billing_cycle' => 'yearly',
        ]);

        $this->assertDatabaseHas('payments', [
            'company_id' => $company->id,
            'gateway' => 'manual',
            'status' => Payment::STATUS_PENDING,
            'amount' => 1500000,
        ]);
    });

    it('fails when single tenant mode is active', function () {
        Config::set('tenant.single_mode', true);

        $response = $this->postJson('/api/v1/auth/register', [
            'company_name' => 'PT Single Test',
            'name' => 'Admin Single',
            'email' => 'admin@singletest.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(404);
    });
});
