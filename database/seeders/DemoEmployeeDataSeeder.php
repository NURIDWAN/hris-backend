<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeExit;
use App\Models\EmployeeSalary;
use App\Models\Position;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder untuk data karyawan lengkap PT Demo GajiPro
 *
 * Data yang dibuat:
 * =================
 *
 * 1. Struktur Organisasi
 *    - 18 Departemen (6 Divisi + 12 Dept)
 *    - 37 Posisi dengan grade level
 *
 * 2. Karyawan (52 orang aktif + 5 ex-karyawan)
 *    - Data personal lengkap (nama Indonesia realistis)
 *    - Data keluarga untuk yang sudah menikah
 *    - NPWP, BPJS, rekening bank
 *    - Foto profil placeholder
 *
 * 3. Exit Management (5 kasus)
 *    - 2 Resignation (1 completed, 1 pending)
 *    - 1 Contract End (completed)
 *    - 1 Retirement (completed)
 *    - 1 Termination (completed)
 *
 * Nama-nama menggunakan nama Indonesia yang umum dan realistis
 */
class DemoEmployeeDataSeeder extends Seeder
{
    private Company $company;

    private array $departments = [];

    private array $positions = [];

    private ?User $hrUser = null;

    private ?User $adminUser = null;

    // Nama-nama Indonesia yang realistis
    private array $maleFirstNames = [
        'Budi', 'Agus', 'Eko', 'Bambang', 'Dedi', 'Hendra', 'Joko', 'Rudi', 'Wahyu', 'Andi',
        'Fajar', 'Rizki', 'Dimas', 'Bayu', 'Arif', 'Galih', 'Irfan', 'Kevin', 'Lutfi', 'Gilang',
        'Anton', 'Tono', 'Yanto', 'Sugeng', 'Udin', 'Asep', 'Herman', 'Hendri', 'Jefri', 'Faisal',
        'Ahmad', 'Muhammad', 'Yoga', 'Rama', 'Surya', 'Angga', 'Dwi', 'Tri', 'Fikri', 'Reno',
    ];

    private array $femaleFirstNames = [
        'Siti', 'Dewi', 'Rina', 'Lina', 'Yuni', 'Maya', 'Fitri', 'Nana', 'Sari', 'Putri',
        'Linda', 'Nina', 'Dian', 'Wulan', 'Citra', 'Mega', 'Tania', 'Ayu', 'Sri', 'Ratna',
        'Ani', 'Erni', 'Wati', 'Yanti', 'Indah', 'Nurul', 'Lia', 'Rini', 'Intan', 'Kartika',
    ];

    private array $lastNames = [
        'Santoso', 'Wijaya', 'Kusuma', 'Rahayu', 'Pratama', 'Gunawan', 'Susanti', 'Sudrajat',
        'Prasetyo', 'Anggraini', 'Handayani', 'Ramadhan', 'Suryani', 'Nugroho', 'Hidayat',
        'Susilo', 'Kurniawan', 'Hermawan', 'Saputra', 'Putra', 'Sugiarto', 'Permata', 'Firmansyah',
        'Safitri', 'Maulana', 'Suryadi', 'Riyadi', 'Lestari', 'Saepudin', 'Suparman', 'Setiawan',
        'Melati', 'Puspita', 'Anggara', 'Rahman', 'Nugraha', 'Akbar', 'Aditya', 'Wibowo', 'Hakim',
        'Kurnia', 'Andrean', 'Marlina', 'Kartika', 'Wahyudi', 'Budiman', 'Hartono', 'Sutrisno',
    ];

    private array $usedNames = [];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat data karyawan untuk: {$this->company->name}");

        // Get HR and Admin users for approvals
        $this->hrUser = User::where('company_id', $this->company->id)
            ->where('email', 'hr@demo.gajipro.com')
            ->first();
        $this->adminUser = User::where('company_id', $this->company->id)
            ->where('email', 'admin@demo.gajipro.com')
            ->first();

        // Hapus data lama
        $this->cleanExistingData();

        // Buat struktur
        $this->createDepartments();
        $this->createPositions();
        $this->createEmployees();
        $this->createExitedEmployees();
        $this->createEmployeeExits();

