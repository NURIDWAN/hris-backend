<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Login page in single-tenant mode', function () {

    it('hides register CTA in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Daftar gratis');
        $response->assertDontSee('Belum punya akun?');
    });

    it('shows register CTA in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Daftar gratis');
    });

    it('hides marketing stats in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('500+');
        $response->assertDontSee('50K+');
        $response->assertDontSee('99%');
    });

    it('shows marketing stats in multi-tenant mode', function () {
        config(['tenant.single_mode' => false]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('500+');
    });

    it('hides SaaS platform badge in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Platform HR #1 di Indonesia');
    });

    it('hides generic saas features list in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertDontSee('Mobile app untuk karyawan');
        $response->assertDontSee('Dashboard real-time & analytics');
    });

    it('renders brand name in single_mode', function () {
        config([
            'tenant.single_mode' => true,
            'tenant.brand.name' => 'HR Gemilang',
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('HR Gemilang');
    });

    it('renders login form inputs in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
    });

    it('shows forgot password link in single_mode', function () {
        config(['tenant.single_mode' => true]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Lupa password?');
    });
});
