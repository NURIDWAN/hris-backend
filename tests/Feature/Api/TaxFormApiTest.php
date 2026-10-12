<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\TaxForm1721A1;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->company = Company::factory()->create([
        'npwp' => '01.234.567.8-901.000',
        'name' => 'PT Contoh Perusahaan',
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'npwp' => '12.345.678.9-012.000',
        'tax_status' => 'K/1',
    ]);
});

describe('GET /api/v1/tax-forms', function () {
    it('returns list of tax forms for authenticated employee', function () {
        Sanctum::actingAs($this->user);

        // Create tax forms for different years (unique constraint: company_id, employee_id, tax_year)
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2023,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2024,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2025,
        ]);

        $response = $this->getJson('/api/v1/tax-forms');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'form_number',
                        'tax_year',
                        'employee_name',
                        'gross_salary',
                        'formatted_gross_salary',
                        'total_pph21_withheld',
                        'formatted_pph21',
                        'generated_at',
                    ],
                ],
            ]);

        expect(count($response->json('data')))->toBe(3);
    });

    it('only returns tax forms for the authenticated employee', function () {
        Sanctum::actingAs($this->user);

        // Create tax form for this employee
        $ownTaxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        // Create tax form for other employee
        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->getJson('/api/v1/tax-forms');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
        expect($response->json('data.0.id'))->toBe($ownTaxForm->id);
    });

    it('can filter by tax year', function () {
        Sanctum::actingAs($this->user);

        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2025,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2024,
        ]);

        $response = $this->getJson('/api/v1/tax-forms?year=2025');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
        expect($response->json('data.0.tax_year'))->toBe(2025);
    });

    it('orders by tax year descending', function () {
        Sanctum::actingAs($this->user);

        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2023,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2025,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2024,
        ]);

        $response = $this->getJson('/api/v1/tax-forms');

        $response->assertOk();
        $data = $response->json('data');
        expect($data[0]['tax_year'])->toBe(2025);
        expect($data[1]['tax_year'])->toBe(2024);
        expect($data[2]['tax_year'])->toBe(2023);
    });

    it('returns 401 for unauthenticated request', function () {
        $response = $this->getJson('/api/v1/tax-forms');

        $response->assertUnauthorized();
    });

    it('returns empty when employee has no tax forms', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/tax-forms');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(0);
    });
});

describe('GET /api/v1/tax-forms/{id}', function () {
    it('returns tax form detail', function () {
        Sanctum::actingAs($this->user);

        $taxForm = TaxForm1721A1::factory()
            ->forCompany($this->company)
            ->forEmployee($this->employee)
            ->create([
                'tax_year' => 2025,
                'gross_salary' => 144000000,
                'total_pph21_withheld' => 3600000,
            ]);

        $response = $this->getJson("/api/v1/tax-forms/{$taxForm->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'form_number',
                    'tax_year',
                    'company' => [
                        'npwp',
                        'name',
                        'address',
                    ],
                    'employee' => [
                        'npwp',
                        'nik',
                        'name',
                        'address',
                        'ptkp_status',
                    ],
                    'work_period',
                    'penghasilan_bruto' => [
                        'gaji_pokok',
                        'tunjangan_pph',
                        'tunjangan_lainnya',
                        'honorarium',
                        'premi_asuransi',
                        'natura',
                        'tantiem_bonus_thr',
                        'gross_salary',
                    ],
                    'pengurang' => [
                        'biaya_jabatan',
                        'iuran_pensiun',
                        'total_pengurang',
                    ],
                    'perhitungan_pph' => [
                        'neto_salary',
                        'ptkp',
                        'pkp',
                        'pph21_terutang',
                        'pph21_dipotong_sebelumnya',
                        'total_pph21_withheld',
                    ],
                    'generated_at',
                ],
            ]);
    });

    it('returns 404 for other employee tax form', function () {
        Sanctum::actingAs($this->user);

        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->getJson("/api/v1/tax-forms/{$taxForm->id}");

        $response->assertNotFound();
    });

    it('returns 404 for non-existent tax form', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/tax-forms/99999');

        $response->assertNotFound();
    });

    it('returns 401 for unauthenticated request', function () {
        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson("/api/v1/tax-forms/{$taxForm->id}");

        $response->assertUnauthorized();
    });
});

describe('GET /api/v1/tax-forms/{id}/download', function () {
    it('returns pdf download url', function () {
        Sanctum::actingAs($this->user);

        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2025,
        ]);

        $response = $this->getJson("/api/v1/tax-forms/{$taxForm->id}/download");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'download_url',
                    'filename',
                ],
            ]);

        expect($response->json('data.filename'))->toContain('1721-A1');
        expect($response->json('data.filename'))->toContain('2025');
    });

    it('returns 404 for other employee tax form', function () {
        Sanctum::actingAs($this->user);

        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $taxForm = TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->getJson("/api/v1/tax-forms/{$taxForm->id}/download");

        $response->assertNotFound();
    });
});

describe('GET /api/v1/tax-forms/years', function () {
    it('returns available tax years for employee', function () {
        Sanctum::actingAs($this->user);

        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2025,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2024,
        ]);
        TaxForm1721A1::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'tax_year' => 2023,
        ]);

        $response = $this->getJson('/api/v1/tax-forms/years');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'year',
                        'count',
                    ],
                ],
            ]);

        $data = $response->json('data');
        expect(count($data))->toBe(3);
        expect($data[0]['year'])->toBe(2025);
    });

    it('returns empty when no tax forms exist', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/tax-forms/years');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(0);
    });
});
