<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk membuat struktur organisasi lengkap PT Demo GajiPro
 *
 * Struktur Organisasi:
 * ====================
 *
 * BOD (Board of Directors)
 * ├── CEO/Direktur Utama
 * │
 * ├── Divisi Keuangan & Akuntansi
 * │   ├── Dept. Akuntansi
 * │   │   ├── Manager Akuntansi
 * │   │   ├── Supervisor Akuntansi
 * │   │   └── Staff Akuntansi (2)
 * │   └── Dept. Keuangan
 * │       ├── Manager Keuangan
 * │       ├── Kasir
 * │       └── Staff Keuangan
 * │
 * ├── Divisi Human Resources
 * │   ├── Dept. Rekrutmen
 * │   │   ├── Manager Rekrutmen
 * │   │   └── Staff Rekrutmen (2)
 * │   ├── Dept. Payroll & Compensation
 * │   │   ├── Manager Payroll
 * │   │   └── Staff Payroll (2)
 * │   └── Dept. Training & Development
 * │       ├── Manager Training
 * │       └── Staff Training
 * │
 * ├── Divisi Operasional
 * │   ├── Dept. Produksi
 * │   │   ├── Manager Produksi
 * │   │   ├── Supervisor Produksi (2)
 * │   │   └── Operator Produksi (4)
 * │   ├── Dept. Quality Control
 * │   │   ├── Manager QC
 * │   │   ├── Supervisor QC
 * │   │   └── Staff QC (2)
 * │   └── Dept. Warehouse & Logistik
 * │       ├── Manager Warehouse
 * │       ├── Supervisor Gudang
 * │       └── Staff Gudang (3)
 * │
 * ├── Divisi Sales & Marketing
 * │   ├── Dept. Sales
 * │   │   ├── Manager Sales
 * │   │   ├── Supervisor Sales
 * │   │   └── Sales Executive (3)
 * │   └── Dept. Marketing
 * │       ├── Manager Marketing
 * │       ├── Digital Marketing Specialist
 * │       └── Staff Marketing
 * │
 * └── Divisi IT & Technology
 *     ├── Dept. Software Development
 *     │   ├── Manager Development
 *     │   ├── Senior Developer (2)
 *     │   └── Junior Developer (2)
 *     └── Dept. IT Infrastructure
 *         ├── Manager IT Infra
 *         ├── System Administrator
 *         └── IT Support (2)
 */
class DemoOrganizationStructureSeeder extends Seeder
{
    private Company $company;

    private array $departments = [];

    private array $positions = [];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat struktur organisasi untuk: {$this->company->name}");

        // Hapus data lama
        $this->cleanExistingData();

        // Buat struktur
        $this->createDepartments();
        $this->createPositions();
        $this->createEmployees();

