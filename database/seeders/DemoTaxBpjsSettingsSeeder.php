<?php

namespace Database\Seeders;

use App\Models\BpjsKesSetting;
use App\Models\BpjsTkSetting;
use App\Models\Company;
use App\Models\Pph21Rate;
use App\Models\Pph21Setting;
use App\Models\Pph21TerRate;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk konfigurasi Pajak dan BPJS PT Demo GajiPro
 *
 * Konfigurasi yang dibuat:
 * =======================
 *
 * 1. PPh 21 Settings
 *    - Metode perhitungan: Tarif Progresif (sesuai UU HPP)
 *    - Beban pajak: Ditanggung Karyawan
 *    - Tarif progresif 5 layer sesuai peraturan 2024
 *    - Tarif TER untuk perhitungan bulanan (opsional)
 *
 * 2. BPJS Ketenagakerjaan Settings
 *    - JHT: 5.7% (3.7% perusahaan + 2% karyawan)
 *    - JKK: 0.24% (tingkat risiko sangat rendah - perkantoran)
 *    - JKM: 0.3%
 *    - JP: 3% (2% perusahaan + 1% karyawan) - max gaji Rp 10.042.300
 *
 * 3. BPJS Kesehatan Settings
 *    - Iuran: 5% (4% perusahaan + 1% karyawan)
 *    - Batas minimal: Rp 2.900.000 (UMK DKI Jakarta 2024)
 *    - Batas maksimal: Rp 12.000.000
 */
class DemoTaxBpjsSettingsSeeder extends Seeder
{
    private Company $company;

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat konfigurasi Pajak & BPJS untuk: {$this->company->name}");

        // Hapus data lama
        $this->cleanExistingData();

        // Buat settings
        $this->createPph21Settings();
        $this->createPph21Rates();
        $this->createPph21TerRates();
        $this->createBpjsTkSettings();
        $this->createBpjsKesSettings();

