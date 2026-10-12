<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Company Model', function () {

    it('can create a company', function () {
        $company = Company::create([
            'name' => 'PT GajiPro Indonesia',
            'slug' => 'pt-jago-gajian-indonesia',
            'email' => 'hr@gajipro.com',
            'phone' => '021-12345678',
            'address' => 'Jl. HR Muhammad No. 123, Jakarta',
            'is_active' => true,
        ]);

        expect($company)->toBeInstanceOf(Company::class)
            ->and($company->name)->toBe('PT GajiPro Indonesia')
            ->and($company->slug)->toBe('pt-jago-gajian-indonesia')
            ->and($company->email)->toBe('hr@gajipro.com')
            ->and($company->is_active)->toBeTrue();
    });

    it('generates slug automatically from name if not provided', function () {
        $company = Company::create([
            'name' => 'PT Maju Bersama Jaya',
            'email' => 'hr@majubersama.com',
        ]);

        expect($company->slug)->toBe('pt-maju-bersama-jaya');
    });

    it('has unique slug', function () {
        Company::create([
            'name' => 'PT Unique Company',
            'slug' => 'pt-unique-company',
            'email' => 'hr@unique1.com',
        ]);

        // This should generate a different slug
        $company2 = Company::create([
            'name' => 'PT Unique Company',
            'email' => 'hr@unique2.com',
        ]);

        expect($company2->slug)->not->toBe('pt-unique-company');
    });

    it('has many users', function () {
        $company = Company::factory()->create();

        $user1 = User::factory()->create(['company_id' => $company->id]);
        $user2 = User::factory()->create(['company_id' => $company->id]);

        expect($company->users)->toHaveCount(2)
            ->and($company->users->first())->toBeInstanceOf(User::class);
    });

    it('can be soft deleted', function () {
        $company = Company::factory()->create();

        $company->delete();

        expect($company->trashed())->toBeTrue()
            ->and(Company::count())->toBe(0)
            ->and(Company::withTrashed()->count())->toBe(1);
    });

    it('has subscription fields', function () {
        $company = Company::create([
            'name' => 'PT Subscription Test',
            'email' => 'hr@subscription.com',
            'subscription_plan' => 'professional',
            'subscription_ends_at' => now()->addYear(),
            'max_employees' => 100,
        ]);

        expect($company->subscription_plan)->toBe('professional')
            ->and($company->max_employees)->toBe(100)
            ->and($company->subscription_ends_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('can check if subscription is active', function () {
        $activeCompany = Company::factory()->create([
            'subscription_ends_at' => now()->addMonth(),
            'is_active' => true,
        ]);

        $expiredCompany = Company::factory()->create([
            'subscription_ends_at' => now()->subDay(),
            'is_active' => true,
        ]);

        $inactiveCompany = Company::factory()->create([
            'subscription_ends_at' => now()->addMonth(),
            'is_active' => false,
        ]);

        expect($activeCompany->isSubscriptionActive())->toBeTrue()
            ->and($expiredCompany->isSubscriptionActive())->toBeFalse()
            ->and($inactiveCompany->isSubscriptionActive())->toBeFalse();
    });

    it('can check employee limit', function () {
        $company = Company::factory()->create([
            'max_employees' => 5,
        ]);

        // Create 4 users
        User::factory()->count(4)->create(['company_id' => $company->id]);

        expect($company->canAddEmployee())->toBeTrue();

        // Create 1 more user to reach limit
        User::factory()->create(['company_id' => $company->id]);

        expect($company->fresh()->canAddEmployee())->toBeFalse();
    });

    it('has settings as json', function () {
        $settings = [
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'date_format' => 'd/m/Y',
        ];

        $company = Company::create([
            'name' => 'PT Settings Test',
            'email' => 'hr@settings.com',
            'settings' => $settings,
        ]);

        expect($company->settings)->toBeArray()
            ->and($company->settings['timezone'])->toBe('Asia/Jakarta')
            ->and($company->settings['currency'])->toBe('IDR');
    });

});
