<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('User Model', function () {

    it('can create a user', function () {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        expect($user)->toBeInstanceOf(User::class)
            ->and($user->name)->toBe('John Doe')
            ->and($user->email)->toBe('john@example.com');
    });

    it('belongs to a company', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        expect($user->company)->toBeInstanceOf(Company::class)
            ->and($user->company->id)->toBe($company->id);
    });

    it('can be a super admin without company', function () {
        $user = User::factory()->create([
            'company_id' => null,
            'is_superadmin' => true,
        ]);

        expect($user->isSuperAdmin())->toBeTrue()
            ->and($user->company_id)->toBeNull();
    });

    it('can be a company admin', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Role::create(['name' => 'admin']);
        $user->assignRole('admin');

        expect($user->isCompanyAdmin())->toBeTrue();
    });

    it('can be soft deleted', function () {
        $user = User::factory()->create();

        $user->delete();

        expect($user->trashed())->toBeTrue()
            ->and(User::count())->toBe(0)
            ->and(User::withTrashed()->count())->toBe(1);
    });

    it('can have roles', function () {
        $user = User::factory()->create();

        Role::create(['name' => 'hr-manager']);
        Role::create(['name' => 'payroll-staff']);

        $user->assignRole('hr-manager');
        $user->assignRole('payroll-staff');

        expect($user->hasRole('hr-manager'))->toBeTrue()
            ->and($user->hasRole('payroll-staff'))->toBeTrue()
            ->and($user->roles)->toHaveCount(2);
    });

    it('hashes password automatically', function () {
        $user = User::create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'secret123',
        ]);

        expect($user->password)->not->toBe('secret123')
            ->and(password_verify('secret123', $user->password))->toBeTrue();
    });

    it('can have active/inactive status', function () {
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        expect($activeUser->is_active)->toBeTrue()
            ->and($inactiveUser->is_active)->toBeFalse();
    });

    it('has optional phone and avatar', function () {
        $user = User::factory()->create([
            'phone' => '08123456789',
            'avatar' => 'avatars/john.jpg',
        ]);

        expect($user->phone)->toBe('08123456789')
            ->and($user->avatar)->toBe('avatars/john.jpg');
    });

});
