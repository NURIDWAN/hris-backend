<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Superadmin panel disabled in single_mode', function () {

    it('returns 404 for /superadmin/login in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/superadmin/login');

        $response->assertNotFound();
    });

    it('returns 404 for POST /superadmin/login in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->post('/superadmin/login', [
            'email' => 'super@example.com',
            'password' => 'password',
        ]);

        $response->assertNotFound();
    });

    it('returns 404 for /superadmin dashboard in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/superadmin');

        $response->assertNotFound();
    });

    it('returns 404 for /superadmin/companies in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/superadmin/companies');

        $response->assertNotFound();
    });

    it('returns 404 for /superadmin/plans in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/superadmin/plans');

        $response->assertNotFound();
    });

    it('allows /superadmin/login in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/superadmin/login');

        $response->assertStatus(200);
    });
});
