<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'is_superadmin' => true,
        'company_id' => null,
        'is_active' => true,
    ]);

    $this->regularUser = User::factory()->create([
        'is_superadmin' => false,
        'is_active' => true,
    ]);
});

describe('System Health Dashboard', function () {
    it('can access system health page as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertViewIs('superadmin.system.health');
    });

    it('displays PHP version info', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertSee(PHP_VERSION);
    });

    it('displays Laravel version info', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertSee(app()->version());
    });

    it('displays disk usage information', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertViewHas('metrics');
    });

    it('displays database information', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertSee('Database');
    });

    it('displays cache status', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertSee('Cache');
    });

    it('displays queue status', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/health');

        $response->assertOk()
            ->assertSee('Queue');
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/health');

        $response->assertRedirect('/superadmin/login');
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->get('/superadmin/system/health');

        $response->assertRedirect('/login');
    });
});
