<?php

use App\Models\Company;
use App\Models\Pph21Rate;
use App\Models\Pph21Setting;
use App\Models\PtkpSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');
});

describe('PPh21 Settings Access', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get(route('pph21-settings.index'));

        $response->assertRedirect('/login');
    });

    it('allows admin to access pph21 settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('pph21-settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('pph21-settings.index');
    });

    it('creates default settings if none exist', function () {
        $this->actingAs($this->admin);

        $this->assertDatabaseMissing('pph21_settings', [
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('pph21-settings.index'));

        $response->assertStatus(200);

        $this->assertDatabaseHas('pph21_settings', [
            'company_id' => $this->company->id,
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
        ]);
    });

    it('displays existing settings', function () {
        Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
            'is_gross_up' => true,
            'use_ter' => false,
            'npwp_discount_rate' => 20,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('pph21-settings.index'));

        $response->assertStatus(200);
    });
});

describe('PPh21 Settings Update', function () {
    it('updates pph21 settings successfully', function () {
        Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-setting'), [
            'is_gross_up' => true,
            'use_ter' => false,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('pph21_settings', [
            'company_id' => $this->company->id,
            'is_gross_up' => true,
            'use_ter' => false,
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-setting'), []);

        $response->assertSessionHasErrors([
            'npwp_discount_rate',
            'effective_year',
        ]);
    });

    it('validates npwp discount rate range', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-setting'), [
            'npwp_discount_rate' => 150, // Invalid - max 100
            'effective_year' => 2026,
        ]);

        $response->assertSessionHasErrors(['npwp_discount_rate']);
    });

    it('stores notes when provided', function () {
        Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-setting'), [
            'is_gross_up' => false,
            'use_ter' => true,
            'npwp_discount_rate' => 20,
            'effective_year' => 2026,
            'notes' => 'Updated per PMK 2024',
        ]);

        $this->assertDatabaseHas('pph21_settings', [
            'company_id' => $this->company->id,
            'notes' => 'Updated per PMK 2024',
        ]);
    });
});

describe('PTKP Initialization', function () {
    it('initializes ptkp settings for a year', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-ptkp'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        // Should create 12 PTKP statuses
        $this->assertDatabaseCount('ptkp_settings', 12);

        $this->assertDatabaseHas('ptkp_settings', [
            'company_id' => $this->company->id,
            'status_code' => 'TK/0',
            'annual_amount' => 54000000,
            'year' => 2026,
        ]);

        $this->assertDatabaseHas('ptkp_settings', [
            'company_id' => $this->company->id,
            'status_code' => 'K/3',
            'annual_amount' => 72000000,
            'year' => 2026,
        ]);
    });

    it('prevents duplicate initialization for same year', function () {
        PtkpSetting::factory()->create([
            'company_id' => $this->company->id,
            'year' => 2026,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-ptkp'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('error');
    });

    it('validates year range', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-ptkp'), [
            'year' => 2019, // Below min
        ]);

        $response->assertSessionHasErrors(['year']);
    });
});

describe('PPh21 Rate Initialization', function () {
    it('initializes pph21 rates for a year', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-rates'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        // Should create 5 progressive rates
        $this->assertDatabaseCount('pph21_rates', 5);

        $this->assertDatabaseHas('pph21_rates', [
            'company_id' => $this->company->id,
            'min_amount' => 0,
            'max_amount' => 60000000,
            'rate' => 5,
            'year' => 2026,
        ]);

        $this->assertDatabaseHas('pph21_rates', [
            'company_id' => $this->company->id,
            'min_amount' => 5000000000,
            'max_amount' => null,
            'rate' => 35,
            'year' => 2026,
        ]);
    });

    it('prevents duplicate initialization for same year', function () {
        Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'year' => 2026,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-rates'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('error');
    });

    it('validates year range', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('pph21-settings.initialize-rates'), [
            'year' => 2019, // Below min
        ]);

        $response->assertSessionHasErrors(['year']);
    });
});

