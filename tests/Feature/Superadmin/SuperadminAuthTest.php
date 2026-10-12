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

describe('Superadmin Login', function () {
    it('can access superadmin login page', function () {
        $response = $this->get('/superadmin/login');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.auth.login');
    });

    it('can login with valid superadmin credentials', function () {
        $response = $this->post('/superadmin/login', [
            'email' => $this->superadmin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/superadmin');
        $this->assertAuthenticatedAs($this->superadmin);
    });

    it('cannot login with invalid credentials', function () {
        $response = $this->post('/superadmin/login', [
            'email' => $this->superadmin->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('cannot login as superadmin with regular user account', function () {
        $response = $this->post('/superadmin/login', [
            'email' => $this->regularUser->email,
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('can logout from superadmin', function () {
        $this->actingAs($this->superadmin);

        $response = $this->post('/superadmin/logout');

        $response->assertRedirect('/superadmin/login');
        $this->assertGuest();
    });
});

describe('Superadmin Dashboard Access', function () {
    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/superadmin');

        // Unauthenticated users get redirected to main login first by auth middleware
        $response->assertRedirect('/login');
    });

    it('redirects regular user to superadmin login', function () {
        $this->actingAs($this->regularUser);

        $response = $this->get('/superadmin');

        $response->assertRedirect('/superadmin/login');
    });

    it('allows superadmin to access dashboard', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
    });

    it('displays dashboard statistics', function () {
        $this->actingAs($this->superadmin);

        $response = $this->get('/superadmin');

        $response->assertStatus(200);
        $response->assertSee('Dashboard');
    });
});

describe('Superadmin User Model', function () {
    it('identifies superadmin correctly', function () {
        expect($this->superadmin->isSuperAdmin())->toBeTrue();
        expect($this->regularUser->isSuperAdmin())->toBeFalse();
    });

    it('superadmin has no company', function () {
        expect($this->superadmin->company_id)->toBeNull();
    });
});
