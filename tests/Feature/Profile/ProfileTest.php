<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'hr-manager']);
    Role::create(['name' => 'employee']);

    $this->company = Company::factory()->create();

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
    $this->user->assignRole('admin');
});

describe('My Profile Page', function () {
    it('displays my profile page', function () {
        $this->actingAs($this->user);

        $response = $this->get('/profile');

        $response->assertStatus(200);
        $response->assertViewIs('profile.index');
        $response->assertSee('Profil Saya');
        $response->assertSee($this->user->name);
        $response->assertSee($this->user->email);
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/profile');

        $response->assertRedirect('/login');
    });
});

describe('Update Profile Information', function () {
    it('can update name', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => 'Jane Doe',
            'email' => $this->user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->name)->toBe('Jane Doe');
    });

    it('can update email', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => $this->user->name,
            'email' => 'newemail@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->email)->toBe('newemail@example.com');
    });

    it('validates required fields', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => '',
            'email' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email']);
    });

    it('validates email format', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates email uniqueness', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'existing@example.com',
        ]);

        $response = $this->put('/profile', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('allows same email for current user', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });
});

describe('Update Password', function () {
    it('can update password', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
    });

    it('validates current password', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['current_password']);
    });

    it('validates password confirmation', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates minimum password length', function () {
        $this->actingAs($this->user);

        $response = $this->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    });
});

describe('Upload Avatar', function () {
    it('can upload avatar', function () {
        Storage::fake('public');
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->user->refresh();
        expect($this->user->avatar)->not->toBeNull();
        Storage::disk('public')->assertExists($this->user->avatar);
    });

    it('validates avatar file type', function () {
        Storage::fake('public');
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => UploadedFile::fake()->create('document.pdf', 100),
        ]);

        $response->assertSessionHasErrors(['avatar']);
    });

    it('validates avatar file size', function () {
        Storage::fake('public');
        $this->actingAs($this->user);

        $response = $this->put('/profile', [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'avatar' => UploadedFile::fake()->image('large.jpg')->size(3000), // 3MB
        ]);

        $response->assertSessionHasErrors(['avatar']);
    });
});
