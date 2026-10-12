<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');
    $this->actingAs($this->admin);
});

describe('Admin Reset User Password', function () {
    it('displays reset password form for employee', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('employees.reset-password', $employee));

        $response->assertOk();
        $response->assertViewIs('employees.reset-password');
        $response->assertViewHas('employee');
    });

    it('returns 404 for employee from other company', function () {
        $otherCompany = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->get(route('employees.reset-password', $employee));

        $response->assertNotFound();
    });

    it('resets employee password successfully', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
            'password' => Hash::make('old-password'),
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('employees.show', $employee));
        $response->assertSessionHas('success');

        // Verify password was changed
        $user->refresh();
        expect(Hash::check('new-password123', $user->password))->toBeTrue();
    });

    it('validates password is required', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), []);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates password confirmation matches', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), [
            'password' => 'new-password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('validates password minimum length', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('returns 404 when resetting password for other company employee', function () {
        $otherCompany = Company::factory()->create();
        $employee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertNotFound();
    });

    it('shows error if employee has no user account', function () {
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => null,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('cannot reset password of demo account', function () {
        $demoUser = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'demo@demo.gajipro.com',
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $demoUser->id,
        ]);

        $response = $this->post(route('employees.reset-password.update', $employee), [
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });
});

describe('Admin Reset Password - Generate Random', function () {
    it('generates random password and shows it', function () {
        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $employee = Employee::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $user->id,
        ]);

        $response = $this->post(route('employees.reset-password.generate', $employee));

        $response->assertRedirect(route('employees.show', $employee));
        $response->assertSessionHas('success');
        $response->assertSessionHas('generated_password');
    });
});
