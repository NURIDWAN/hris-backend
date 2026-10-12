<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk membuat lokasi kantor PT Demo GajiPro
 *
 * Lokasi Kantor:
 * ==============
 *
 * 1. Kantor Pusat (HQ) - Jakarta Selatan
 *    - BOD, Divisi Keuangan, Divisi HR, Divisi IT
 *
 * 2. Pabrik Cikarang - Bekasi
 *    - Divisi Operasional (Produksi, QC, Warehouse)
 *
 * 3. Kantor Cabang Bandung - Bandung
 *    - Divisi Sales & Marketing (regional Jawa Barat)
 *
 * 4. Kantor Cabang Surabaya - Surabaya
 *    - Divisi Sales & Marketing (regional Jawa Timur)
 *
 * 5. Gudang Tangerang - Tangerang
 *    - Warehouse tambahan untuk distribusi
 */
class DemoOfficeLocationSeeder extends Seeder
{
    private Company $company;

    private array $locations = [];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat lokasi kantor untuk: {$this->company->name}");

        // Hapus data lokasi lama
        $this->cleanExistingData();

        // Buat lokasi kantor
        $this->createOfficeLocations();

        // Assign karyawan ke lokasi
        $this->assignEmployeesToLocations();

        $this->command->info('Lokasi kantor berhasil dibuat!');
        $this->printLocationSummary();
    }

    private function cleanExistingData(): void
    {
        $this->command->info('Menghapus data lokasi kantor lama...');

        // Delete employee assignments first
        $locationIds = OfficeLocation::where('company_id', $this->company->id)->pluck('id');
        DB::table('employee_office_locations')->whereIn('office_location_id', $locationIds)->delete();

        // Delete locations
        OfficeLocation::where('company_id', $this->company->id)->forceDelete();
    }

    private function createOfficeLocations(): void
    {
        $this->command->info('Membuat lokasi kantor...');

        // 1. Kantor Pusat (Headquarters) - Jakarta Selatan
        $this->locations['HQ'] = OfficeLocation::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Pusat Jakarta',
            'code' => 'HQ-JKT',
            'address' => 'Gedung Graha GajiPro Lt. 15-18, Jl. Jend. Sudirman Kav. 52-53, Senayan',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2271460,
            'longitude' => 106.8080740,
            'radius' => 150, // meter
            'is_headquarters' => true,
            'is_active' => true,
        ]);

        // 2. Pabrik Cikarang - Bekasi
        $this->locations['FACTORY'] = OfficeLocation::create([
            'company_id' => $this->company->id,
            'name' => 'Pabrik Cikarang',
            'code' => 'FAC-CKR',
            'address' => 'Kawasan Industri Jababeka II, Jl. Industri Selatan Blok HH No. 5A',
            'city' => 'Cikarang, Bekasi',
            'province' => 'Jawa Barat',
            'latitude' => -6.3025890,
            'longitude' => 107.1467890,
            'radius' => 200,
            'is_headquarters' => false,
            'is_active' => true,
        ]);

        // 3. Kantor Cabang Bandung
        $this->locations['BDG'] = OfficeLocation::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Cabang Bandung',
            'code' => 'BR-BDG',
            'address' => 'Jl. Asia Afrika No. 141-149, Braga',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'latitude' => -6.9214740,
            'longitude' => 107.6072710,
            'radius' => 100,
            'is_headquarters' => false,
            'is_active' => true,
        ]);

        // 4. Kantor Cabang Surabaya
        $this->locations['SBY'] = OfficeLocation::create([
            'company_id' => $this->company->id,
            'name' => 'Kantor Cabang Surabaya',
            'code' => 'BR-SBY',
            'address' => 'Pakuwon Center Lt. 8, Jl. Embong Malang No. 1-5',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'latitude' => -7.2620330,
            'longitude' => 112.7527190,
            'radius' => 100,
            'is_headquarters' => false,
            'is_active' => true,
        ]);

        // 5. Gudang Tangerang
        $this->locations['WH-TNG'] = OfficeLocation::create([
            'company_id' => $this->company->id,
            'name' => 'Gudang Tangerang',
            'code' => 'WH-TNG',
            'address' => 'Kawasan Pergudangan Bandara Mas Blok A No. 10-12, Neglasari',
            'city' => 'Tangerang',
            'province' => 'Banten',
            'latitude' => -6.1445670,
            'longitude' => 106.6551230,
            'radius' => 150,
            'is_headquarters' => false,
            'is_active' => true,
        ]);

        $this->command->info('Total lokasi dibuat: '.count($this->locations));
    }

    private function assignEmployeesToLocations(): void
    {
        $this->command->info('Mengassign karyawan ke lokasi kantor...');

        // Mapping departemen ke lokasi
        $deptLocationMapping = [
            // Kantor Pusat (HQ) - BOD, Finance, HR, IT
            'BOD' => 'HQ',
            'DIV-FIN' => 'HQ',
            'DEPT-ACC' => 'HQ',
            'DEPT-FIN' => 'HQ',
            'DIV-HR' => 'HQ',
            'DEPT-REC' => 'HQ',
            'DEPT-PAY' => 'HQ',
            'DEPT-TND' => 'HQ',
            'DIV-IT' => 'HQ',
            'DEPT-DEV' => 'HQ',
            'DEPT-INF' => 'HQ',

            // Pabrik Cikarang - Operasional
            'DIV-OPS' => 'FACTORY',
            'DEPT-PRD' => 'FACTORY',
            'DEPT-QC' => 'FACTORY',

            // Marketing di HQ, tapi Sales tersebar
            'DIV-SM' => 'HQ',
            'DEPT-MKT' => 'HQ',
        ];

        // Get all departments with their codes
        $departments = Department::where('company_id', $this->company->id)
            ->get()
            ->keyBy('code');

        // Assign employees based on department
        foreach ($deptLocationMapping as $deptCode => $locationKey) {
            if (isset($departments[$deptCode]) && isset($this->locations[$locationKey])) {
                $dept = $departments[$deptCode];
                $location = $this->locations[$locationKey];

                $employees = Employee::where('company_id', $this->company->id)
                    ->where('department_id', $dept->id)
                    ->get();

                foreach ($employees as $employee) {
                    DB::table('employee_office_locations')->insert([
                        'employee_id' => $employee->id,
                        'office_location_id' => $location->id,
                        'is_primary' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Special handling untuk Dept. Warehouse - split between Factory and Tangerang
        $warehouseDept = $departments['DEPT-WH'] ?? null;
        if ($warehouseDept) {
            $warehouseEmployees = Employee::where('company_id', $this->company->id)
                ->where('department_id', $warehouseDept->id)
                ->get();

            foreach ($warehouseEmployees as $index => $employee) {
                // Manager dan Supervisor di Pabrik, sebagian staff di Tangerang
                if ($index < 2) {
                    // Manager dan Supervisor di Pabrik
                    $location = $this->locations['FACTORY'];
                } else {
                    // Staff split: some at factory, some at Tangerang warehouse
                    $location = $index % 2 === 0 ? $this->locations['FACTORY'] : $this->locations['WH-TNG'];
                }

                DB::table('employee_office_locations')->insert([
                    'employee_id' => $employee->id,
                    'office_location_id' => $location->id,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Special handling untuk Dept. Sales - split to branches
        $salesDept = $departments['DEPT-SLS'] ?? null;
        if ($salesDept) {
            $salesEmployees = Employee::where('company_id', $this->company->id)
                ->where('department_id', $salesDept->id)
                ->get();

            $salesLocations = ['HQ', 'BDG', 'SBY']; // Distribute across locations

            foreach ($salesEmployees as $index => $employee) {
                // Manager di HQ, sisanya tersebar
                if ($index === 0) {
                    $locationKey = 'HQ'; // Manager di HQ
                } else {
                    $locationKey = $salesLocations[$index % count($salesLocations)];
                }

                DB::table('employee_office_locations')->insert([
                    'employee_id' => $employee->id,
                    'office_location_id' => $this->locations[$locationKey]->id,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function printLocationSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== LOKASI KANTOR PT DEMO GAJIPRO ===');
        $this->command->newLine();

        foreach ($this->locations as $key => $location) {
            $employeeCount = DB::table('employee_office_locations')
                ->where('office_location_id', $location->id)
                ->count();

            $hqBadge = $location->is_headquarters ? ' [HEADQUARTERS]' : '';
            $this->command->line("  {$location->code} - {$location->name}{$hqBadge}");
            $this->command->line("       Alamat: {$location->address}");
            $this->command->line("       Kota: {$location->city}, {$location->province}");
            $this->command->line("       Koordinat: {$location->latitude}, {$location->longitude}");
            $this->command->line("       Radius: {$location->radius}m");
            $this->command->line("       Karyawan: {$employeeCount} orang");
            $this->command->newLine();
        }

        $this->command->info('=== RINGKASAN ===');
        $this->command->info('Total Lokasi: '.count($this->locations));

        $totalAssigned = DB::table('employee_office_locations')
            ->whereIn('office_location_id', collect($this->locations)->pluck('id'))
            ->count();
        $this->command->info("Total Karyawan Ter-assign: {$totalAssigned}");
    }
}
