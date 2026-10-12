<?php

use App\Models\BpjsKesSetting;
use App\Models\BpjsTkSetting;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Pph21Setting;
use App\Services\PayrollCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'tax_status' => 'TK/0',
        'npwp' => '12.345.678.9-012.345',
    ]);
    $this->service = new PayrollCalculationService;
});

describe('PayrollCalculationService', function () {
    describe('PPh21 Calculation', function () {
        it('calculates PPh21 with default 5% when no settings exist', function () {
            $grossSalary = 10000000;

            $pph21 = $this->service->calculatePph21(
                $this->company->id,
                $this->employee,
                $grossSalary
            );

            // Default 5% calculation
            expect($pph21)->toBe(500000.0);
        });

        it('calculates PPh21 with progressive rate when settings exist', function () {
            Pph21Setting::create([
                'company_id' => $this->company->id,
                'is_gross_up' => false,
                'use_ter' => false,
                'npwp_discount_rate' => 20,
                'effective_year' => 2024,
            ]);

            $grossSalary = 10000000;

            $pph21 = $this->service->calculatePph21(
                $this->company->id,
                $this->employee,
                $grossSalary
            );

            // Should use progressive calculation
            // Gross: 10,000,000
            // PTKP TK/0 monthly: 54,000,000 / 12 = 4,500,000
            // PKP monthly: 10,000,000 - 4,500,000 = 5,500,000
            // PKP annual: 5,500,000 * 12 = 66,000,000
            // Tax: (60,000,000 * 5%) + (6,000,000 * 15%) = 3,000,000 + 900,000 = 3,900,000 annual
            // Monthly: 3,900,000 / 12 = 325,000
            expect($pph21)->toBe(325000.0);
        });

        it('applies 20% penalty when employee has no NPWP', function () {
            Pph21Setting::create([
                'company_id' => $this->company->id,
                'is_gross_up' => false,
                'use_ter' => false,
                'npwp_discount_rate' => 20,
                'effective_year' => 2024,
            ]);

            $employeeNoNpwp = Employee::factory()->create([
                'company_id' => $this->company->id,
                'tax_status' => 'TK/0',
                'npwp' => null,
            ]);

            $grossSalary = 10000000;

            $pph21 = $this->service->calculatePph21(
                $this->company->id,
                $employeeNoNpwp,
                $grossSalary
            );

            // 325,000 * 1.2 = 390,000
            expect($pph21)->toBe(390000.0);
        });

        it('returns zero tax when income is below PTKP', function () {
            Pph21Setting::create([
                'company_id' => $this->company->id,
                'is_gross_up' => false,
                'use_ter' => false,
                'npwp_discount_rate' => 20,
                'effective_year' => 2024,
            ]);

            $grossSalary = 4000000; // Below PTKP monthly (4,500,000)

            $pph21 = $this->service->calculatePph21(
                $this->company->id,
                $this->employee,
                $grossSalary
            );

            expect($pph21)->toBe(0.0);
        });
    });

    describe('BPJS Kesehatan Calculation', function () {
        it('calculates BPJS Kesehatan with default rates when no settings exist', function () {
            $grossSalary = 10000000;

            $bpjsKes = $this->service->calculateBpjsKesehatan(
                $this->company->id,
                $grossSalary
            );

            // Default: 4% company, 1% employee
            expect($bpjsKes['company'])->toBe(400000.0);
            expect($bpjsKes['employee'])->toBe(100000.0);
            expect($bpjsKes['total'])->toBe(500000.0);
        });

        it('calculates BPJS Kesehatan with custom settings', function () {
            BpjsKesSetting::create([
                'company_id' => $this->company->id,
                'company_rate' => 4.00,
                'employee_rate' => 1.00,
                'min_salary_basis' => 2900000,
                'max_salary_basis' => 12000000,
                'effective_year' => 2024,
                'is_active' => true,
            ]);

            $grossSalary = 10000000;

            $bpjsKes = $this->service->calculateBpjsKesehatan(
                $this->company->id,
                $grossSalary
            );

            expect($bpjsKes['company'])->toBe(400000.0);
            expect($bpjsKes['employee'])->toBe(100000.0);
        });

        it('applies max salary cap for BPJS Kesehatan', function () {
            BpjsKesSetting::create([
                'company_id' => $this->company->id,
                'company_rate' => 4.00,
                'employee_rate' => 1.00,
                'min_salary_basis' => 2900000,
                'max_salary_basis' => 12000000,
                'effective_year' => 2024,
                'is_active' => true,
            ]);

            $grossSalary = 20000000; // Above max

            $bpjsKes = $this->service->calculateBpjsKesehatan(
                $this->company->id,
                $grossSalary
            );

            // Should be capped at 12,000,000
            expect((float) $bpjsKes['salary_basis'])->toBe(12000000.0);
            expect((float) $bpjsKes['company'])->toBe(480000.0); // 12M * 4%
            expect((float) $bpjsKes['employee'])->toBe(120000.0); // 12M * 1%
        });
    });

    describe('BPJS Ketenagakerjaan Calculation', function () {
        it('calculates BPJS TK with default rates when no settings exist', function () {
            $grossSalary = 10000000;

            $bpjsTk = $this->service->calculateBpjsKetenagakerjaan(
                $this->company->id,
                $grossSalary
            );

            // Default rates
            expect($bpjsTk['jht_employee'])->toBe(200000.0); // 2%
            expect($bpjsTk['jht_company'])->toBe(370000.0); // 3.7%
            expect($bpjsTk['jp_employee'])->toBe(100000.0); // 1%
            expect($bpjsTk['jp_company'])->toBe(200000.0); // 2%
            expect($bpjsTk['employee_total'])->toBe(300000.0);
        });

        it('calculates BPJS TK with custom settings', function () {
            BpjsTkSetting::create([
                'company_id' => $this->company->id,
                'jht_company_rate' => 3.70,
                'jht_employee_rate' => 2.00,
                'jht_enabled' => true,
                'jkk_rate' => 0.24,
                'jkk_risk_level' => 'very_low',
                'jkk_enabled' => true,
                'jkm_rate' => 0.30,
                'jkm_enabled' => true,
                'jp_company_rate' => 2.00,
                'jp_employee_rate' => 1.00,
                'jp_max_salary' => 10042300,
                'jp_enabled' => true,
                'effective_year' => 2024,
                'is_active' => true,
            ]);

            $grossSalary = 10000000;

            $bpjsTk = $this->service->calculateBpjsKetenagakerjaan(
                $this->company->id,
                $grossSalary
            );

            expect($bpjsTk['jht_employee'])->toBe(200000.0);
            expect($bpjsTk['jp_employee'])->toBe(100000.0);
            expect($bpjsTk['employee_total'])->toBe(300000.0);
        });
    });

    describe('Full Calculation', function () {
        it('calculates all payroll components correctly', function () {
            // Setup settings
            Pph21Setting::create([
                'company_id' => $this->company->id,
                'is_gross_up' => false,
                'use_ter' => false,
                'npwp_discount_rate' => 20,
                'effective_year' => 2024,
            ]);

            BpjsKesSetting::create([
                'company_id' => $this->company->id,
                'company_rate' => 4.00,
                'employee_rate' => 1.00,
                'min_salary_basis' => 2900000,
                'max_salary_basis' => 12000000,
                'effective_year' => 2024,
                'is_active' => true,
            ]);

            BpjsTkSetting::create([
                'company_id' => $this->company->id,
                'jht_company_rate' => 3.70,
                'jht_employee_rate' => 2.00,
                'jht_enabled' => true,
                'jkk_rate' => 0.24,
                'jkk_risk_level' => 'very_low',
                'jkk_enabled' => true,
                'jkm_rate' => 0.30,
                'jkm_enabled' => true,
                'jp_company_rate' => 2.00,
                'jp_employee_rate' => 1.00,
                'jp_max_salary' => 10042300,
                'jp_enabled' => true,
                'effective_year' => 2024,
                'is_active' => true,
            ]);

            $salaryData = [
                'gross_salary' => 11000000,
                'basic_salary' => 10000000,
                'total_earnings' => 1000000,
                'taxable_earnings' => 1000000,
                'total_deductions' => 0,
            ];

            $result = $this->service->calculate(
                $this->employee,
                $salaryData,
                $this->company->id
            );

            // Check PPh21 is calculated
            expect($result['pph21'])->toBeGreaterThan(0);

            // Check BPJS Kesehatan (use toEqual for type-flexible comparison)
            expect($result['bpjs_kes_employee'])->toEqual(110000); // 11M * 1%
            expect($result['bpjs_kes_company'])->toEqual(440000); // 11M * 4%

            // Check BPJS TK (JP has max salary cap of 10,042,300)
            // JHT: 11M * 2% = 220,000
            // JP: min(11M, 10,042,300) * 1% = 100,423
            // Total: 320,423
            expect($result['bpjs_tk_employee'])->toEqual(320423);

            // Check total deductions includes BPJS
            // BPJS Kes: 110,000 + BPJS TK: 320,423 = 430,423
            expect($result['total_deductions'])->toEqual(430423);

            // Check net salary
            $expectedNet = 11000000 - $result['total_deductions'] - $result['pph21'];
            expect($result['net_salary'])->toEqual($expectedNet);
        });
    });

    describe('Deduction Breakdown', function () {
        it('returns correct breakdown of all deductions', function () {
            $grossSalary = 10000000;

            $breakdown = $this->service->getDeductionBreakdown(
                $this->employee,
                $grossSalary,
                $this->company->id
            );

            expect($breakdown)->toHaveKeys(['pph21', 'bpjs_kes', 'bpjs_jht', 'bpjs_jp']);
            expect($breakdown['pph21']['name'])->toBe('PPh 21');
            expect($breakdown['bpjs_kes']['name'])->toBe('BPJS Kesehatan');
            expect($breakdown['bpjs_jht']['name'])->toBe('BPJS JHT');
            expect($breakdown['bpjs_jp']['name'])->toBe('BPJS JP');
        });
    });
});
