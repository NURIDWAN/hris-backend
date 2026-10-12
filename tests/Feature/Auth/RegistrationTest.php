<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Roles are created by the registration controller with proper team context
});

describe('Registration', function () {

    it('displays registration page', function () {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertViewIs('auth.register');
    });

    it('can register a new company with admin user', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Baru Jaya',
            'name' => 'Admin Baru',
            'email' => 'admin@barujaya.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        // Check company created
        $this->assertDatabaseHas('companies', [
            'name' => 'PT Baru Jaya',
            'email' => 'admin@barujaya.com',
        ]);

        // Check user created with company
        $user = User::where('email', 'admin@barujaya.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->company_id)->not->toBeNull()
            ->and($user->hasRole('admin'))->toBeTrue();

        // User should be logged in
        $this->assertAuthenticated();
    });

    it('validates required fields', function () {
        $response = $this->post('/register', []);

        $response->assertSessionHasErrors([
            'company_name',
            'name',
            'email',
            'password',
        ]);
    });

    it('validates email format', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Test',
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'company_name' => 'PT Test',
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates password confirmation', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Test',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different123',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates minimum password length', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Test',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('generates unique company slug', function () {
        Company::factory()->create(['slug' => 'pt-test']);

        $this->post('/register', [
            'company_name' => 'PT Test',
            'name' => 'Admin',
            'email' => 'admin@pttest.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $company = Company::where('email', 'admin@pttest.com')->first();
        expect($company->slug)->not->toBe('pt-test');
    });

    it('sets default subscription for new company', function () {
        $this->post('/register', [
            'company_name' => 'PT New Company',
            'name' => 'Admin',
            'email' => 'admin@newcompany.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $company = Company::where('email', 'admin@newcompany.com')->first();
        expect($company->subscription_plan)->toBe('trial')
            ->and($company->max_employees)->toBe(10)
            ->and($company->is_active)->toBeTrue();
    });

    it('assigns multiple roles to registered user', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Multi Role',
            'name' => 'Admin Multi',
            'email' => 'admin@multirole.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'admin@multirole.com')->first();

        // User should have admin, hr-manager, and employee roles
        expect($user->hasRole('admin'))->toBeTrue()
            ->and($user->hasRole('hr-manager'))->toBeTrue()
            ->and($user->hasRole('employee'))->toBeTrue();
    });

    it('creates employee record for registered user', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Employee Record',
            'name' => 'Admin Employee',
            'email' => 'admin@employeerecord.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'admin@employeerecord.com')->first();
        $company = Company::where('email', 'admin@employeerecord.com')->first();

        // Employee record should be created and linked to user
        $employee = Employee::where('user_id', $user->id)->first();

        expect($employee)->not->toBeNull()
            ->and($employee->company_id)->toBe($company->id)
            ->and($employee->email)->toBe('admin@employeerecord.com')
            ->and($employee->first_name)->toBe('Admin')
            ->and($employee->last_name)->toBe('Employee')
            ->and($employee->is_active)->toBeTrue();
    });

    it('creates office location and assigns employee to it', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Office Test',
            'name' => 'Admin Office',
            'email' => 'admin@officetest.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $company = Company::where('email', 'admin@officetest.com')->first();
        $user = User::where('email', 'admin@officetest.com')->first();
        $employee = Employee::where('user_id', $user->id)->first();

        // Office location should be created
        $officeLocation = OfficeLocation::where('company_id', $company->id)
            ->where('is_headquarters', true)
            ->first();

        expect($officeLocation)->not->toBeNull()
            ->and($officeLocation->name)->toBe('Kantor Pusat')
            ->and($officeLocation->code)->toBe('HQ');

        // Employee should be assigned to office location
        expect($employee->officeLocations)->not->toBeEmpty()
            ->and($employee->officeLocations->contains($officeLocation))->toBeTrue();
    });

});
