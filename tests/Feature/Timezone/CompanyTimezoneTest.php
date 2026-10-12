<?php

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Company Timezone', function () {
    it('has default timezone Asia/Jakarta', function () {
        $company = Company::factory()->create();

        expect($company->timezone)->toBe('Asia/Jakarta');
    });

    it('can get current time in company timezone', function () {
        $company = Company::factory()->create(['timezone' => 'Asia/Makassar']);

        $companyNow = $company->now();

        expect($companyNow->timezone->getName())->toBe('Asia/Makassar');
    });

    it('can get today date in company timezone', function () {
        $company = Company::factory()->create(['timezone' => 'Asia/Jayapura']);

        $companyToday = $company->today();

        expect($companyToday->timezone->getName())->toBe('Asia/Jayapura');
        expect($companyToday->format('H:i:s'))->toBe('00:00:00');
    });

    it('can convert datetime to company timezone', function () {
        $company = Company::factory()->create(['timezone' => 'Asia/Makassar']);

        $utcTime = Carbon::parse('2026-02-18 00:00:00', 'UTC');
        $companyTime = $company->toCompanyTimezone($utcTime);

        expect($companyTime->timezone->getName())->toBe('Asia/Makassar');
        expect($companyTime->format('H:i:s'))->toBe('08:00:00');
    });

    it('can convert datetime to UTC', function () {
        $company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);

        $jakartaTime = Carbon::parse('2026-02-18 07:00:00', 'Asia/Jakarta');
        $utcTime = $company->toUtc($jakartaTime);

        expect($utcTime->timezone->getName())->toBe('UTC');
        expect($utcTime->format('H:i:s'))->toBe('00:00:00');
    });

    it('returns correct timezone offset', function () {
        $company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);
        expect($company->timezone_offset)->toBe('+07:00');

        $company2 = Company::factory()->create(['timezone' => 'Asia/Makassar']);
        expect($company2->timezone_offset)->toBe('+08:00');

        $company3 = Company::factory()->create(['timezone' => 'Asia/Jayapura']);
        expect($company3->timezone_offset)->toBe('+09:00');
    });

    it('returns indonesian timezones list', function () {
        $timezones = Company::getIndonesianTimezones();

        expect($timezones)->toHaveKeys(['Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura']);
    });

    it('returns all available timezones list', function () {
        $timezones = Company::getAllTimezones();

        expect($timezones)->toHaveKey('Asia/Jakarta');
        expect($timezones)->toHaveKey('Asia/Singapore');
        expect($timezones)->toHaveKey('America/New_York');
    });
});

describe('Company Timezone Settings', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $this->company = Company::factory()->create(['timezone' => 'Asia/Jakarta']);
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->user->assignRole('admin');
    });

    it('can update company timezone', function () {
        $response = $this->actingAs($this->user)
            ->put(route('settings.company-profile.update-settings'), [
                'timezone' => 'Asia/Makassar',
                'date_format' => 'd/m/Y',
                'currency' => 'IDR',
                'working_days_per_month' => 22,
            ]);

        $response->assertRedirect();

        $this->company->refresh();
        expect($this->company->timezone)->toBe('Asia/Makassar');
    });

    it('validates timezone value', function () {
        $response = $this->actingAs($this->user)
            ->put(route('settings.company-profile.update-settings'), [
                'timezone' => 'Invalid/Timezone',
            ]);

        $response->assertSessionHasErrors('timezone');
    });
});
