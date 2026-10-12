<?php

use App\Models\PaymentGatewaySetting;
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

describe('Payment Gateway Index', function () {
    it('displays payment gateways list page', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin/payment-gateways');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.payment-gateways.index');
    });

    it('shows existing gateways in the list', function () {
        $this->actingAs($this->superadmin);

        $gateway = PaymentGatewaySetting::factory()->midtrans()->create();

        $response = $this->get('/superadmin/payment-gateways');

        $response->assertStatus(200);
        $response->assertSee('Midtrans');
    });
});

describe('Payment Gateway Create', function () {
    it('displays gateway create form', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin/payment-gateways/create');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.payment-gateways.create');
    });

    it('can create a new gateway', function () {
        $this->actingAs($this->superadmin);

        $response = $this->post('/superadmin/payment-gateways', [
            'gateway' => 'midtrans',
            'name' => 'Midtrans',
            'environment' => 'sandbox',
            'credentials' => [
                'server_key' => 'SB-Mid-server-test123',
                'client_key' => 'SB-Mid-client-test123',
                'merchant_id' => 'G123456',
            ],
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $response->assertRedirect('/superadmin/payment-gateways');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_gateway_settings', [
            'gateway' => 'midtrans',
            'name' => 'Midtrans',
            'environment' => 'sandbox',
        ]);
    });

    it('validates required fields on create', function () {
        $this->actingAs($this->superadmin);

        $response = $this->post('/superadmin/payment-gateways', []);

        $response->assertSessionHasErrors(['gateway', 'name']);
    });

    it('validates unique gateway', function () {
        $this->actingAs($this->superadmin);

        PaymentGatewaySetting::factory()->midtrans()->create();

        $response = $this->post('/superadmin/payment-gateways', [
            'gateway' => 'midtrans',
            'name' => 'Midtrans 2',
            'environment' => 'sandbox',
        ]);

        $response->assertSessionHasErrors('gateway');
    });
});

describe('Payment Gateway Edit', function () {
    it('displays gateway edit form', function () {
        $this->actingAs($this->superadmin);

        $gateway = PaymentGatewaySetting::factory()->midtrans()->create();

        $response = $this->get("/superadmin/payment-gateways/{$gateway->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.payment-gateways.edit');
    });

    it('can update a gateway', function () {
        $this->actingAs($this->superadmin);

        $gateway = PaymentGatewaySetting::factory()->midtrans()->create([
            'environment' => 'sandbox',
        ]);

        $response = $this->put("/superadmin/payment-gateways/{$gateway->id}", [
            'gateway' => $gateway->gateway,
            'name' => 'Midtrans Updated',
            'environment' => 'production',
            'credentials' => [
                'server_key' => 'Mid-server-prod123',
                'client_key' => 'Mid-client-prod123',
                'merchant_id' => 'G654321',
            ],
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $response->assertRedirect('/superadmin/payment-gateways');
        $response->assertSessionHas('success');

        $gateway->refresh();
        expect($gateway->environment)->toBe('production');
        expect($gateway->name)->toBe('Midtrans Updated');
    });
});

describe('Payment Gateway Toggle Status', function () {
    it('can toggle gateway active status', function () {
        $this->actingAs($this->superadmin);

        $gateway = PaymentGatewaySetting::factory()->create(['is_active' => false]);

        $response = $this->patch("/superadmin/payment-gateways/{$gateway->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $gateway->refresh();
        expect($gateway->is_active)->toBeTrue();
    });
});

describe('Payment Gateway Delete', function () {
    it('can delete a gateway', function () {
        $this->actingAs($this->superadmin);

        $gateway = PaymentGatewaySetting::factory()->create();

        $response = $this->delete("/superadmin/payment-gateways/{$gateway->id}");

        $response->assertRedirect('/superadmin/payment-gateways');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('payment_gateway_settings', [
            'id' => $gateway->id,
        ]);
    });
});

describe('Payment Gateway Credentials Security', function () {
    it('encrypts credentials when stored', function () {
        $this->actingAs($this->superadmin);

        $gateway = PaymentGatewaySetting::factory()->midtrans()->create([
            'credentials' => [
                'server_key' => 'secret-key-123',
            ],
        ]);

        // Fetch from DB directly to check encryption
        $rawCredentials = \DB::table('payment_gateway_settings')
            ->where('id', $gateway->id)
            ->value('credentials');

        // Should be encrypted (not plain JSON)
        expect($rawCredentials)->not->toContain('secret-key-123');

        // But model should decrypt automatically
        $gateway->refresh();
        expect($gateway->credentials['server_key'])->toBe('secret-key-123');
    });
});