describe('PTKP Update', function () {
    it('updates ptkp annual amount', function () {
        $ptkp = PtkpSetting::factory()->create([
            'company_id' => $this->company->id,
            'status_code' => 'TK/0',
            'annual_amount' => 54000000,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ptkp', $ptkp), [
            'annual_amount' => 60000000,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        $ptkp->refresh();
        expect((float) $ptkp->annual_amount)->toEqual(60000000);
    });

    it('validates annual amount is required', function () {
        $ptkp = PtkpSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ptkp', $ptkp), []);

        $response->assertSessionHasErrors(['annual_amount']);
    });

    it('prevents updating other company ptkp', function () {
        $otherCompany = Company::factory()->create();
        $ptkp = PtkpSetting::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-ptkp', $ptkp), [
            'annual_amount' => 60000000,
        ]);

        $response->assertStatus(403);
    });
});

describe('PPh21 Rate Update', function () {
    it('updates pph21 rate', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'rate' => 5,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-rate', $rate), [
            'rate' => 6,
        ]);

        $response->assertRedirect(route('pph21-settings.index'));
        $response->assertSessionHas('success');

        $rate->refresh();
        expect((float) $rate->rate)->toEqual(6);
    });

    it('validates rate is required', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-rate', $rate), []);

        $response->assertSessionHasErrors(['rate']);
    });

    it('validates rate range', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-rate', $rate), [
            'rate' => 150, // Invalid - max 100
        ]);

        $response->assertSessionHasErrors(['rate']);
    });

    it('prevents updating other company rate', function () {
        $otherCompany = Company::factory()->create();
        $rate = Pph21Rate::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('pph21-settings.update-rate', $rate), [
            'rate' => 10,
        ]);

        $response->assertStatus(403);
    });
});

describe('PPh21 Setting Model', function () {
    it('returns correct calculation method label for TER', function () {
        $setting = Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
            'use_ter' => true,
        ]);

        expect($setting->calculation_method_label)->toBe('TER (Tarif Efektif Rata-rata)');
    });

    it('returns correct calculation method label for Progressive', function () {
        $setting = Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
            'use_ter' => false,
        ]);

        expect($setting->calculation_method_label)->toBe('Tarif Progresif');
    });

    it('returns correct tax burden label for gross up', function () {
        $setting = Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
            'is_gross_up' => true,
        ]);

        expect($setting->tax_burden_label)->toBe('Ditanggung Perusahaan (Gross Up)');
    });

    it('returns correct tax burden label for employee burden', function () {
        $setting = Pph21Setting::factory()->create([
            'company_id' => $this->company->id,
            'is_gross_up' => false,
        ]);

        expect($setting->tax_burden_label)->toBe('Ditanggung Karyawan');
    });
});

describe('PTKP Model', function () {
    it('calculates monthly amount correctly', function () {
        $ptkp = PtkpSetting::factory()->create([
            'company_id' => $this->company->id,
            'annual_amount' => 54000000,
        ]);

        expect($ptkp->monthly_amount)->toEqual(4500000);
    });

    it('formats annual amount correctly', function () {
        $ptkp = PtkpSetting::factory()->create([
            'company_id' => $this->company->id,
            'annual_amount' => 54000000,
        ]);

        expect($ptkp->formatted_annual_amount)->toBe('Rp 54.000.000');
    });

    it('formats monthly amount correctly', function () {
        $ptkp = PtkpSetting::factory()->create([
            'company_id' => $this->company->id,
            'annual_amount' => 54000000,
        ]);

        expect($ptkp->formatted_monthly_amount)->toBe('Rp 4.500.000');
    });
});

describe('PPh21 Rate Model', function () {
    it('formats min amount correctly', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'min_amount' => 60000000,
        ]);

        expect($rate->formatted_min_amount)->toBe('Rp 60.000.000');
    });

    it('formats max amount correctly', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'max_amount' => 250000000,
        ]);

        expect($rate->formatted_max_amount)->toBe('Rp 250.000.000');
    });

    it('returns unlimited for null max amount', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'max_amount' => null,
        ]);

        expect($rate->formatted_max_amount)->toBe('Tak Terbatas');
    });

    it('returns correct range label', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'min_amount' => 60000000,
            'max_amount' => 250000000,
        ]);

        expect($rate->range_label)->toBe('Rp 60.000.000 - Rp 250.000.000');
    });

    it('returns above label for unlimited max', function () {
        $rate = Pph21Rate::factory()->create([
            'company_id' => $this->company->id,
            'min_amount' => 5000000000,
            'max_amount' => null,
        ]);

        expect($rate->range_label)->toBe('Di atas Rp 5.000.000.000');
    });
});
