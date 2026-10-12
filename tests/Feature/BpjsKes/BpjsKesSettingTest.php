<?php

use App\Models\BpjsKesSetting;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();

    // Create standard roles with proper team context
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->admin->assignRole('admin');
});

describe('BPJS Kesehatan Settings Index', function () {
    it('displays bpjs kes settings page', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/bpjs-kes-settings');

        $response->assertStatus(200);
        $response->assertViewIs('bpjs-kes-settings.index');
    });

    it('creates default settings if not exists', function () {
        $this->actingAs($this->admin);

        $this->assertDatabaseMissing('bpjs_kes_settings', [
            'company_id' => $this->company->id,
        ]);

        $response = $this->get('/bpjs-kes-settings');

        $response->assertStatus(200);
        $this->assertDatabaseHas('bpjs_kes_settings', [
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
        ]);
    });

    it('shows existing settings', function () {
        $this->actingAs($this->admin);

        BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
        ]);

        $response = $this->get('/bpjs-kes-settings');

        $response->assertStatus(200);
        $response->assertSee('4');
        $response->assertSee('BPJS Kesehatan');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/bpjs-kes-settings');

        $response->assertRedirect('/login');
    });
});

describe('BPJS Kesehatan Settings Update', function () {
    it('can update bpjs kes settings', function () {
        $this->actingAs($this->admin);

        BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->put('/bpjs-kes-settings/update', [
            'company_rate' => 4.50,
            'employee_rate' => 1.50,
            'min_salary_basis' => 3000000,
            'max_salary_basis' => 15000000,
            'effective_year' => 2026,
            'notes' => 'Updated settings',
        ]);

        $response->assertRedirect('/bpjs-kes-settings');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bpjs_kes_settings', [
            'company_id' => $this->company->id,
            'company_rate' => 4.50,
            'employee_rate' => 1.50,
            'min_salary_basis' => 3000000,
            'max_salary_basis' => 15000000,
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/bpjs-kes-settings/update', []);

        $response->assertSessionHasErrors([
            'company_rate',
            'employee_rate',
            'min_salary_basis',
            'max_salary_basis',
            'effective_year',
        ]);
    });

    it('validates rate ranges', function () {
        $this->actingAs($this->admin);

        $response = $this->put('/bpjs-kes-settings/update', [
            'company_rate' => 150, // > 100
            'employee_rate' => -5, // < 0
            'min_salary_basis' => 3000000,
            'max_salary_basis' => 15000000,
            'effective_year' => 2026,
        ]);

        $response->assertSessionHasErrors(['company_rate', 'employee_rate']);
    });
});

describe('BPJS Kesehatan Calculation', function () {
    it('calculates contribution correctly', function () {
        $this->actingAs($this->admin);

        BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
        ]);

        $response = $this->post('/bpjs-kes-settings/calculate', [
            'salary' => 10000000,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'salary',
            'contribution' => [
                'salary_basis',
                'company',
                'employee',
                'total',
            ],
        ]);

        $data = $response->json();
        // 10,000,000 * 4% = 400,000 (company)
        // 10,000,000 * 1% = 100,000 (employee)
        expect((float) $data['contribution']['company'])->toBe(400000.0);
        expect((float) $data['contribution']['employee'])->toBe(100000.0);
        expect((float) $data['contribution']['total'])->toBe(500000.0);
    });

    it('applies max salary limit', function () {
        $this->actingAs($this->admin);

        BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
        ]);

        $response = $this->post('/bpjs-kes-settings/calculate', [
            'salary' => 20000000, // Above max
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Should use max: 12,000,000
        // 12,000,000 * 4% = 480,000 (company)
        // 12,000,000 * 1% = 120,000 (employee)
        expect((float) $data['contribution']['salary_basis'])->toBe(12000000.0);
        expect((float) $data['contribution']['company'])->toBe(480000.0);
        expect((float) $data['contribution']['employee'])->toBe(120000.0);
    });

    it('applies min salary limit', function () {
        $this->actingAs($this->admin);

        BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
        ]);

        $response = $this->post('/bpjs-kes-settings/calculate', [
            'salary' => 2000000, // Below min
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Should use min: 2,900,000
        expect((float) $data['contribution']['salary_basis'])->toBe(2900000.0);
    });

    it('returns error when settings not configured', function () {
        $this->actingAs($this->admin);

        // No settings created

        $response = $this->post('/bpjs-kes-settings/calculate', [
            'salary' => 10000000,
        ]);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Setting BPJS Kesehatan belum dikonfigurasi']);
    });
});

describe('BPJS Kesehatan Model', function () {
    it('calculates total rate correctly', function () {
        $setting = BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00,
            'employee_rate' => 1.00,
        ]);

        expect($setting->total_rate)->toBe(5.0);
    });

    it('formats salary basis correctly', function () {
        $setting = BpjsKesSetting::factory()->create([
            'company_id' => $this->company->id,
            'min_salary_basis' => 2900000,
            'max_salary_basis' => 12000000,
        ]);

        expect($setting->formatted_min_salary_basis)->toBe('Rp 2.900.000');
        expect($setting->formatted_max_salary_basis)->toBe('Rp 12.000.000');
    });
});