        $this->printSummary();
    }

    private function cleanExistingData(): void
    {
        $this->command->info('Menghapus data karyawan lama...');

        // Delete in order of dependencies
        EmployeeExit::where('company_id', $this->company->id)->forceDelete();
        DB::table('employee_office_locations')
            ->whereIn('employee_id', Employee::where('company_id', $this->company->id)->pluck('id'))
            ->delete();
        EmployeeSalary::where('company_id', $this->company->id)->delete();
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
        $divFinance = $this->createDepartment('Divisi Keuangan & Akuntansi', 'DIV-FIN', $bod->id, 'Divisi yang menangani keuangan dan akuntansi perusahaan');
        $divHR = $this->createDepartment('Divisi Human Resources', 'DIV-HR', $bod->id, 'Divisi yang menangani sumber daya manusia');
        $divOps = $this->createDepartment('Divisi Operasional', 'DIV-OPS', $bod->id, 'Divisi yang menangani operasional dan produksi');
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

        $this->command->info('  - '.count($this->departments).' departemen dibuat');
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

        // BOD Positions (Level 1 - Executive)
        $this->createPosition('BOD', 'Direktur Utama', 'DIR-CEO', 1, 75000000, 'Memimpin dan mengarahkan seluruh operasional perusahaan');
        $this->createPosition('BOD', 'Direktur Keuangan', 'DIR-CFO', 1, 60000000, 'Bertanggung jawab atas keuangan dan akuntansi perusahaan');
        $this->createPosition('BOD', 'Direktur Operasional', 'DIR-COO', 1, 60000000, 'Bertanggung jawab atas operasional perusahaan');
        $this->createPosition('BOD', 'Direktur HR', 'DIR-CHO', 1, 55000000, 'Bertanggung jawab atas sumber daya manusia');

        // Divisi Keuangan - Dept Akuntansi
        $this->createPosition('DEPT-ACC', 'Manager Akuntansi', 'MGR-ACC', 2, 25000000, 'Mengelola tim akuntansi dan pelaporan keuangan');
        $this->createPosition('DEPT-ACC', 'Supervisor Akuntansi', 'SPV-ACC', 3, 15000000, 'Mengawasi operasional harian akuntansi');
        $this->createPosition('DEPT-ACC', 'Staff Akuntansi', 'STF-ACC', 4, 8000000, 'Melakukan pencatatan dan pelaporan akuntansi');

        // Divisi Keuangan - Dept Keuangan
        $this->createPosition('DEPT-FIN', 'Manager Keuangan', 'MGR-FIN', 2, 25000000, 'Mengelola treasury dan cash management');
        $this->createPosition('DEPT-FIN', 'Kasir', 'STF-KSR', 4, 6500000, 'Menangani transaksi kas harian');
        $this->createPosition('DEPT-FIN', 'Staff Keuangan', 'STF-FIN', 4, 7500000, 'Membantu operasional keuangan');

        // Divisi HR - Dept Rekrutmen
        $this->createPosition('DEPT-REC', 'Manager Rekrutmen', 'MGR-REC', 2, 22000000, 'Mengelola proses rekrutmen karyawan');
        $this->createPosition('DEPT-REC', 'Staff Rekrutmen', 'STF-REC', 4, 7000000, 'Melakukan sourcing dan screening kandidat');

        // Divisi HR - Dept Payroll
        $this->createPosition('DEPT-PAY', 'Manager Payroll', 'MGR-PAY', 2, 22000000, 'Mengelola penggajian dan kompensasi');
        $this->createPosition('DEPT-PAY', 'Staff Payroll', 'STF-PAY', 4, 7500000, 'Memproses penggajian karyawan');

        // Divisi HR - Dept Training
        $this->createPosition('DEPT-TND', 'Manager Training', 'MGR-TND', 2, 20000000, 'Mengelola program pelatihan karyawan');
        $this->createPosition('DEPT-TND', 'Staff Training', 'STF-TND', 4, 7000000, 'Melaksanakan program pelatihan');

        // Divisi Operasional - Dept Produksi
        $this->createPosition('DEPT-PRD', 'Manager Produksi', 'MGR-PRD', 2, 25000000, 'Mengelola operasional produksi');
        $this->createPosition('DEPT-PRD', 'Supervisor Produksi', 'SPV-PRD', 3, 12000000, 'Mengawasi lini produksi');
        $this->createPosition('DEPT-PRD', 'Operator Produksi', 'OPR-PRD', 5, 5500000, 'Mengoperasikan mesin produksi');

        // Divisi Operasional - Dept QC
        $this->createPosition('DEPT-QC', 'Manager QC', 'MGR-QC', 2, 23000000, 'Mengelola pengendalian mutu');
        $this->createPosition('DEPT-QC', 'Supervisor QC', 'SPV-QC', 3, 12000000, 'Mengawasi proses QC');
        $this->createPosition('DEPT-QC', 'Staff QC', 'STF-QC', 4, 6500000, 'Melakukan inspeksi kualitas');

        // Divisi Operasional - Dept Warehouse
        $this->createPosition('DEPT-WH', 'Manager Warehouse', 'MGR-WH', 2, 20000000, 'Mengelola gudang dan logistik');
        $this->createPosition('DEPT-WH', 'Supervisor Gudang', 'SPV-WH', 3, 10000000, 'Mengawasi operasional gudang');
        $this->createPosition('DEPT-WH', 'Staff Gudang', 'STF-WH', 5, 5500000, 'Menangani barang masuk dan keluar');

        // Divisi Sales - Dept Sales
        $this->createPosition('DEPT-SLS', 'Manager Sales', 'MGR-SLS', 2, 28000000, 'Mengelola tim penjualan');
        $this->createPosition('DEPT-SLS', 'Supervisor Sales', 'SPV-SLS', 3, 15000000, 'Mengawasi aktivitas penjualan');
        $this->createPosition('DEPT-SLS', 'Sales Executive', 'STF-SLS', 4, 7000000, 'Melakukan penjualan ke pelanggan');

        // Divisi Sales - Dept Marketing
        $this->createPosition('DEPT-MKT', 'Manager Marketing', 'MGR-MKT', 2, 26000000, 'Mengelola strategi pemasaran');
        $this->createPosition('DEPT-MKT', 'Digital Marketing Specialist', 'SPC-DGM', 4, 10000000, 'Mengelola pemasaran digital');
        $this->createPosition('DEPT-MKT', 'Staff Marketing', 'STF-MKT', 4, 7000000, 'Mendukung aktivitas pemasaran');

        // Divisi IT - Dept Development
        $this->createPosition('DEPT-DEV', 'Manager Development', 'MGR-DEV', 2, 35000000, 'Mengelola tim pengembangan software');
        $this->createPosition('DEPT-DEV', 'Senior Developer', 'SNR-DEV', 3, 20000000, 'Mengembangkan fitur kompleks');
        $this->createPosition('DEPT-DEV', 'Junior Developer', 'JNR-DEV', 4, 10000000, 'Mengembangkan fitur dasar');

        // Divisi IT - Dept Infrastructure
        $this->createPosition('DEPT-INF', 'Manager IT Infra', 'MGR-INF', 2, 30000000, 'Mengelola infrastruktur IT');
        $this->createPosition('DEPT-INF', 'System Administrator', 'SPC-SYS', 3, 15000000, 'Mengelola server dan jaringan');
        $this->createPosition('DEPT-INF', 'IT Support', 'STF-ITS', 4, 7000000, 'Memberikan dukungan teknis');

        $this->command->info('  - '.count($this->positions).' posisi dibuat');
    }

    private function createPosition(string $deptCode, string $name, string $code, int $level, int $baseSalary, ?string $description = null): Position
    {
        $dept = $this->departments[$deptCode];

        $position = Position::create([
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'name' => $name,
            'code' => $code,
            'level' => $level,
            'base_salary' => $baseSalary,
            'description' => $description,
            'is_active' => true,
        ]);

        $this->positions[$code] = $position;

        return $position;
    }

    private function createEmployees(): void
    {
        $this->command->info('Membuat karyawan aktif...');

        $employeeNumber = 1;

        // BOD - Direktur (senior executives, 5+ years)
        $this->createEmployee('M', 'Budi', 'Santoso', 'DIR-CEO', 'K/3', 72, $employeeNumber++, 52);
        $this->createEmployee('F', 'Dewi', 'Kusuma', 'DIR-CFO', 'K/1', 48, $employeeNumber++, 45);
        $this->createEmployee('M', 'Agus', 'Wijaya', 'DIR-COO', 'K/2', 60, $employeeNumber++, 48);
        $this->createEmployee('F', 'Sri', 'Rahayu', 'DIR-CHO', 'K/1', 48, $employeeNumber++, 44);

        // Dept Akuntansi
        $this->createEmployee('F', 'Rina', 'Marlina', 'MGR-ACC', 'K/2', 36, $employeeNumber++, 38);
        $this->createEmployee('M', 'Hendra', 'Gunawan', 'SPV-ACC', 'K/1', 24, $employeeNumber++, 32);
        $this->createEmployee('F', 'Lina', 'Susanti', 'STF-ACC', 'TK/0', 18, $employeeNumber++, 26);
        $this->createEmployee('M', 'Doni', 'Pratama', 'STF-ACC', 'TK/0', 12, $employeeNumber++, 24);

        // Dept Keuangan
        $this->createEmployee('M', 'Bambang', 'Sudrajat', 'MGR-FIN', 'K/2', 30, $employeeNumber++, 42);
        $this->createEmployee('F', 'Yuni', 'Kartika', 'STF-KSR', 'TK/0', 10, $employeeNumber++, 25);
        $this->createEmployee('M', 'Eko', 'Prasetyo', 'STF-FIN', 'K/0', 14, $employeeNumber++, 28);

        // Dept Rekrutmen
        $this->createEmployee('F', 'Maya', 'Anggraini', 'MGR-REC', 'K/1', 24, $employeeNumber++, 35);
        $this->createEmployee('F', 'Fitri', 'Handayani', 'STF-REC', 'TK/0', 8, $employeeNumber++, 26);
        $this->createEmployee('M', 'Rizki', 'Ramadhan', 'STF-REC', 'TK/0', 6, $employeeNumber++, 24);

        // Dept Payroll
        $this->createEmployee('F', 'Nana', 'Suryani', 'MGR-PAY', 'K/2', 24, $employeeNumber++, 36);
        $this->createEmployee('M', 'Adi', 'Nugroho', 'STF-PAY', 'K/0', 10, $employeeNumber++, 29);
        $this->createEmployee('F', 'Putri', 'Ayu', 'STF-PAY', 'TK/0', 8, $employeeNumber++, 25);

        // Dept Training
        $this->createEmployee('M', 'Wahyu', 'Hidayat', 'MGR-TND', 'K/1', 18, $employeeNumber++, 34);
        $this->createEmployee('F', 'Sari', 'Dewi', 'STF-TND', 'TK/0', 6, $employeeNumber++, 27);

        // Dept Produksi
        $this->createEmployee('M', 'Joko', 'Susilo', 'MGR-PRD', 'K/3', 40, $employeeNumber++, 46);
        $this->createEmployee('M', 'Anton', 'Wijaya', 'SPV-PRD', 'K/1', 20, $employeeNumber++, 35);
        $this->createEmployee('M', 'Dedi', 'Kurniawan', 'SPV-PRD', 'K/1', 18, $employeeNumber++, 33);
        $this->createEmployee('M', 'Rudi', 'Hermawan', 'OPR-PRD', 'K/0', 10, $employeeNumber++, 30);
        $this->createEmployee('M', 'Tono', 'Saputra', 'OPR-PRD', 'TK/0', 8, $employeeNumber++, 28);
        $this->createEmployee('M', 'Bima', 'Putra', 'OPR-PRD', 'TK/0', 6, $employeeNumber++, 25);
        $this->createEmployee('M', 'Yanto', 'Sugiarto', 'OPR-PRD', 'K/1', 12, $employeeNumber++, 32);

        // Dept QC
        $this->createEmployee('F', 'Linda', 'Permata', 'MGR-QC', 'K/1', 24, $employeeNumber++, 37);
        $this->createEmployee('M', 'Andi', 'Firmansyah', 'SPV-QC', 'K/0', 14, $employeeNumber++, 31);
        $this->createEmployee('F', 'Nina', 'Safitri', 'STF-QC', 'TK/0', 8, $employeeNumber++, 26);
        $this->createEmployee('M', 'Fajar', 'Maulana', 'STF-QC', 'TK/0', 6, $employeeNumber++, 24);

        // Dept Warehouse
        $this->createEmployee('M', 'Herman', 'Suryadi', 'MGR-WH', 'K/2', 20, $employeeNumber++, 40);
        $this->createEmployee('M', 'Sugeng', 'Riyadi', 'SPV-WH', 'K/1', 12, $employeeNumber++, 34);
        $this->createEmployee('F', 'Dian', 'Lestari', 'STF-WH', 'TK/0', 6, $employeeNumber++, 26);
        $this->createEmployee('M', 'Udin', 'Saepudin', 'STF-WH', 'K/0', 8, $employeeNumber++, 29);
        $this->createEmployee('M', 'Asep', 'Suparman', 'STF-WH', 'TK/0', 4, $employeeNumber++, 24);

        // Dept Sales
        $this->createEmployee('M', 'Roni', 'Setiawan', 'MGR-SLS', 'K/2', 28, $employeeNumber++, 39);
        $this->createEmployee('F', 'Wulan', 'Sari', 'SPV-SLS', 'K/0', 16, $employeeNumber++, 32);
        $this->createEmployee('M', 'Adit', 'Pratama', 'STF-SLS', 'TK/0', 8, $employeeNumber++, 27);
        $this->createEmployee('F', 'Citra', 'Melati', 'STF-SLS', 'TK/0', 6, $employeeNumber++, 25);
        $this->createEmployee('M', 'Gilang', 'Ramadhan', 'STF-SLS', 'TK/0', 4, $employeeNumber++, 24);

        // Dept Marketing
        $this->createEmployee('F', 'Mega', 'Puspita', 'MGR-MKT', 'K/1', 22, $employeeNumber++, 36);
        $this->createEmployee('M', 'Kevin', 'Anggara', 'SPC-DGM', 'TK/0', 10, $employeeNumber++, 28);
        $this->createEmployee('F', 'Tania', 'Putri', 'STF-MKT', 'TK/0', 6, $employeeNumber++, 25);

        // Dept Development
        $this->createEmployee('M', 'Arif', 'Rahman', 'MGR-DEV', 'K/1', 24, $employeeNumber++, 35);
        $this->createEmployee('M', 'Bayu', 'Nugraha', 'SNR-DEV', 'K/0', 14, $employeeNumber++, 30);
        $this->createEmployee('M', 'Faisal', 'Akbar', 'SNR-DEV', 'TK/0', 12, $employeeNumber++, 29);
        $this->createEmployee('M', 'Dimas', 'Aditya', 'JNR-DEV', 'TK/0', 4, $employeeNumber++, 24);
        $this->createEmployee('M', 'Galih', 'Pratama', 'JNR-DEV', 'TK/0', 2, $employeeNumber++, 23);

        // Dept IT Infrastructure
        $this->createEmployee('M', 'Hendri', 'Wibowo', 'MGR-INF', 'K/2', 20, $employeeNumber++, 38);
        $this->createEmployee('M', 'Irfan', 'Hakim', 'SPC-SYS', 'K/0', 12, $employeeNumber++, 31);
        $this->createEmployee('M', 'Jefri', 'Kurnia', 'STF-ITS', 'TK/0', 6, $employeeNumber++, 26);
        $this->createEmployee('M', 'Lutfi', 'Andrean', 'STF-ITS', 'TK/0', 4, $employeeNumber++, 24);

        $this->command->info('  - '.($employeeNumber - 1).' karyawan aktif dibuat');
    }

    private function createExitedEmployees(): void
    {
        $this->command->info('Membuat ex-karyawan...');

        // Ex-karyawan yang sudah tidak aktif
        $exitedCount = 0;

        // 1. Resign - pindah ke perusahaan lain (3 bulan lalu)
        $emp1 = $this->createEmployee('M', 'Yoga', 'Hartono', 'STF-SLS', 'TK/0', 18, 100, 27, false);
        $emp1->update([
            'is_active' => false,
            'resignation_date' => Carbon::now()->subMonths(3),
        ]);
        $exitedCount++;

        // 2. Contract End - kontrak tidak diperpanjang (2 bulan lalu)
        $emp2 = $this->createEmployee('F', 'Ratna', 'Dewi', 'STF-ACC', 'TK/0', 12, 101, 25, false);
        $emp2->update([
            'is_active' => false,
            'employment_status' => 'contract',
            'resignation_date' => Carbon::now()->subMonths(2),
        ]);
        $exitedCount++;

        // 3. Retirement - pensiun (4 bulan lalu)
        $emp3 = $this->createEmployee('M', 'Surya', 'Budiman', 'SPV-PRD', 'K/3', 180, 102, 56, false);
        $emp3->update([
            'is_active' => false,
            'resignation_date' => Carbon::now()->subMonths(4),
        ]);
        $exitedCount++;

        // 4. Termination - PHK karena pelanggaran (5 bulan lalu)
        $emp4 = $this->createEmployee('M', 'Reno', 'Sutrisno', 'STF-WH', 'K/0', 8, 103, 28, false);
        $emp4->update([
            'is_active' => false,
            'resignation_date' => Carbon::now()->subMonths(5),
        ]);
        $exitedCount++;

        // 5. Resign - pending approval (masih dalam proses)
        $emp5 = $this->createEmployee('F', 'Indah', 'Permatasari', 'STF-MKT', 'TK/0', 14, 104, 26, true);
        $exitedCount++;

        $this->command->info("  - {$exitedCount} ex-karyawan dibuat");
    }

    private function createEmployeeExits(): void
    {
        $this->command->info('Membuat data exit management...');

        $exitCount = 0;

        // 1. Resign - completed
        $emp1 = Employee::where('company_id', $this->company->id)
            ->where('employee_id', 'EMP0100')
            ->first();
        if ($emp1) {
            EmployeeExit::create([
                'company_id' => $this->company->id,
                'employee_id' => $emp1->id,
                'exit_type' => 'resignation',
                'request_date' => Carbon::now()->subMonths(4),
                'last_working_day' => Carbon::now()->subMonths(3)->endOfMonth(),
                'effective_date' => Carbon::now()->subMonths(3),
                'reason' => 'Mendapat tawaran pekerjaan dengan posisi dan kompensasi yang lebih baik di perusahaan lain.',
                'notes' => 'Karyawan resign dengan baik, sudah melakukan handover dengan lengkap.',
                'status' => 'completed',
                'approved_by' => $this->hrUser?->id,
                'approved_at' => Carbon::now()->subMonths(4)->addDays(3),
                'approval_notes' => 'Disetujui. Terima kasih atas kontribusinya selama bekerja.',
                'assets_returned' => true,
                'knowledge_transfer_done' => true,
                'final_settlement_done' => true,
                'exit_interview_done' => true,
                'exit_interview_notes' => 'Karyawan menyatakan alasan resign adalah untuk pengembangan karir. Tidak ada keluhan signifikan terhadap perusahaan.',
                'is_rehire_eligible' => true,
                'rehire_notes' => 'Performa baik selama bekerja, dapat dipertimbangkan untuk rehire.',
                'created_by' => $this->hrUser?->id,
            ]);
            $exitCount++;
        }

        // 2. Contract End - completed
        $emp2 = Employee::where('company_id', $this->company->id)
            ->where('employee_id', 'EMP0101')
            ->first();
        if ($emp2) {
            EmployeeExit::create([
                'company_id' => $this->company->id,
                'employee_id' => $emp2->id,
                'exit_type' => 'contract_end',
                'request_date' => Carbon::now()->subMonths(3),
                'last_working_day' => Carbon::now()->subMonths(2)->endOfMonth(),
                'effective_date' => Carbon::now()->subMonths(2),
                'reason' => 'Kontrak kerja berakhir dan tidak diperpanjang karena penyesuaian kebutuhan organisasi.',
                'notes' => 'Kontrak 1 tahun berakhir. Posisi tidak lagi dibutuhkan setelah implementasi sistem baru.',
                'status' => 'completed',
                'approved_by' => $this->hrUser?->id,
                'approved_at' => Carbon::now()->subMonths(3)->addDays(2),
                'approval_notes' => 'Kontrak tidak diperpanjang sesuai kebijakan perusahaan.',
                'assets_returned' => true,
                'knowledge_transfer_done' => true,
                'final_settlement_done' => true,
                'exit_interview_done' => true,
                'exit_interview_notes' => 'Karyawan memahami situasi. Tidak ada keluhan.',
                'is_rehire_eligible' => true,
                'rehire_notes' => 'Dapat dipertimbangkan untuk posisi lain jika ada kebutuhan.',
                'created_by' => $this->hrUser?->id,
            ]);
            $exitCount++;
        }

        // 3. Retirement - completed
        $emp3 = Employee::where('company_id', $this->company->id)
            ->where('employee_id', 'EMP0102')
            ->first();
        if ($emp3) {
            EmployeeExit::create([
                'company_id' => $this->company->id,
                'employee_id' => $emp3->id,
                'exit_type' => 'retirement',
                'request_date' => Carbon::now()->subMonths(6),
                'last_working_day' => Carbon::now()->subMonths(4)->endOfMonth(),
                'effective_date' => Carbon::now()->subMonths(4),
                'reason' => 'Memasuki usia pensiun normal (56 tahun) sesuai kebijakan perusahaan.',
                'notes' => 'Karyawan senior yang telah mengabdi selama 15 tahun. Diadakan acara perpisahan.',
                'status' => 'completed',
                'approved_by' => $this->adminUser?->id,
                'approved_at' => Carbon::now()->subMonths(6)->addDays(1),
                'approval_notes' => 'Disetujui. Terima kasih atas pengabdian selama 15 tahun.',
                'assets_returned' => true,
                'knowledge_transfer_done' => true,
                'final_settlement_done' => true,
                'exit_interview_done' => true,
                'exit_interview_notes' => 'Karyawan merasa puas dengan perjalanan karirnya di perusahaan. Memberikan saran untuk pengembangan tim produksi.',
                'is_rehire_eligible' => false,
                'rehire_notes' => 'N/A - Pensiun',
                'created_by' => $this->hrUser?->id,
            ]);
            $exitCount++;
        }

        // 4. Termination - completed
        $emp4 = Employee::where('company_id', $this->company->id)
            ->where('employee_id', 'EMP0103')
            ->first();
        if ($emp4) {
            EmployeeExit::create([
                'company_id' => $this->company->id,
                'employee_id' => $emp4->id,
                'exit_type' => 'termination',
                'request_date' => Carbon::now()->subMonths(5)->subDays(7),
                'last_working_day' => Carbon::now()->subMonths(5),
                'effective_date' => Carbon::now()->subMonths(5),
                'reason' => 'Pelanggaran berat: ketidakhadiran tanpa keterangan lebih dari 5 hari berturut-turut.',
                'notes' => 'Karyawan tidak masuk kerja tanpa keterangan selama 7 hari. Sudah diberikan surat peringatan 1, 2, dan 3.',
                'status' => 'completed',
                'approved_by' => $this->adminUser?->id,
                'approved_at' => Carbon::now()->subMonths(5)->subDays(5),
                'approval_notes' => 'PHK disetujui sesuai PP No. 35 Tahun 2021 tentang PKWT, Alih Daya, Waktu Kerja.',
                'assets_returned' => true,
                'knowledge_transfer_done' => false,
                'final_settlement_done' => true,
                'exit_interview_done' => false,
                'exit_interview_notes' => null,
                'is_rehire_eligible' => false,
                'rehire_notes' => 'Tidak eligible untuk rehire karena PHK akibat pelanggaran.',
                'created_by' => $this->hrUser?->id,
            ]);
            $exitCount++;
        }

        // 5. Resign - pending (masih dalam proses)
        $emp5 = Employee::where('company_id', $this->company->id)
            ->where('employee_id', 'EMP0104')
            ->first();
        if ($emp5) {
            EmployeeExit::create([
                'company_id' => $this->company->id,
                'employee_id' => $emp5->id,
                'exit_type' => 'resignation',
                'request_date' => Carbon::now()->subDays(7),
                'last_working_day' => Carbon::now()->addDays(23), // One month notice
                'effective_date' => Carbon::now()->addMonth(),
                'reason' => 'Akan melanjutkan studi S2 di luar negeri.',
                'notes' => 'Karyawan mengajukan resign untuk melanjutkan pendidikan.',
                'status' => 'approved',
                'approved_by' => $this->hrUser?->id,
                'approved_at' => Carbon::now()->subDays(5),
                'approval_notes' => 'Disetujui. Semoga sukses dengan studinya.',
                'assets_returned' => false,
                'knowledge_transfer_done' => false,
                'final_settlement_done' => false,
                'exit_interview_done' => false,
                'exit_interview_notes' => null,
                'is_rehire_eligible' => true,
                'rehire_notes' => 'Karyawan berpotensi, dapat dipertimbangkan setelah selesai studi.',
                'created_by' => $this->hrUser?->id,
            ]);
            $exitCount++;
        }

        $this->command->info("  - {$exitCount} data exit dibuat");
    }

    private function createEmployee(
        string $gender,
        string $firstName,
        string $lastName,
        string $positionCode,
        string $taxStatus,
        int $monthsWorked,
        int $employeeNumber,
        int $age,
        bool $isActive = true
    ): Employee {
        $position = $this->positions[$positionCode];
        $joinDate = Carbon::now()->subMonths($monthsWorked)->startOfMonth();

        // Generate salary with some variation (+/- 10%)
        $baseSalary = $position->base_salary;
        $salaryVariation = $baseSalary * (rand(-10, 10) / 100);
        $actualSalary = round(($baseSalary + $salaryVariation) / 100000) * 100000;

        $dateOfBirth = Carbon::now()->subYears($age)->subDays(rand(0, 365));
        $isMarried = str_starts_with($taxStatus, 'K');

        // Generate unique email
        $emailBase = strtolower($firstName.'.'.$lastName);
        $email = $emailBase.'@demo.gajipro.com';

        // Generate realistic Indonesian data
        $city = $this->getRandomCity();
        $province = $this->getProvinceForCity($city);

        $employee = Employee::create([
            'company_id' => $this->company->id,
            'department_id' => $position->department_id,
            'position_id' => $position->id,
            'employee_id' => 'EMP'.str_pad($employeeNumber, 4, '0', STR_PAD_LEFT),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $email,
            'phone' => $this->generatePhoneNumber(),
            'gender' => $gender === 'M' ? 'male' : 'female',
            'date_of_birth' => $dateOfBirth,
            'religion' => $this->getRandomReligion(),
            'marital_status' => $isMarried ? 'married' : 'single',
            'identity_number' => $this->generateNIK($dateOfBirth, $gender),
            'identity_address' => $this->generateAddress($this->getRandomCity()),
            'address' => $this->generateAddress($city),
            'city' => $city,
            'province' => $province,
            'postal_code' => $this->generatePostalCode(),
            'hire_date' => $joinDate,
            'employment_status' => $monthsWorked >= 6 ? 'permanent' : 'contract',
            'tax_status' => $taxStatus,
            'npwp' => $this->generateNPWP(),
            'bpjs_kesehatan' => $this->generateBPJSKes(),
            'bpjs_ketenagakerjaan' => $this->generateBPJSTK(),
            'bank_name' => $this->getRandomBank(),
            'bank_account_number' => $this->generateBankAccount(),
            'bank_account_name' => strtoupper($firstName.' '.$lastName),
            'emergency_contact_name' => $isMarried ? $this->generateSpouseName($gender) : $this->generateParentName($gender),
            'emergency_contact_phone' => $this->generatePhoneNumber(),
            'emergency_contact_relationship' => $isMarried ? ($gender === 'M' ? 'Istri' : 'Suami') : ($gender === 'M' ? 'Ibu' : 'Ayah'),
            'is_active' => $isActive,
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

    private function generatePhoneNumber(): string
    {
        $prefixes = ['0811', '0812', '0813', '0814', '0815', '0816', '0817', '0818', '0819', '0821', '0822', '0823', '0852', '0853', '0857', '0858'];
        $prefix = $prefixes[array_rand($prefixes)];

        return $prefix.rand(10000000, 99999999);
    }

    private function generateNPWP(): string
    {
        return sprintf(
            '%02d.%03d.%03d.%d-%03d.%03d',
            rand(10, 99),
            rand(100, 999),
            rand(100, 999),
            rand(0, 9),
            rand(100, 999),
            rand(0, 999)
        );
    }

    private function generateNIK(Carbon $dob, string $gender): string
    {
        $provinceCode = rand(31, 36); // Jabodetabek area
        $cityCode = rand(1, 79);
        $districtCode = rand(1, 99);

        $day = $dob->day;
        if ($gender === 'F') {
            $day += 40; // Female NIK has day + 40
        }

        return sprintf(
            '%02d%02d%02d%02d%02d%02d%04d',
            $provinceCode,
            $cityCode,
            $districtCode,
            $day,
            $dob->month,
            $dob->year % 100,
            rand(1, 9999)
        );
    }

    private function generateBPJSKes(): string
    {
        return '000'.rand(10000000000, 99999999999);
    }

    private function generateBPJSTK(): string
    {
        return rand(1000000000, 9999999999).''.rand(100, 999);
    }

    private function generateBankAccount(): string
    {
        return rand(1000000000, 9999999999);
    }

    private function generateAddress(string $city): string
    {
        $streets = [
            'Jl. Sudirman', 'Jl. Gatot Subroto', 'Jl. Thamrin', 'Jl. Kuningan',
            'Jl. Rasuna Said', 'Jl. Casablanca', 'Jl. Mangga Dua', 'Jl. Hayam Wuruk',
            'Jl. Pangeran Jayakarta', 'Jl. Gunung Sahari', 'Jl. Kemang Raya', 'Jl. Fatmawati',
            'Jl. Radio Dalam', 'Jl. Pejaten', 'Jl. Mampang Prapatan', 'Jl. Tebet Raya',
        ];

        $street = $streets[array_rand($streets)];
        $number = rand(1, 200);
        $rt = rand(1, 20);
        $rw = rand(1, 15);

        return "{$street} No. {$number}, RT {$rt}/RW {$rw}, {$city}";
    }

    private function generatePostalCode(): string
    {
        return rand(10000, 17999);
    }

    private function generateSpouseName(string $gender): string
    {
        if ($gender === 'M') {
            $firstName = $this->femaleFirstNames[array_rand($this->femaleFirstNames)];
        } else {
            $firstName = $this->maleFirstNames[array_rand($this->maleFirstNames)];
        }
        $lastName = $this->lastNames[array_rand($this->lastNames)];

        return $firstName.' '.$lastName;
    }

    private function generateParentName(string $gender): string
    {
        if ($gender === 'M') {
            $firstName = $this->femaleFirstNames[array_rand($this->femaleFirstNames)];
        } else {
            $firstName = $this->maleFirstNames[array_rand($this->maleFirstNames)];
        }
        $lastName = $this->lastNames[array_rand($this->lastNames)];

        return $firstName.' '.$lastName;
    }

    private function getRandomCity(): string
    {
        $cities = [
            'Jakarta Selatan', 'Jakarta Pusat', 'Jakarta Barat', 'Jakarta Timur', 'Jakarta Utara',
            'Tangerang', 'Tangerang Selatan', 'Bekasi', 'Depok', 'Bogor',
        ];

        return $cities[array_rand($cities)];
    }

    private function getProvinceForCity(string $city): string
    {
        if (str_contains($city, 'Jakarta')) {
            return 'DKI Jakarta';
        }
        if (in_array($city, ['Tangerang', 'Tangerang Selatan'])) {
            return 'Banten';
        }

        return 'Jawa Barat';
    }

    private function getRandomReligion(): string
    {
        $religions = ['islam', 'kristen', 'katolik', 'hindu', 'buddha', 'konghucu'];
        $weights = [70, 12, 8, 5, 4, 1];

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
        $banks = ['BCA', 'Mandiri', 'BNI', 'BRI', 'CIMB Niaga', 'Permata', 'Danamon', 'BTN'];
        $weights = [35, 25, 15, 10, 5, 5, 3, 2];

        $rand = rand(1, 100);
        $cumulative = 0;
        foreach ($weights as $i => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $banks[$i];
            }
        }

        return 'BCA';
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== DATA KARYAWAN PT DEMO GAJIPRO ===');
        $this->command->newLine();

        // Department summary
        $this->command->info('DEPARTEMEN:');
        $departments = Department::where('company_id', $this->company->id)
            ->whereNull('parent_id')
            ->with('children')
            ->get();

        foreach ($departments as $dept) {
            $employeeCount = Employee::where('department_id', $dept->id)->where('is_active', true)->count();
            $this->command->line("  {$dept->name} ({$dept->code}) - {$employeeCount} karyawan");

            foreach ($dept->children as $child) {
                $childCount = Employee::where('department_id', $child->id)->where('is_active', true)->count();
                $this->command->line("    └─ {$child->name} ({$child->code}) - {$childCount} karyawan");
            }
        }
        $this->command->newLine();

        // Employee statistics
        $totalActive = Employee::where('company_id', $this->company->id)->where('is_active', true)->count();
        $totalInactive = Employee::where('company_id', $this->company->id)->where('is_active', false)->count();
        $totalMale = Employee::where('company_id', $this->company->id)->where('is_active', true)->where('gender', 'male')->count();
        $totalFemale = Employee::where('company_id', $this->company->id)->where('is_active', true)->where('gender', 'female')->count();
        $totalPermanent = Employee::where('company_id', $this->company->id)->where('is_active', true)->where('employment_status', 'permanent')->count();
        $totalContract = Employee::where('company_id', $this->company->id)->where('is_active', true)->where('employment_status', 'contract')->count();

        $this->command->info('STATISTIK KARYAWAN:');
        $this->command->line("  Total Aktif: {$totalActive}");
        $this->command->line("  Total Non-Aktif: {$totalInactive}");
        $this->command->line("  Laki-laki: {$totalMale}");
        $this->command->line("  Perempuan: {$totalFemale}");
        $this->command->line("  Permanent: {$totalPermanent}");
        $this->command->line("  Kontrak: {$totalContract}");
        $this->command->newLine();

        // Exit management
        $exits = EmployeeExit::where('company_id', $this->company->id)->get();
        $this->command->info('EXIT MANAGEMENT:');
        foreach ($exits as $exit) {
            $this->command->line("  {$exit->employee->full_name} - {$exit->exit_type_label} ({$exit->status_label})");
        }
        $this->command->newLine();

        // Salary range
        $salaries = EmployeeSalary::where('company_id', $this->company->id)
            ->where('is_active', true)
            ->get();

        $minSalary = $salaries->min('basic_salary');
        $maxSalary = $salaries->max('basic_salary');
        $avgSalary = $salaries->avg('basic_salary');

        $this->command->info('RANGE GAJI:');
        $this->command->line('  Minimum: Rp '.number_format($minSalary, 0, ',', '.'));
        $this->command->line('  Maximum: Rp '.number_format($maxSalary, 0, ',', '.'));
        $this->command->line('  Rata-rata: Rp '.number_format($avgSalary, 0, ',', '.'));
        $this->command->newLine();

        $this->command->info('Data karyawan berhasil dibuat!');
    }
}
