<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    setPermissionsTeamId(null);
    Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
});

describe('Brand assets', function () {

    it('ships a dummy logo SVG', function () {
        expect(public_path('images/brand/logo.svg'))->toBeFile();
    });

    it('ships a dummy logo mark SVG', function () {
        expect(public_path('images/brand/logo-mark.svg'))->toBeFile();
    });

    it('ships a dummy favicon SVG', function () {
        expect(public_path('images/brand/favicon.svg'))->toBeFile();
    });

    it('resolves brand_asset() to a valid URL', function () {
        config(['tenant.brand.logo_path' => 'images/brand/logo.svg']);

        expect(brand_asset('logo_path'))
            ->toBeString()
            ->toContain('images/brand/logo.svg');
    });

    it('exposes brand primary color via helper', function () {
        config(['tenant.brand.primary_color' => '#1d4ed8']);

        expect(brand_color('primary'))->toBe('#1d4ed8');
    });

    it('renders favicon link on login page', function () {
        config(['tenant.brand.favicon_path' => 'images/brand/favicon.svg']);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('images/brand/favicon.svg', false);
    });
});
