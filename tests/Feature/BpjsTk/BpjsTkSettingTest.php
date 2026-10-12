<?php

use App\Models\BpjsTkSetting;
use App\Models\Company;
use App\Models\JkkRiskRate;
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

describe('BPJS TK Settings Access', function () {
    it('redirects unauthenticated users to login', function () {
        $response = $this->get(route('bpjs-tk-settings.index'));

        $response->assertRedirect('/login');
    });

    it('allows admin to access bpjs tk settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('bpjs-tk-settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('bpjs-tk-settings.index');
    });

    it('creates default settings if none exist', function () {
        $this->actingAs($this->admin);

        $this->assertDatabaseMissing('bpjs_tk_settings', [
            'company_id' => $this->company->id,
        ]);

        $response = $this->get(route('bpjs-tk-settings.index'));

        $response->assertStatus(200);

        $this->assertDatabaseHas('bpjs_tk_settings', [
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
        ]);
    });

    it('displays existing settings', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jkk_risk_level' => 'medium',
            'jkk_rate' => 0.89,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('bpjs-tk-settings.index'));

        $response->assertStatus(200);
        $response->assertSee('3.70');
        $response->assertSee('2.00');
    });
});

describe('BPJS TK Settings Update', function () {
    it('updates bpjs tk settings successfully', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('bpjs-tk-settings.update'), [
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jkk_risk_level' => 'high',
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => 2026,
        ]);

        $response->assertRedirect(route('bpjs-tk-settings.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bpjs_tk_settings', [
            'company_id' => $this->company->id,
            'jkk_risk_level' => 'high',
            'jkk_rate' => 1.27, // High risk rate
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('bpjs-tk-settings.update'), []);

        $response->assertSessionHasErrors([
            'jht_company_rate',
            'jht_employee_rate',
            'jkk_risk_level',
            'jkm_rate',
            'jp_company_rate',
            'jp_employee_rate',
            'jp_max_salary',
            'effective_year',
        ]);
    });

    it('validates rate ranges', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('bpjs-tk-settings.update'), [
            'jht_company_rate' => 150, // Invalid - max 100
            'jht_employee_rate' => -5, // Invalid - min 0
            'jkk_risk_level' => 'very_low',
            'jkm_rate' => 0.30,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'effective_year' => 2026,
        ]);

        $response->assertSessionHasErrors(['jht_company_rate', 'jht_employee_rate']);
    });

    it('validates jkk risk level options', function () {
        $this->actingAs($this->admin);

        $response = $this->put(route('bpjs-tk-settings.update'), [
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jkk_risk_level' => 'invalid_level',
            'jkm_rate' => 0.30,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'effective_year' => 2026,
        ]);

        $response->assertSessionHasErrors(['jkk_risk_level']);
    });

    it('updates jkk rate based on risk level', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jkk_risk_level' => 'very_low',
            'jkk_rate' => 0.24,
        ]);

        $this->actingAs($this->admin);

        // Update to very_high risk
        $this->put(route('bpjs-tk-settings.update'), [
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jkk_risk_level' => 'very_high',
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
            'effective_year' => 2026,
        ]);

        $this->assertDatabaseHas('bpjs_tk_settings', [
            'company_id' => $this->company->id,
            'jkk_risk_level' => 'very_high',
            'jkk_rate' => 1.74,
        ]);
    });

    it('can disable individual programs', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_enabled' => true,
            'jkk_enabled' => true,
            'jkm_enabled' => true,
            'jp_enabled' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('bpjs-tk-settings.update'), [
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => false,
            'jkk_risk_level' => 'very_low',
            'jkk_enabled' => false,
            'jkm_rate' => 0.30,
            'jkm_enabled' => false,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => false,
            'effective_year' => 2026,
        ]);

        $response->assertRedirect(route('bpjs-tk-settings.index'));

        $this->assertDatabaseHas('bpjs_tk_settings', [
            'company_id' => $this->company->id,
            'jht_enabled' => false,
            'jkk_enabled' => false,
            'jkm_enabled' => false,
            'jp_enabled' => false,
        ]);
    });

    it('stores notes when provided', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('bpjs-tk-settings.update'), [
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jkk_risk_level' => 'very_low',
            'jkm_rate' => 0.30,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'effective_year' => 2026,
            'notes' => 'Updated per PP 2024',
        ]);

        $this->assertDatabaseHas('bpjs_tk_settings', [
            'company_id' => $this->company->id,
            'notes' => 'Updated per PP 2024',
        ]);
    });
});