        $this->printSummary();
    }

    private function cleanExistingData(): void
    {
        $this->command->info('Menghapus data konfigurasi lama...');

        Pph21Setting::where('company_id', $this->company->id)->delete();
        Pph21Rate::where('company_id', $this->company->id)->delete();
        Pph21TerRate::where('company_id', $this->company->id)->delete();
        BpjsTkSetting::where('company_id', $this->company->id)->delete();
        BpjsKesSetting::where('company_id', $this->company->id)->delete();
    }

    private function createPph21Settings(): void
    {
        $this->command->info('Membuat PPh 21 Settings...');

        Pph21Setting::create([
            'company_id' => $this->company->id,
            'is_gross_up' => false, // Ditanggung karyawan
            'use_ter' => false, // Gunakan tarif progresif
            'npwp_discount_rate' => 20.00, // Penalti 20% bagi yang tidak punya NPWP
            'effective_year' => 2026,
            'notes' => 'Konfigurasi PPh 21 sesuai UU HPP No. 7 Tahun 2021. Menggunakan metode tarif progresif dengan beban pajak ditanggung karyawan.',
        ]);
    }

    private function createPph21Rates(): void
    {
        $this->command->info('Membuat PPh 21 Progressive Rates...');

        // Tarif PPh 21 sesuai UU HPP (berlaku sejak 2022)
        $progressiveRates = [
            [
                'min_amount' => 0,
                'max_amount' => 60000000,
                'rate' => 5,
            ],
            [
                'min_amount' => 60000000,
                'max_amount' => 250000000,
                'rate' => 15,
            ],
            [
                'min_amount' => 250000000,
                'max_amount' => 500000000,
                'rate' => 25,
            ],
            [
                'min_amount' => 500000000,
                'max_amount' => 5000000000,
                'rate' => 30,
            ],
            [
                'min_amount' => 5000000000,
                'max_amount' => null, // Tidak ada batas atas
                'rate' => 35,
            ],
        ];

        foreach ($progressiveRates as $rate) {
            Pph21Rate::create([
                'company_id' => $this->company->id,
                'min_amount' => $rate['min_amount'],
                'max_amount' => $rate['max_amount'],
                'rate' => $rate['rate'],
                'year' => 2026,
                'is_active' => true,
            ]);
        }

        $this->command->info('  - 5 layer tarif progresif dibuat');
    }

    private function createPph21TerRates(): void
    {
        $this->command->info('Membuat PPh 21 TER (Tarif Efektif Rata-rata)...');

        // TER Kategori A (TK/0, TK/1, K/0)
        $terCategoryA = [
            ['min_income' => 0, 'max_income' => 5400000, 'rate' => 0],
            ['min_income' => 5400000, 'max_income' => 5650000, 'rate' => 0.25],
            ['min_income' => 5650000, 'max_income' => 5950000, 'rate' => 0.50],
            ['min_income' => 5950000, 'max_income' => 6300000, 'rate' => 0.75],
            ['min_income' => 6300000, 'max_income' => 6750000, 'rate' => 1.00],
            ['min_income' => 6750000, 'max_income' => 7500000, 'rate' => 1.25],
            ['min_income' => 7500000, 'max_income' => 8550000, 'rate' => 1.50],
            ['min_income' => 8550000, 'max_income' => 9650000, 'rate' => 1.75],
            ['min_income' => 9650000, 'max_income' => 10050000, 'rate' => 2.00],
            ['min_income' => 10050000, 'max_income' => 10350000, 'rate' => 2.25],
            ['min_income' => 10350000, 'max_income' => 10700000, 'rate' => 2.50],
            ['min_income' => 10700000, 'max_income' => 11050000, 'rate' => 3.00],
            ['min_income' => 11050000, 'max_income' => 11600000, 'rate' => 3.50],
            ['min_income' => 11600000, 'max_income' => 12500000, 'rate' => 4.00],
            ['min_income' => 12500000, 'max_income' => 13750000, 'rate' => 5.00],
            ['min_income' => 13750000, 'max_income' => 15100000, 'rate' => 6.00],
            ['min_income' => 15100000, 'max_income' => 16950000, 'rate' => 7.00],
            ['min_income' => 16950000, 'max_income' => 19750000, 'rate' => 8.00],
            ['min_income' => 19750000, 'max_income' => 24150000, 'rate' => 9.00],
            ['min_income' => 24150000, 'max_income' => 26450000, 'rate' => 10.00],
            ['min_income' => 26450000, 'max_income' => 28000000, 'rate' => 11.00],
            ['min_income' => 28000000, 'max_income' => 30050000, 'rate' => 12.00],
            ['min_income' => 30050000, 'max_income' => 32400000, 'rate' => 13.00],
            ['min_income' => 32400000, 'max_income' => 35400000, 'rate' => 14.00],
            ['min_income' => 35400000, 'max_income' => 39100000, 'rate' => 15.00],
            ['min_income' => 39100000, 'max_income' => 43850000, 'rate' => 16.00],
            ['min_income' => 43850000, 'max_income' => 47800000, 'rate' => 17.00],
            ['min_income' => 47800000, 'max_income' => 51400000, 'rate' => 18.00],
            ['min_income' => 51400000, 'max_income' => 56300000, 'rate' => 19.00],
            ['min_income' => 56300000, 'max_income' => 62200000, 'rate' => 20.00],
            ['min_income' => 62200000, 'max_income' => 68600000, 'rate' => 21.00],
            ['min_income' => 68600000, 'max_income' => 77500000, 'rate' => 22.00],
            ['min_income' => 77500000, 'max_income' => 89000000, 'rate' => 23.00],
            ['min_income' => 89000000, 'max_income' => 103000000, 'rate' => 24.00],
            ['min_income' => 103000000, 'max_income' => 125000000, 'rate' => 25.00],
            ['min_income' => 125000000, 'max_income' => 157000000, 'rate' => 26.00],
            ['min_income' => 157000000, 'max_income' => 206000000, 'rate' => 27.00],
            ['min_income' => 206000000, 'max_income' => 337000000, 'rate' => 28.00],
            ['min_income' => 337000000, 'max_income' => 454000000, 'rate' => 29.00],
            ['min_income' => 454000000, 'max_income' => 550000000, 'rate' => 30.00],
            ['min_income' => 550000000, 'max_income' => 695000000, 'rate' => 31.00],
            ['min_income' => 695000000, 'max_income' => 910000000, 'rate' => 32.00],
            ['min_income' => 910000000, 'max_income' => 1400000000, 'rate' => 33.00],
            ['min_income' => 1400000000, 'max_income' => null, 'rate' => 34.00],
        ];

        // TER Kategori B (TK/2, TK/3, K/1, K/2)
        $terCategoryB = [
            ['min_income' => 0, 'max_income' => 6200000, 'rate' => 0],
            ['min_income' => 6200000, 'max_income' => 6500000, 'rate' => 0.25],
            ['min_income' => 6500000, 'max_income' => 6850000, 'rate' => 0.50],
            ['min_income' => 6850000, 'max_income' => 7300000, 'rate' => 0.75],
            ['min_income' => 7300000, 'max_income' => 9200000, 'rate' => 1.00],
            ['min_income' => 9200000, 'max_income' => 10750000, 'rate' => 1.50],
            ['min_income' => 10750000, 'max_income' => 11250000, 'rate' => 2.00],
            ['min_income' => 11250000, 'max_income' => 11600000, 'rate' => 2.50],
            ['min_income' => 11600000, 'max_income' => 12600000, 'rate' => 3.00],
            ['min_income' => 12600000, 'max_income' => 13600000, 'rate' => 4.00],
            ['min_income' => 13600000, 'max_income' => 14950000, 'rate' => 5.00],
            ['min_income' => 14950000, 'max_income' => 16400000, 'rate' => 6.00],
            ['min_income' => 16400000, 'max_income' => 18450000, 'rate' => 7.00],
            ['min_income' => 18450000, 'max_income' => 21850000, 'rate' => 8.00],
            ['min_income' => 21850000, 'max_income' => 26000000, 'rate' => 9.00],
            ['min_income' => 26000000, 'max_income' => 27700000, 'rate' => 10.00],
            ['min_income' => 27700000, 'max_income' => 29350000, 'rate' => 11.00],
            ['min_income' => 29350000, 'max_income' => 31450000, 'rate' => 12.00],
            ['min_income' => 31450000, 'max_income' => 33950000, 'rate' => 13.00],
            ['min_income' => 33950000, 'max_income' => 37100000, 'rate' => 14.00],
            ['min_income' => 37100000, 'max_income' => 41100000, 'rate' => 15.00],
            ['min_income' => 41100000, 'max_income' => 45800000, 'rate' => 16.00],
            ['min_income' => 45800000, 'max_income' => 49500000, 'rate' => 17.00],
            ['min_income' => 49500000, 'max_income' => 53800000, 'rate' => 18.00],
            ['min_income' => 53800000, 'max_income' => 58500000, 'rate' => 19.00],
            ['min_income' => 58500000, 'max_income' => 64000000, 'rate' => 20.00],
            ['min_income' => 64000000, 'max_income' => 71000000, 'rate' => 21.00],
            ['min_income' => 71000000, 'max_income' => 80000000, 'rate' => 22.00],
            ['min_income' => 80000000, 'max_income' => 93000000, 'rate' => 23.00],
            ['min_income' => 93000000, 'max_income' => 109000000, 'rate' => 24.00],
            ['min_income' => 109000000, 'max_income' => 129000000, 'rate' => 25.00],
            ['min_income' => 129000000, 'max_income' => 163000000, 'rate' => 26.00],
            ['min_income' => 163000000, 'max_income' => 211000000, 'rate' => 27.00],
            ['min_income' => 211000000, 'max_income' => 374000000, 'rate' => 28.00],
            ['min_income' => 374000000, 'max_income' => 459000000, 'rate' => 29.00],
            ['min_income' => 459000000, 'max_income' => 555000000, 'rate' => 30.00],
            ['min_income' => 555000000, 'max_income' => 704000000, 'rate' => 31.00],
            ['min_income' => 704000000, 'max_income' => 957000000, 'rate' => 32.00],
            ['min_income' => 957000000, 'max_income' => 1405000000, 'rate' => 33.00],
            ['min_income' => 1405000000, 'max_income' => null, 'rate' => 34.00],
        ];

        // TER Kategori C (K/3, K/I/0, K/I/1, K/I/2, K/I/3)
        $terCategoryC = [
            ['min_income' => 0, 'max_income' => 6600000, 'rate' => 0],
            ['min_income' => 6600000, 'max_income' => 6950000, 'rate' => 0.25],
            ['min_income' => 6950000, 'max_income' => 7350000, 'rate' => 0.50],
            ['min_income' => 7350000, 'max_income' => 7800000, 'rate' => 0.75],
            ['min_income' => 7800000, 'max_income' => 8850000, 'rate' => 1.00],
            ['min_income' => 8850000, 'max_income' => 9800000, 'rate' => 1.25],
            ['min_income' => 9800000, 'max_income' => 10950000, 'rate' => 1.50],
            ['min_income' => 10950000, 'max_income' => 11200000, 'rate' => 1.75],
            ['min_income' => 11200000, 'max_income' => 12050000, 'rate' => 2.00],
            ['min_income' => 12050000, 'max_income' => 12950000, 'rate' => 3.00],
            ['min_income' => 12950000, 'max_income' => 14150000, 'rate' => 4.00],
            ['min_income' => 14150000, 'max_income' => 15550000, 'rate' => 5.00],
            ['min_income' => 15550000, 'max_income' => 17050000, 'rate' => 6.00],
            ['min_income' => 17050000, 'max_income' => 19500000, 'rate' => 7.00],
            ['min_income' => 19500000, 'max_income' => 22700000, 'rate' => 8.00],
            ['min_income' => 22700000, 'max_income' => 26600000, 'rate' => 9.00],
            ['min_income' => 26600000, 'max_income' => 28100000, 'rate' => 10.00],
            ['min_income' => 28100000, 'max_income' => 30100000, 'rate' => 11.00],
            ['min_income' => 30100000, 'max_income' => 32600000, 'rate' => 12.00],
            ['min_income' => 32600000, 'max_income' => 35400000, 'rate' => 13.00],
            ['min_income' => 35400000, 'max_income' => 38900000, 'rate' => 14.00],
            ['min_income' => 38900000, 'max_income' => 43000000, 'rate' => 15.00],
            ['min_income' => 43000000, 'max_income' => 47400000, 'rate' => 16.00],
            ['min_income' => 47400000, 'max_income' => 51200000, 'rate' => 17.00],
            ['min_income' => 51200000, 'max_income' => 55800000, 'rate' => 18.00],
            ['min_income' => 55800000, 'max_income' => 60400000, 'rate' => 19.00],
            ['min_income' => 60400000, 'max_income' => 66700000, 'rate' => 20.00],
            ['min_income' => 66700000, 'max_income' => 74500000, 'rate' => 21.00],
            ['min_income' => 74500000, 'max_income' => 83200000, 'rate' => 22.00],
            ['min_income' => 83200000, 'max_income' => 95000000, 'rate' => 23.00],
            ['min_income' => 95000000, 'max_income' => 110000000, 'rate' => 24.00],
            ['min_income' => 110000000, 'max_income' => 134000000, 'rate' => 25.00],
            ['min_income' => 134000000, 'max_income' => 169000000, 'rate' => 26.00],
            ['min_income' => 169000000, 'max_income' => 221000000, 'rate' => 27.00],
            ['min_income' => 221000000, 'max_income' => 390000000, 'rate' => 28.00],
            ['min_income' => 390000000, 'max_income' => 463000000, 'rate' => 29.00],
            ['min_income' => 463000000, 'max_income' => 561000000, 'rate' => 30.00],
            ['min_income' => 561000000, 'max_income' => 709000000, 'rate' => 31.00],
            ['min_income' => 709000000, 'max_income' => 965000000, 'rate' => 32.00],
            ['min_income' => 965000000, 'max_income' => 1419000000, 'rate' => 33.00],
            ['min_income' => 1419000000, 'max_income' => null, 'rate' => 34.00],
        ];

        $ptkpByCategory = Pph21TerRate::getPtkpStatusByCategory();

        // Insert TER rates untuk setiap kategori
        foreach (['A' => $terCategoryA, 'B' => $terCategoryB, 'C' => $terCategoryC] as $category => $rates) {
            foreach ($rates as $rate) {
                Pph21TerRate::create([
                    'company_id' => $this->company->id,
                    'category' => $category,
                    'ptkp_status' => implode(',', $ptkpByCategory[$category]),
                    'min_income' => $rate['min_income'],
                    'max_income' => $rate['max_income'],
                    'rate' => $rate['rate'],
                    'year' => 2026,
                    'is_active' => true,
                ]);
            }
        }

        $countA = count($terCategoryA);
        $countB = count($terCategoryB);
        $countC = count($terCategoryC);
        $total = $countA + $countB + $countC;

        $this->command->info("  - Kategori A: {$countA} rates");
        $this->command->info("  - Kategori B: {$countB} rates");
        $this->command->info("  - Kategori C: {$countC} rates");
        $this->command->info("  - Total: {$total} TER rates dibuat");
    }

    private function createBpjsTkSettings(): void
    {
        $this->command->info('Membuat BPJS Ketenagakerjaan Settings...');

        // PT Demo GajiPro adalah perusahaan manufaktur dengan kantor pusat
        // Menggunakan tingkat risiko "very_low" untuk kantor pusat
        // Catatan: Pabrik akan menggunakan tingkat risiko lebih tinggi di produksi

        BpjsTkSetting::create([
            'company_id' => $this->company->id,

            // JHT (Jaminan Hari Tua) - Total 5.7%
            'jht_company_rate' => 3.70,
            'jht_employee_rate' => 2.00,
            'jht_enabled' => true,

            // JKK (Jaminan Kecelakaan Kerja) - Tingkat Risiko Sangat Rendah
            'jkk_rate' => 0.24,
            'jkk_risk_level' => 'very_low',
            'jkk_enabled' => true,

            // JKM (Jaminan Kematian)
            'jkm_rate' => 0.30,
            'jkm_enabled' => true,

            // JP (Jaminan Pensiun) - Total 3%
            'jp_company_rate' => 2.00,
            'jp_employee_rate' => 1.00,
            'jp_max_salary' => 10042300, // Batas atas upah JP tahun 2024
            'jp_enabled' => true,

            'max_salary_basis' => null, // Tidak ada batas untuk JHT, JKK, JKM
            'effective_year' => 2026,
            'notes' => 'Konfigurasi BPJS Ketenagakerjaan sesuai PP No. 37 Tahun 2021. Tingkat risiko JKK "Sangat Rendah" untuk perkantoran. Batas maksimal JP mengikuti ketentuan BPJS TK terbaru.',
            'is_active' => true,
        ]);
    }

    private function createBpjsKesSettings(): void
    {
        $this->command->info('Membuat BPJS Kesehatan Settings...');

        BpjsKesSetting::create([
            'company_id' => $this->company->id,
            'company_rate' => 4.00, // 4% ditanggung perusahaan
            'employee_rate' => 1.00, // 1% ditanggung karyawan
            'min_salary_basis' => 2900000, // UMK DKI Jakarta 2024 sebagai basis minimum
            'max_salary_basis' => 12000000, // Batas maksimal iuran
            'effective_year' => 2026,
            'notes' => 'Konfigurasi BPJS Kesehatan sesuai Perpres No. 64 Tahun 2020. Total iuran 5% dari upah dengan batas minimal UMK dan maksimal Rp 12.000.000.',
            'is_active' => true,
        ]);
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== KONFIGURASI PAJAK & BPJS PT DEMO GAJIPRO ===');
        $this->command->newLine();

        // PPh 21 Summary
        $pph21Setting = Pph21Setting::where('company_id', $this->company->id)->first();
        $pph21RatesCount = Pph21Rate::where('company_id', $this->company->id)->count();
        $pph21TerCount = Pph21TerRate::where('company_id', $this->company->id)->count();

        $this->command->info('PPh 21:');
        $this->command->line('  Metode: '.$pph21Setting->calculation_method_label);
        $this->command->line('  Beban Pajak: '.$pph21Setting->tax_burden_label);
        $this->command->line('  Penalti Non-NPWP: '.$pph21Setting->npwp_discount_rate.'%');
        $this->command->line('  Tarif Progresif: '.$pph21RatesCount.' layer');
        $this->command->line('  Tarif TER: '.$pph21TerCount.' rates (3 kategori)');
        $this->command->newLine();

        // Progressive rates detail
        $this->command->info('Tarif Progresif PPh 21:');
        $rates = Pph21Rate::where('company_id', $this->company->id)
            ->orderBy('min_amount')
            ->get();
        foreach ($rates as $rate) {
            $this->command->line("  {$rate->range_label}: {$rate->rate}%");
        }
        $this->command->newLine();

        // BPJS TK Summary
        $bpjsTk = BpjsTkSetting::where('company_id', $this->company->id)->first();

        $this->command->info('BPJS Ketenagakerjaan:');
        $this->command->line("  JHT: {$bpjsTk->jht_total_rate}% (Perusahaan: {$bpjsTk->jht_company_rate}%, Karyawan: {$bpjsTk->jht_employee_rate}%)");
        $this->command->line("  JKK: {$bpjsTk->jkk_rate}% ({$bpjsTk->jkk_risk_level_label})");
        $this->command->line("  JKM: {$bpjsTk->jkm_rate}%");
        $this->command->line("  JP: {$bpjsTk->jp_total_rate}% (Perusahaan: {$bpjsTk->jp_company_rate}%, Karyawan: {$bpjsTk->jp_employee_rate}%)");
        $this->command->line("  Batas Maks JP: {$bpjsTk->formatted_jp_max_salary}");
        $this->command->line("  Total Perusahaan: {$bpjsTk->total_company_rate}%");
        $this->command->line("  Total Karyawan: {$bpjsTk->total_employee_rate}%");
        $this->command->newLine();

        // BPJS Kesehatan Summary
        $bpjsKes = BpjsKesSetting::where('company_id', $this->company->id)->first();

        $this->command->info('BPJS Kesehatan:');
        $this->command->line("  Total Iuran: {$bpjsKes->total_rate}%");
        $this->command->line("  Perusahaan: {$bpjsKes->company_rate}%");
        $this->command->line("  Karyawan: {$bpjsKes->employee_rate}%");
        $this->command->line("  Batas Minimal: {$bpjsKes->formatted_min_salary_basis}");
        $this->command->line("  Batas Maksimal: {$bpjsKes->formatted_max_salary_basis}");
        $this->command->newLine();

        // Example calculation
        $this->command->info('=== CONTOH PERHITUNGAN (Gaji Rp 10.000.000) ===');

        $sampleSalary = 10000000;

        // BPJS TK calculation
        $bpjsTkEmployee = $bpjsTk->calculateEmployeeContribution($sampleSalary);
        $bpjsTkCompany = $bpjsTk->calculateCompanyContribution($sampleSalary);

        $this->command->line('BPJS Ketenagakerjaan:');
        $this->command->line('  Karyawan: Rp '.number_format($bpjsTkEmployee['total'], 0, ',', '.'));
        $this->command->line('    - JHT: Rp '.number_format($bpjsTkEmployee['jht'], 0, ',', '.'));
        $this->command->line('    - JP: Rp '.number_format($bpjsTkEmployee['jp'], 0, ',', '.'));
        $this->command->line('  Perusahaan: Rp '.number_format($bpjsTkCompany['total'], 0, ',', '.'));
        $this->command->line('    - JHT: Rp '.number_format($bpjsTkCompany['jht'], 0, ',', '.'));
        $this->command->line('    - JKK: Rp '.number_format($bpjsTkCompany['jkk'], 0, ',', '.'));
        $this->command->line('    - JKM: Rp '.number_format($bpjsTkCompany['jkm'], 0, ',', '.'));
        $this->command->line('    - JP: Rp '.number_format($bpjsTkCompany['jp'], 0, ',', '.'));

        // BPJS Kesehatan calculation
        $bpjsKesCalc = $bpjsKes->calculateContribution($sampleSalary);

        $this->command->line('BPJS Kesehatan:');
        $this->command->line('  Basis: Rp '.number_format($bpjsKesCalc['salary_basis'], 0, ',', '.'));
        $this->command->line('  Karyawan: Rp '.number_format($bpjsKesCalc['employee'], 0, ',', '.'));
        $this->command->line('  Perusahaan: Rp '.number_format($bpjsKesCalc['company'], 0, ',', '.'));
        $this->command->line('  Total: Rp '.number_format($bpjsKesCalc['total'], 0, ',', '.'));

        $this->command->newLine();
        $this->command->info('Konfigurasi Pajak & BPJS berhasil dibuat!');
    }
}