        $this->command->info('Struktur organisasi berhasil dibuat!');
        $this->printOrganizationTree();
    }

    private function cleanExistingData(): void
    {
        $this->command->info('Menghapus data struktur organisasi lama...');

        // Delete employees first (karena ada foreign key)
        Employee::where('company_id', $this->company->id)->forceDelete();
        Position::where('company_id', $this->company->id)->forceDelete();
        Department::where('company_id', $this->company->id)->forceDelete();
    }

    private function createDepartments(): void
    {
        $this->command->info('Membuat departemen...');

        // Level 0: Board of Directors
        $bod = $this->createDepartment('Board of Directors', 'BOD', null, 'Dewan Direksi perusahaan');

        // Level 1: Divisi-divisi utama
        $divFinance = $this->createDepartment('Divisi Keuangan & Akuntansi', 'DIV-FIN', $bod->id, 'Divisi yang menangani keuangan dan akuntansi');
        $divHR = $this->createDepartment('Divisi Human Resources', 'DIV-HR', $bod->id, 'Divisi yang menangani sumber daya manusia');
        $divOps = $this->createDepartment('Divisi Operasional', 'DIV-OPS', $bod->id, 'Divisi yang menangani operasional perusahaan');
        $divSales = $this->createDepartment('Divisi Sales & Marketing', 'DIV-SM', $bod->id, 'Divisi yang menangani penjualan dan pemasaran');
        $divIT = $this->createDepartment('Divisi IT & Technology', 'DIV-IT', $bod->id, 'Divisi yang menangani teknologi informasi');

        // Level 2: Departemen di bawah Divisi Keuangan
        $this->createDepartment('Dept. Akuntansi', 'DEPT-ACC', $divFinance->id, 'Departemen akuntansi dan pelaporan keuangan');
        $this->createDepartment('Dept. Keuangan', 'DEPT-FIN', $divFinance->id, 'Departemen treasury dan cash management');

        // Level 2: Departemen di bawah Divisi HR
        $this->createDepartment('Dept. Rekrutmen', 'DEPT-REC', $divHR->id, 'Departemen rekrutmen dan seleksi karyawan');
        $this->createDepartment('Dept. Payroll & Compensation', 'DEPT-PAY', $divHR->id, 'Departemen penggajian dan kompensasi');
        $this->createDepartment('Dept. Training & Development', 'DEPT-TND', $divHR->id, 'Departemen pelatihan dan pengembangan karyawan');

        // Level 2: Departemen di bawah Divisi Operasional
        $this->createDepartment('Dept. Produksi', 'DEPT-PRD', $divOps->id, 'Departemen produksi dan manufaktur');
        $this->createDepartment('Dept. Quality Control', 'DEPT-QC', $divOps->id, 'Departemen pengendalian mutu');
        $this->createDepartment('Dept. Warehouse & Logistik', 'DEPT-WH', $divOps->id, 'Departemen gudang dan logistik');

        // Level 2: Departemen di bawah Divisi Sales & Marketing
        $this->createDepartment('Dept. Sales', 'DEPT-SLS', $divSales->id, 'Departemen penjualan');
        $this->createDepartment('Dept. Marketing', 'DEPT-MKT', $divSales->id, 'Departemen pemasaran dan branding');

        // Level 2: Departemen di bawah Divisi IT
        $this->createDepartment('Dept. Software Development', 'DEPT-DEV', $divIT->id, 'Departemen pengembangan software');
        $this->createDepartment('Dept. IT Infrastructure', 'DEPT-INF', $divIT->id, 'Departemen infrastruktur IT');
    }

    private function createDepartment(string $name, string $code, ?int $parentId, ?string $description = null): Department
    {
        $dept = Department::create([
            'company_id' => $this->company->id,
            'parent_id' => $parentId,
            'name' => $name,
            'code' => $code,
            'description' => $description,
            'is_active' => true,
        ]);

        $this->departments[$code] = $dept;

        return $dept;
    }

    private function createPositions(): void
    {
        $this->command->info('Membuat posisi/jabatan...');

        // BOD Positions
        $this->createPosition('BOD', 'Direktur Utama', 'DIR-CEO', 1, 75000000);
        $this->createPosition('BOD', 'Direktur Keuangan', 'DIR-CFO', 1, 60000000);
        $this->createPosition('BOD', 'Direktur Operasional', 'DIR-COO', 1, 60000000);
        $this->createPosition('BOD', 'Direktur HR', 'DIR-CHO', 1, 55000000);

        // Divisi Keuangan - Dept Akuntansi
        $this->createPosition('DEPT-ACC', 'Manager Akuntansi', 'MGR-ACC', 2, 25000000);
        $this->createPosition('DEPT-ACC', 'Supervisor Akuntansi', 'SPV-ACC', 3, 15000000);
        $this->createPosition('DEPT-ACC', 'Staff Akuntansi', 'STF-ACC', 4, 8000000);

        // Divisi Keuangan - Dept Keuangan
        $this->createPosition('DEPT-FIN', 'Manager Keuangan', 'MGR-FIN', 2, 25000000);
        $this->createPosition('DEPT-FIN', 'Kasir', 'STF-KSR', 4, 6500000);
        $this->createPosition('DEPT-FIN', 'Staff Keuangan', 'STF-FIN', 4, 7500000);

        // Divisi HR - Dept Rekrutmen
        $this->createPosition('DEPT-REC', 'Manager Rekrutmen', 'MGR-REC', 2, 22000000);
        $this->createPosition('DEPT-REC', 'Staff Rekrutmen', 'STF-REC', 4, 7000000);

        // Divisi HR - Dept Payroll
        $this->createPosition('DEPT-PAY', 'Manager Payroll', 'MGR-PAY', 2, 22000000);
        $this->createPosition('DEPT-PAY', 'Staff Payroll', 'STF-PAY', 4, 7500000);

        // Divisi HR - Dept Training
        $this->createPosition('DEPT-TND', 'Manager Training', 'MGR-TND', 2, 20000000);
        $this->createPosition('DEPT-TND', 'Staff Training', 'STF-TND', 4, 7000000);

        // Divisi Operasional - Dept Produksi
        $this->createPosition('DEPT-PRD', 'Manager Produksi', 'MGR-PRD', 2, 25000000);
        $this->createPosition('DEPT-PRD', 'Supervisor Produksi', 'SPV-PRD', 3, 12000000);
        $this->createPosition('DEPT-PRD', 'Operator Produksi', 'OPR-PRD', 5, 5500000);

        // Divisi Operasional - Dept QC
        $this->createPosition('DEPT-QC', 'Manager QC', 'MGR-QC', 2, 23000000);
        $this->createPosition('DEPT-QC', 'Supervisor QC', 'SPV-QC', 3, 12000000);
        $this->createPosition('DEPT-QC', 'Staff QC', 'STF-QC', 4, 6500000);

        // Divisi Operasional - Dept Warehouse
        $this->createPosition('DEPT-WH', 'Manager Warehouse', 'MGR-WH', 2, 20000000);
        $this->createPosition('DEPT-WH', 'Supervisor Gudang', 'SPV-WH', 3, 10000000);
        $this->createPosition('DEPT-WH', 'Staff Gudang', 'STF-WH', 5, 5500000);

        // Divisi Sales - Dept Sales
        $this->createPosition('DEPT-SLS', 'Manager Sales', 'MGR-SLS', 2, 28000000);
        $this->createPosition('DEPT-SLS', 'Supervisor Sales', 'SPV-SLS', 3, 15000000);
        $this->createPosition('DEPT-SLS', 'Sales Executive', 'STF-SLS', 4, 7000000);

        // Divisi Sales - Dept Marketing
        $this->createPosition('DEPT-MKT', 'Manager Marketing', 'MGR-MKT', 2, 26000000);
        $this->createPosition('DEPT-MKT', 'Digital Marketing Specialist', 'SPC-DGM', 4, 10000000);
        $this->createPosition('DEPT-MKT', 'Staff Marketing', 'STF-MKT', 4, 7000000);

        // Divisi IT - Dept Development
        $this->createPosition('DEPT-DEV', 'Manager Development', 'MGR-DEV', 2, 35000000);
        $this->createPosition('DEPT-DEV', 'Senior Developer', 'SNR-DEV', 3, 20000000);
        $this->createPosition('DEPT-DEV', 'Junior Developer', 'JNR-DEV', 4, 10000000);

        // Divisi IT - Dept Infrastructure
        $this->createPosition('DEPT-INF', 'Manager IT Infra', 'MGR-INF', 2, 30000000);
        $this->createPosition('DEPT-INF', 'System Administrator', 'SPC-SYS', 3, 15000000);
        $this->createPosition('DEPT-INF', 'IT Support', 'STF-ITS', 4, 7000000);
    }

    private function createPosition(string $deptCode, string $name, string $code, int $level, int $baseSalary): Position
    {
        $dept = $this->departments[$deptCode];

        $position = Position::create([
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'name' => $name,
            'code' => $code,
            'level' => $level,
            'base_salary' => $baseSalary,
            'is_active' => true,
        ]);

        $this->positions[$code] = $position;

        return $position;
    }

    private function createEmployees(): void
    {
        $this->command->info('Membuat karyawan...');

        $employeeNumber = 1;

        // BOD - Direktur
        $this->createEmployee('Budi', 'Santoso', 'DIR-CEO', 'K/3', '32.456.789.0-123.000', 36, $employeeNumber++);
        $this->createEmployee('Dewi', 'Kusuma', 'DIR-CFO', 'K/1', '32.456.789.0-124.000', 24, $employeeNumber++);
        $this->createEmployee('Agus', 'Wijaya', 'DIR-COO', 'K/2', '32.456.789.0-125.000', 30, $employeeNumber++);
        $this->createEmployee('Siti', 'Rahayu', 'DIR-CHO', 'K/1', '32.456.789.0-126.000', 24, $employeeNumber++);

        // Dept Akuntansi
        $this->createEmployee('Rina', 'Marlina', 'MGR-ACC', 'K/2', '32.456.789.0-127.000', 18, $employeeNumber++);
        $this->createEmployee('Hendra', 'Gunawan', 'SPV-ACC', 'K/1', '32.456.789.0-128.000', 12, $employeeNumber++);
        $this->createEmployee('Lina', 'Susanti', 'STF-ACC', 'TK/0', '32.456.789.0-129.000', 8, $employeeNumber++);
        $this->createEmployee('Doni', 'Pratama', 'STF-ACC', 'TK/0', '32.456.789.0-130.000', 6, $employeeNumber++);

        // Dept Keuangan
        $this->createEmployee('Bambang', 'Sudrajat', 'MGR-FIN', 'K/2', '32.456.789.0-131.000', 15, $employeeNumber++);
        $this->createEmployee('Yuni', 'Kartika', 'STF-KSR', 'TK/0', '32.456.789.0-132.000', 5, $employeeNumber++);
        $this->createEmployee('Eko', 'Prasetyo', 'STF-FIN', 'K/0', '32.456.789.0-133.000', 7, $employeeNumber++);

        // Dept Rekrutmen
        $this->createEmployee('Maya', 'Anggraini', 'MGR-REC', 'K/1', '32.456.789.0-134.000', 10, $employeeNumber++);
        $this->createEmployee('Fitri', 'Handayani', 'STF-REC', 'TK/0', '32.456.789.0-135.000', 4, $employeeNumber++);
        $this->createEmployee('Rizki', 'Ramadhan', 'STF-REC', 'TK/0', '32.456.789.0-136.000', 3, $employeeNumber++);

        // Dept Payroll
        $this->createEmployee('Nana', 'Suryani', 'MGR-PAY', 'K/2', '32.456.789.0-137.000', 12, $employeeNumber++);
        $this->createEmployee('Adi', 'Nugroho', 'STF-PAY', 'K/0', '32.456.789.0-138.000', 5, $employeeNumber++);
        $this->createEmployee('Putri', 'Ayu', 'STF-PAY', 'TK/0', '32.456.789.0-139.000', 4, $employeeNumber++);

        // Dept Training
        $this->createEmployee('Wahyu', 'Hidayat', 'MGR-TND', 'K/1', '32.456.789.0-140.000', 8, $employeeNumber++);
        $this->createEmployee('Sari', 'Dewi', 'STF-TND', 'TK/0', '32.456.789.0-141.000', 3, $employeeNumber++);

        // Dept Produksi
        $this->createEmployee('Joko', 'Susilo', 'MGR-PRD', 'K/3', '32.456.789.0-142.000', 20, $employeeNumber++);
        $this->createEmployee('Anton', 'Wijaya', 'SPV-PRD', 'K/1', '32.456.789.0-143.000', 10, $employeeNumber++);
        $this->createEmployee('Dedi', 'Kurniawan', 'SPV-PRD', 'K/1', '32.456.789.0-144.000', 9, $employeeNumber++);
        $this->createEmployee('Rudi', 'Hermawan', 'OPR-PRD', 'K/0', '32.456.789.0-145.000', 5, $employeeNumber++);
        $this->createEmployee('Tono', 'Saputra', 'OPR-PRD', 'TK/0', '32.456.789.0-146.000', 4, $employeeNumber++);
        $this->createEmployee('Bima', 'Putra', 'OPR-PRD', 'TK/0', '32.456.789.0-147.000', 3, $employeeNumber++);
        $this->createEmployee('Yanto', 'Sugiarto', 'OPR-PRD', 'K/1', '32.456.789.0-148.000', 6, $employeeNumber++);

        // Dept QC
        $this->createEmployee('Linda', 'Permata', 'MGR-QC', 'K/1', '32.456.789.0-149.000', 12, $employeeNumber++);
        $this->createEmployee('Andi', 'Firmansyah', 'SPV-QC', 'K/0', '32.456.789.0-150.000', 7, $employeeNumber++);
        $this->createEmployee('Nina', 'Safitri', 'STF-QC', 'TK/0', '32.456.789.0-151.000', 4, $employeeNumber++);
        $this->createEmployee('Fajar', 'Maulana', 'STF-QC', 'TK/0', '32.456.789.0-152.000', 3, $employeeNumber++);

        // Dept Warehouse
        $this->createEmployee('Herman', 'Suryadi', 'MGR-WH', 'K/2', '32.456.789.0-153.000', 10, $employeeNumber++);
        $this->createEmployee('Sugeng', 'Riyadi', 'SPV-WH', 'K/1', '32.456.789.0-154.000', 6, $employeeNumber++);
        $this->createEmployee('Dian', 'Lestari', 'STF-WH', 'TK/0', '32.456.789.0-155.000', 3, $employeeNumber++);
        $this->createEmployee('Udin', 'Saepudin', 'STF-WH', 'K/0', '32.456.789.0-156.000', 4, $employeeNumber++);
        $this->createEmployee('Asep', 'Suparman', 'STF-WH', 'TK/0', '32.456.789.0-157.000', 2, $employeeNumber++);

        // Dept Sales
        $this->createEmployee('Roni', 'Setiawan', 'MGR-SLS', 'K/2', '32.456.789.0-158.000', 14, $employeeNumber++);
        $this->createEmployee('Wulan', 'Sari', 'SPV-SLS', 'K/0', '32.456.789.0-159.000', 8, $employeeNumber++);
        $this->createEmployee('Adit', 'Pratama', 'STF-SLS', 'TK/0', '32.456.789.0-160.000', 4, $employeeNumber++);
        $this->createEmployee('Citra', 'Melati', 'STF-SLS', 'TK/0', '32.456.789.0-161.000', 3, $employeeNumber++);
        $this->createEmployee('Gilang', 'Ramadhan', 'STF-SLS', 'TK/0', '32.456.789.0-162.000', 2, $employeeNumber++);

        // Dept Marketing
        $this->createEmployee('Mega', 'Puspita', 'MGR-MKT', 'K/1', '32.456.789.0-163.000', 11, $employeeNumber++);
        $this->createEmployee('Kevin', 'Anggara', 'SPC-DGM', 'TK/0', '32.456.789.0-164.000', 5, $employeeNumber++);
        $this->createEmployee('Tania', 'Putri', 'STF-MKT', 'TK/0', '32.456.789.0-165.000', 3, $employeeNumber++);

        // Dept Development
        $this->createEmployee('Arif', 'Rahman', 'MGR-DEV', 'K/1', '32.456.789.0-166.000', 12, $employeeNumber++);
        $this->createEmployee('Bayu', 'Nugraha', 'SNR-DEV', 'K/0', '32.456.789.0-167.000', 7, $employeeNumber++);
        $this->createEmployee('Faisal', 'Akbar', 'SNR-DEV', 'TK/0', '32.456.789.0-168.000', 6, $employeeNumber++);
        $this->createEmployee('Dimas', 'Aditya', 'JNR-DEV', 'TK/0', '32.456.789.0-169.000', 2, $employeeNumber++);
        $this->createEmployee('Galih', 'Pratama', 'JNR-DEV', 'TK/0', '32.456.789.0-170.000', 1, $employeeNumber++);

        // Dept IT Infrastructure
        $this->createEmployee('Hendri', 'Wibowo', 'MGR-INF', 'K/2', '32.456.789.0-171.000', 10, $employeeNumber++);
        $this->createEmployee('Irfan', 'Hakim', 'SPC-SYS', 'K/0', '32.456.789.0-172.000', 6, $employeeNumber++);
        $this->createEmployee('Jefri', 'Kurnia', 'STF-ITS', 'TK/0', '32.456.789.0-173.000', 3, $employeeNumber++);
        $this->createEmployee('Lutfi', 'Andrean', 'STF-ITS', 'TK/0', '32.456.789.0-174.000', 2, $employeeNumber++);

        $this->command->info('Total karyawan dibuat: '.($employeeNumber - 1));
    }

    private function createEmployee(
        string $firstName,
        string $lastName,
        string $positionCode,
        string $taxStatus,
        string $npwp,
        int $monthsWorked,
        int $employeeNumber
    ): Employee {
        $position = $this->positions[$positionCode];
        $joinDate = Carbon::now()->subMonths($monthsWorked)->startOfMonth();

        // Generate salary with some variation (+/- 10%)
        $baseSalary = $position->base_salary;
        $salaryVariation = $baseSalary * (rand(-10, 10) / 100);
        $actualSalary = round(($baseSalary + $salaryVariation) / 100000) * 100000; // Round to nearest 100k

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'department_id' => $position->department_id,
            'position_id' => $position->id,
            'employee_id' => 'EMP'.str_pad($employeeNumber, 4, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName.'.'.$lastName).'@demo.gajipro.com',
            'phone' => '08'.rand(1000000000, 9999999999),
            'gender' => rand(0, 1) ? 'male' : 'female',
            'date_of_birth' => Carbon::now()->subYears(rand(25, 50))->subDays(rand(0, 365)),
            'religion' => $this->getRandomReligion(),
            'marital_status' => str_starts_with($taxStatus, 'K') ? 'married' : 'single',
            'address' => 'Jl. '.$this->getRandomStreet().' No. '.rand(1, 100).', '.$this->getRandomCity(),
            'city' => $this->getRandomCity(),
            'province' => 'DKI Jakarta',
            'hire_date' => $joinDate,
            'employment_status' => $monthsWorked >= 6 ? 'permanent' : 'contract',
            'tax_status' => $taxStatus,
            'npwp' => $npwp,
            'bpjs_kesehatan' => '000'.rand(10000000000, 99999999999),
            'bpjs_ketenagakerjaan' => rand(1000000000, 9999999999).''.rand(100, 999),
            'bank_name' => $this->getRandomBank(),
            'bank_account_number' => rand(1000000000, 9999999999),
            'bank_account_name' => strtoupper($firstName.' '.$lastName),
            'is_active' => true,
        ]);

        // Create salary record
        EmployeeSalary::create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'basic_salary' => $actualSalary,
            'effective_date' => $joinDate,
            'is_active' => true,
        ]);

        return $employee;
    }

    private function getRandomStreet(): string
    {
        $streets = [
            'Sudirman', 'Thamrin', 'Gatot Subroto', 'Kuningan', 'Rasuna Said',
            'Casablanca', 'Satrio', 'Mega Kuningan', 'Senopati', 'Kemang',
            'Fatmawati', 'Simatupang', 'Antasari', 'Prapanca', 'Wijaya',
            'Tendean', 'Wolter Monginsidi', 'Panglima Polim', 'Cipete', 'Radio Dalam',
        ];

        return $streets[array_rand($streets)];
    }

    private function getRandomCity(): string
    {
        $cities = ['Jakarta', 'Bandung', 'Surabaya', 'Semarang', 'Yogyakarta', 'Medan', 'Makassar', 'Palembang', 'Tangerang', 'Bekasi', 'Depok', 'Bogor'];

        return $cities[array_rand($cities)];
    }

    private function getRandomReligion(): string
    {
        $religions = ['islam', 'kristen', 'katolik', 'hindu', 'buddha'];
        $weights = [70, 12, 8, 5, 5]; // Approximate distribution

        $rand = rand(1, 100);
        $cumulative = 0;
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $religions[$i];
            }
        }

        return 'islam';
    }

    private function getRandomBank(): string
    {
        $banks = ['BCA', 'Mandiri', 'BNI', 'BRI', 'CIMB Niaga', 'Permata', 'Danamon'];

        return $banks[array_rand($banks)];
    }

    private function printOrganizationTree(): void
    {
        $this->command->newLine();
        $this->command->info('=== STRUKTUR ORGANISASI PT DEMO GAJIPRO ===');
        $this->command->newLine();

        $departments = Department::where('company_id', $this->company->id)
            ->whereNull('parent_id')
            ->with(['children.children', 'positions.employees'])
            ->get();

        foreach ($departments as $dept) {
            $this->printDepartmentTree($dept, 0);
        }

        $this->command->newLine();
        $this->command->info('=== RINGKASAN ===');
        $this->command->info('Total Departemen: '.Department::where('company_id', $this->company->id)->count());
        $this->command->info('Total Posisi: '.Position::where('company_id', $this->company->id)->count());
        $this->command->info('Total Karyawan: '.Employee::where('company_id', $this->company->id)->count());
    }

    private function printDepartmentTree(Department $dept, int $level): void
    {
        $indent = str_repeat('  ', $level);
        $prefix = $level > 0 ? '├── ' : '';

        $employeeCount = Employee::where('department_id', $dept->id)->count();
        $this->command->line("{$indent}{$prefix}{$dept->name} ({$dept->code}) - {$employeeCount} karyawan");

        // Print positions with employees
        $positions = Position::where('department_id', $dept->id)->withCount('employees')->get();
        foreach ($positions as $position) {
            $posIndent = str_repeat('  ', $level + 1);
            $this->command->line("{$posIndent}│   └ {$position->name}: {$position->employees_count} orang");
        }

        // Print children
        $children = Department::where('parent_id', $dept->id)->get();
        foreach ($children as $child) {
            $this->printDepartmentTree($child, $level + 1);
        }
    }
}