describe('BPJS TK Calculation', function () {
    it('calculates employee contribution correctly', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('bpjs-tk-settings.calculate'), [
            'salary' => 10000000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'salary',
            'employee' => ['jht', 'jp', 'total'],
            'company' => ['jht', 'jkk', 'jkm', 'jp', 'total'],
            'total' => ['employee', 'company', 'grand_total'],
        ]);

        // Employee JHT: 10,000,000 * 2% = 200,000
        // Employee JP: 10,000,000 * 1% = 100,000 (below max salary)
        $data = $response->json();
        expect($data['employee']['jht'])->toEqual(200000);
        expect($data['employee']['jp'])->toEqual(100000);
        expect($data['employee']['total'])->toEqual(300000);
    });

    it('calculates company contribution correctly', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jkk_rate' => 0.24,
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('bpjs-tk-settings.calculate'), [
            'salary' => 10000000,
        ]);

        $response->assertStatus(200);

        // Company JHT: 10,000,000 * 3.7% = 370,000
        // Company JKK: 10,000,000 * 0.24% = 24,000
        // Company JKM: 10,000,000 * 0.30% = 30,000
        // Company JP: 10,000,000 * 2% = 200,000
        $data = $response->json();
        expect(round($data['company']['jht']))->toEqual(370000);
        expect(round($data['company']['jkk']))->toEqual(24000);
        expect(round($data['company']['jkm']))->toEqual(30000);
        expect(round($data['company']['jp']))->toEqual(200000);
        expect(round($data['company']['total']))->toEqual(624000);
    });

    it('applies jp max salary cap', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_enabled' => false,
            'jkk_enabled' => false,
            'jkm_enabled' => false,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300,
            'jp_enabled' => true,
        ]);

        $this->actingAs($this->admin);

        // Salary above max
        $response = $this->postJson(route('bpjs-tk-settings.calculate'), [
            'salary' => 20000000,
        ]);

        $response->assertStatus(200);

        // JP should be calculated on max salary, not actual salary
        // Employee JP: 10,042,300 * 1% = 100,423
        // Company JP: 10,042,300 * 2% = 200,846
        $data = $response->json();
        expect((int) $data['employee']['jp'])->toEqual(100423);
        expect((int) $data['company']['jp'])->toEqual(200846);
    });

    it('returns zero for disabled programs', function () {
        BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_enabled' => false,
            'jkk_enabled' => false,
            'jkm_enabled' => false,
            'jp_enabled' => false,
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson(route('bpjs-tk-settings.calculate'), [
            'salary' => 10000000,
        ]);

        $response->assertStatus(200);

        $data = $response->json();
        expect($data['employee']['total'])->toEqual(0);
        expect($data['company']['total'])->toEqual(0);
    });

    it('validates salary is required', function () {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('bpjs-tk-settings.calculate'), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['salary']);
    });

    it('returns error when settings not configured', function () {
        $this->actingAs($this->admin);

        $response = $this->postJson(route('bpjs-tk-settings.calculate'), [
            'salary' => 10000000,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Setting BPJS TK belum dikonfigurasi']);
    });
});

describe('BPJS TK Model', function () {
    it('calculates total company rate correctly', function () {
        $setting = BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_enabled' => true,
            'jkk_rate' => 0.24,
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_enabled' => true,
        ]);

        // 3.70 + 0.24 + 0.30 + 2.00 = 6.24
        expect($setting->total_company_rate)->toBe(6.24);
    });

    it('calculates total employee rate correctly', function () {
        $setting = BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,
            'jp_employee_rate' => 1.00,
            'jp_enabled' => true,
        ]);

        // 2.00 + 1.00 = 3.00
        expect($setting->total_employee_rate)->toBe(3.0);
    });

    it('excludes disabled programs from total rates', function () {
        $setting = BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => false, // Disabled
            'jkk_rate' => 0.24,
            'jkk_enabled' => true,
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_enabled' => false, // Disabled
        ]);

        // Company: only JKK + JKM = 0.24 + 0.30 = 0.54
        expect($setting->total_company_rate)->toBe(0.54);

        // Employee: nothing enabled that contributes
        expect($setting->total_employee_rate)->toBe(0.0);
    });

    it('returns correct jkk risk level label', function () {
        $setting = BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jkk_risk_level' => 'high',
        ]);

        expect($setting->jkk_risk_level_label)->toBe('Tinggi (1.27%)');
    });

    it('formats jp max salary correctly', function () {
        $setting = BpjsTkSetting::factory()->create([
            'company_id' => $this->company->id,
            'jp_max_salary' => 10042300,
        ]);

        expect($setting->formatted_jp_max_salary)->toBe('Rp 10.042.300');
    });
});

describe('JKK Risk Rate Initialization', function () {
    it('initializes jkk risk rates for a year', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('bpjs-tk-settings.initialize-jkk-rates'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('bpjs-tk-settings.index'));
        $response->assertSessionHas('success');

        // Should create 5 risk levels
        $this->assertDatabaseCount('jkk_risk_rates', 5);

        $this->assertDatabaseHas('jkk_risk_rates', [
            'company_id' => $this->company->id,
            'risk_level' => 'very_low',
            'rate' => 0.24,
            'year' => 2026,
        ]);

        $this->assertDatabaseHas('jkk_risk_rates', [
            'company_id' => $this->company->id,
            'risk_level' => 'very_high',
            'rate' => 1.74,
            'year' => 2026,
        ]);
    });

    it('prevents duplicate initialization for same year', function () {
        JkkRiskRate::factory()->create([
            'company_id' => $this->company->id,
            'year' => 2026,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('bpjs-tk-settings.initialize-jkk-rates'), [
            'year' => 2026,
        ]);

        $response->assertRedirect(route('bpjs-tk-settings.index'));
        $response->assertSessionHas('error');
    });

    it('validates year range', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('bpjs-tk-settings.initialize-jkk-rates'), [
            'year' => 2019, // Below min
        ]);

        $response->assertSessionHasErrors(['year']);
    });
});
