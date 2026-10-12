<?php

use App\Models\Company;
use App\Models\ThrSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'hr-manager']);
    Role::create(['name' => 'employee']);

    $this->company = Company::factory()->create();

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');
});

describe('THR Setting Index', function () {
    it('displays thr settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/thr-settings');

        $response->assertStatus(200);
        $response->assertViewIs('thr-settings.index');
        $response->assertSee('Pengaturan THR');
    });

    it('shows current thr settings', function () {
        $this->actingAs($this->admin);

        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
        ]);

        $response = $this->get('/thr-settings');

        $response->assertStatus(200);
        $response->assertSee('1 Bulan Gaji');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/thr-settings');

        $response->assertRedirect('/login');
    });
});

describe('Update THR Settings', function () {
    it('can update thr settings', function () {
        $this->actingAs($this->admin);

        ThrSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->put('/thr-settings', [
            'calculation_method' => 'prorata',
            'min_service_months' => 3,
            'prorata_formula' => 'months_worked_per_12',
            'include_allowances' => true,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('thr_settings', [
            'company_id' => $this->company->id,
            'calculation_method' => 'prorata',
            'min_service_months' => 3,
        ]);
    });

    it('creates thr settings if not exists', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/thr-settings', [
            'calculation_method' => 'one_month_salary',
            'min_service_months' => 1,
            'prorata_formula' => 'months_worked_per_12',
            'include_allowances' => false,
            'religious_holiday' => 'idul_fitri',
            'payment_days_before' => 7,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('thr_settings', [
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
        ]);
    });

    it('validates calculation method', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/thr-settings', [
            'calculation_method' => 'invalid',
            'min_service_months' => 1,
        ]);

        $response->assertSessionHasErrors(['calculation_method']);
    });

    it('validates min service months', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/thr-settings', [
            'calculation_method' => 'one_month_salary',
            'min_service_months' => -1,
        ]);

        $response->assertSessionHasErrors(['min_service_months']);
    });
});

describe('THR Setting Model', function () {
    it('has correct calculation method labels', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'calculation_method' => 'one_month_salary',
        ]);

        expect($setting->calculation_method_label)->toBe('1 Bulan Gaji');

        $setting->update(['calculation_method' => 'prorata']);
        expect($setting->calculation_method_label)->toBe('Prorata');
    });

    it('has correct religious holiday labels', function () {
        $setting = ThrSetting::factory()->create([
            'company_id' => $this->company->id,
            'religious_holiday' => 'idul_fitri',
        ]);

        expect($setting->religious_holiday_label)->toBe('Idul Fitri');

        $setting->update(['religious_holiday' => 'christmas']);
        expect($setting->religious_holiday_label)->toBe('Natal');
    });
});
