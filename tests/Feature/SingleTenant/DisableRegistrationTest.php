<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Registration disabled in single_mode', function () {

    it('returns 404 when visiting /register in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/register');

        $response->assertNotFound();
    });

    it('returns 404 when POST /register in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->post('/register', [
            'name' => 'Foo',
            'email' => 'foo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'company_name' => 'PT Foo',
        ]);

        $response->assertNotFound();
    });

    it('allows /register in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/register');

        $response->assertStatus(200);
    });
});
