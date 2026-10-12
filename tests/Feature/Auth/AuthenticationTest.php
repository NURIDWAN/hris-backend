<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Authentication', function () {

    it('displays login page', function () {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertViewIs('auth.login');
    });

    it('can login with valid credentials', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    });

    it('cannot login with invalid password', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('cannot login with non-existent email', function () {
        $response = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('cannot login if user is inactive', function () {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('cannot login if company is inactive', function () {
        $company = Company::factory()->create(['is_active' => false]);
        $user = User::factory()->create([
            'email' => 'user@inactivecompany.com',
            'password' => 'password123',
            'company_id' => $company->id,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'user@inactivecompany.com',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    });

    it('super admin can login without company', function () {
        $superAdmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'password' => 'password123',
            'company_id' => null,
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'email' => 'superadmin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    });

    it('remembers user when remember checkbox is checked', function () {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
            'remember' => true,
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();

        // Check that remember token was set
        $user->refresh();
        expect($user->remember_token)->not->toBeNull();
    });

    it('can logout', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/');
        $this->assertGuest();
    });

    it('validates email and password are required', function () {
        $response = $this->post('/login', []);

        $response->assertSessionHasErrors(['email', 'password']);
    });

    it('validates email format', function () {
        $response = $this->post('/login', [
            'email' => 'not-an-email',
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('redirects to dashboard if already authenticated', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/dashboard');
    });

    it('redirects to dashboard after registration when authenticated', function () {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/register');

        $response->assertRedirect('/dashboard');
    });

});
