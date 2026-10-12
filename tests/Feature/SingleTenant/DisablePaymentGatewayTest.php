<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Payment gateway webhooks disabled in single_mode', function () {

    it('returns 404 for POST /api/webhooks/xendit in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->postJson('/api/webhooks/xendit', []);

        $response->assertNotFound();
    });

    it('returns 404 for POST /api/webhooks/midtrans in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->postJson('/api/webhooks/midtrans', []);

        $response->assertNotFound();
    });

    it('allows webhook routes in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        // We only care the route is registered (not 404). Actual webhook
        // processing is exercised elsewhere.
        $response = $this->postJson('/api/webhooks/xendit', []);

        expect($response->status())->not->toBe(404);
    });
});
