<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create([
        'name' => 'PT Test Company',
    ]);
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Admin User',
        'email' => 'admin@test.com',
    ]);
    $this->admin->assignRole('admin');

    $this->hrManager = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'HR Manager',
        'email' => 'hr@test.com',
    ]);
    $this->hrManager->assignRole('hr-manager');

    $this->employee = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Employee User',
        'email' => 'employee@test.com',
    ]);
    $this->employee->assignRole('employee');
});

describe('User Management Index', function () {
    it('displays user list page for admin', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/users');

        $response->assertStatus(200);
        $response->assertViewIs('settings.users.index');
        $response->assertSee('Admin User');
        $response->assertSee('HR Manager');
        $response->assertSee('Employee User');
    });

    it('only shows users from same company', function () {
        $this->actingAs($this->admin);

        // Create user from another company
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company User',
        ]);

        $response = $this->get('/settings/users');

        $response->assertStatus(200);
        $response->assertSee('Admin User');
        $response->assertDontSee('Other Company User');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/settings/users');

        $response->assertRedirect('/login');
    });

    it('can filter users by role', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/users?role=admin');

        $response->assertStatus(200);
        $response->assertSee('Admin User');
    });

    it('can search users by name or email', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/users?search=HR');

        $response->assertStatus(200);
        $response->assertSee('HR Manager');
    });
});

describe('User Management Create', function () {
    it('displays create user form for admin', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/users/create');

        $response->assertStatus(200);
        $response->assertViewIs('settings.users.create');
    });

    it('can create new user with role', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/users', [
            'name' => 'New User',
            'email' => 'newuser@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '08123456789',
            'role' => 'hr-manager',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'company_id' => $this->company->id,
            'name' => 'New User',
            'email' => 'newuser@test.com',
        ]);

        $newUser = User::where('email', 'newuser@test.com')->first();
        expect($newUser->hasRole('hr-manager'))->toBeTrue();
    });

    it('validates required fields on create', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/users', [
            'name' => '',
            'email' => '',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    });

    it('validates email uniqueness', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/users', [
            'name' => 'Duplicate User',
            'email' => 'admin@test.com', // Already exists
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates password confirmation', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/users', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates role exists', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/users', [
            'name' => 'New User',
            'email' => 'new@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'non-existent-role',
        ]);

        $response->assertSessionHasErrors(['role']);
    });
});

describe('User Management Edit', function () {
    it('displays edit user form', function () {
        $this->actingAs($this->admin);

        $response = $this->get("/settings/users/{$this->hrManager->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('settings.users.edit');
        $response->assertSee('HR Manager');
    });

    it('cannot edit user from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->get("/settings/users/{$otherUser->id}/edit");

        $response->assertStatus(404);
    });

    it('can update user information', function () {
        $this->actingAs($this->admin);

        $response = $this->put("/settings/users/{$this->hrManager->id}", [
            'name' => 'Updated HR Manager',
            'email' => 'updatedhr@test.com',
            'phone' => '08987654321',
            'role' => 'hr-manager',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        $this->hrManager->refresh();
        expect($this->hrManager->name)->toBe('Updated HR Manager');
        expect($this->hrManager->email)->toBe('updatedhr@test.com');
    });

    it('can update user password', function () {
        $this->actingAs($this->admin);

        $response = $this->put("/settings/users/{$this->hrManager->id}", [
            'name' => 'HR Manager',
            'email' => 'hr@test.com',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'role' => 'hr-manager',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->hrManager->refresh();
        expect(Hash::check('newpassword123', $this->hrManager->password))->toBeTrue();
    });

    it('does not change password if not provided', function () {
        $this->actingAs($this->admin);

        $oldPassword = $this->hrManager->password;

        $response = $this->put("/settings/users/{$this->hrManager->id}", [
            'name' => 'HR Manager Updated',
            'email' => 'hr@test.com',
            'role' => 'hr-manager',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->hrManager->refresh();
        expect($this->hrManager->password)->toBe($oldPassword);
    });

    it('can change user role', function () {
        $this->actingAs($this->admin);

        $response = $this->put("/settings/users/{$this->hrManager->id}", [
            'name' => 'HR Manager',
            'email' => 'hr@test.com',
            'role' => 'employee',
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->hrManager->refresh();
        expect($this->hrManager->hasRole('employee'))->toBeTrue();
        expect($this->hrManager->hasRole('hr-manager'))->toBeFalse();
    });

    it('allows keeping same email on update', function () {
        $this->actingAs($this->admin);

        $response = $this->put("/settings/users/{$this->hrManager->id}", [
            'name' => 'HR Manager Updated',
            'email' => 'hr@test.com', // Same email
            'role' => 'hr-manager',
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionDoesntHaveErrors();
    });
});

describe('User Management Delete', function () {
    it('can delete user', function () {
        $this->actingAs($this->admin);

        $response = $this->delete("/settings/users/{$this->employee->id}");

        $response->assertRedirect(route('settings.users.index'));
        $response->assertSessionHas('success');

        // Soft delete
        $this->assertSoftDeleted('users', ['id' => $this->employee->id]);
    });

    it('cannot delete self', function () {
        $this->actingAs($this->admin);

        $response = $this->delete("/settings/users/{$this->admin->id}");

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id, 'deleted_at' => null]);
    });

    it('cannot delete user from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->delete("/settings/users/{$otherUser->id}");

        $response->assertStatus(404);
    });
});

describe('User Management Toggle Status', function () {
    it('can toggle user active status', function () {
        $this->actingAs($this->admin);

        // Initially active
        expect($this->hrManager->is_active)->toBeTrue();

        $response = $this->patch("/settings/users/{$this->hrManager->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->hrManager->refresh();
        expect($this->hrManager->is_active)->toBeFalse();

        // Toggle back
        $this->patch("/settings/users/{$this->hrManager->id}/toggle-status");

        $this->hrManager->refresh();
        expect($this->hrManager->is_active)->toBeTrue();
    });

    it('cannot toggle own status', function () {
        $this->actingAs($this->admin);

        $response = $this->patch("/settings/users/{$this->admin->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});
