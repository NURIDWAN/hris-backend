<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
});

describe('Forgot Password', function () {
    it('displays forgot password page', function () {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertViewIs('auth.forgot-password');
    });

    it('sends password reset link to valid email', function () {
        Notification::fake();

        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'user@example.com',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'user@example.com',
        ]);

        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    });

    it('does not send reset link to invalid email', function () {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);

        Notification::assertNothingSent();
    });

    it('validates email is required', function () {
        $response = $this->post(route('password.email'), []);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates email format', function () {
        $response = $this->post(route('password.email'), [
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    });
});

describe('Reset Password', function () {
    it('displays reset password page with valid token', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $token = Password::createToken($user);

        $response = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $response->assertOk();
        $response->assertViewIs('auth.reset-password');
    });

    it('resets password with valid token', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('old-password'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        // Verify password was changed
        $user->refresh();
        expect(Hash::check('new-password123', $user->password))->toBeTrue();
    });

    it('fails with invalid token', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates password confirmation matches', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates password minimum length', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    });
});
