<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Public Tenant API', function () {
    it('can fetch active subscription plans via public endpoint', function () {
        $response = $this->getJson('/api/v1/public/plans');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['name', 'slug', 'description'],
            ],
        ]);
        expect($response->json('success'))->toBeTrue();
    });

    it('can fetch payment gateways via public endpoint', function () {
        $response = $this->getJson('/api/v1/public/payment-gateways');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['gateway', 'name'],
            ],
        ]);
        expect($response->json('success'))->toBeTrue();
    });

    it('can register a new tenant company via public REST API', function () {
        $response = $this->postJson('/api/v1/public/register-tenant', [
            'company_name' => 'PT API Tech Indonesia',
            'name' => 'John Doe',
            'email' => 'john.doe@apitech.id',
            'phone' => '081299988877',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'plan' => 'trial',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => ['id', 'name', 'email', 'roles'],
                'company' => ['id', 'name', 'subscription_plan'],
                'is_paid_plan',
            ],
        ]);

        // Assert database records
        $this->assertDatabaseHas('companies', [
            'name' => 'PT API Tech Indonesia',
            'email' => 'john.doe@apitech.id',
        ]);

        $user = User::where('email', 'john.doe@apitech.id')->first();
        expect($user)->not->toBeNull()
            ->and($user->company_id)->not->toBeNull()
            ->and($user->hasRole('admin'))->toBeTrue();
    });

    it('validates registration input fields', function () {
        $response = $this->postJson('/api/v1/public/register-tenant', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'company_name',
            'name',
            'email',
            'password',
        ]);
    });
});
