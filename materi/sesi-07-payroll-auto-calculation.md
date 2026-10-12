# Sesi 7: Payroll Auto-Calculation Logic (PPh 21, BPJS, THR, Lembur)

> **Durasi**: 3-4 jam
> **Tanggal**: 22 April 2026 (Minggu 3)
> **Prasyarat**: GajiPro running, paham multi-tenant (Sesi 2-6), familiar dengan Employee & Salary Components
> **Tujuan**: Paham arsitektur payroll engine GajiPro secara end-to-end — dari input kehadiran sampai slip gaji final. Mampu menjelaskan rumus PPh 21 (TER & Progressive), BPJS, THR, dan Lembur berdasarkan kode yang berjalan di sistem.

---

## Daftar Isi

1. [Overview Payroll Engine](#1-overview-payroll-engine)
2. [Payroll Lifecycle (Workflow Status)](#2-payroll-lifecycle-workflow-status)
3. [Data yang Masuk ke Payroll](#3-data-yang-masuk-ke-payroll)
4. [Proses Kalkulasi Step-by-Step](#4-proses-kalkulasi-step-by-step)
5. [PPh 21 — Pajak Penghasilan](#5-pph-21--pajak-penghasilan)
6. [BPJS — Jaminan Sosial](#6-bpjs--jaminan-sosial)
7. [Lembur (Overtime)](#7-lembur-overtime)
8. [THR — Tunjangan Hari Raya](#8-thr--tunjangan-hari-raya)
9. [Potongan Otomatis (Telat & Absen)](#9-potongan-otomatis-telat--absen)
10. [Reimbursement dalam Payroll](#10-reimbursement-dalam-payroll)
11. [Contoh Simulasi Lengkap](#11-contoh-simulasi-lengkap)
12. [Arsitektur Kode (File Map)](#12-arsitektur-kode-file-map)
13. [Latihan Praktik](#13-latihan-praktik)

---

## 1. Overview Payroll Engine

GajiPro memiliki **payroll engine otomatis** yang menghitung gaji bulanan karyawan secara lengkap. Satu kali klik "Proses", semua komponen dihitung otomatis:

```
┌──────────────────────────────────────────────────────────────────────┐
│                        PAYROLL ENGINE                                │
│                                                                      │
│  INPUT:                          OUTPUT:                             │
│  ┌─────────────────┐            ┌──────────────────────────┐        │
│  │ Data Kehadiran   │            │ Gaji Kotor (Gross)       │        │
│  │ Komponen Gaji    │ ────────►  │ PPh 21                   │        │
│  │ Setting Lembur   │            │ BPJS (Kes + TK)          │        │
│  │ Setting Pajak    │            │ Potongan Lain             │        │
│  │ Setting BPJS     │            │ Gaji Bersih (Net)        │        │
│  │ Reimbursement    │            │ Slip Gaji Per Karyawan   │        │
│  └─────────────────┘            └──────────────────────────┘        │
└──────────────────────────────────────────────────────────────────────┘
```

### File Utama

| File | Fungsi |
|------|--------|
| `app/Http/Controllers/PayrollController.php` | Orchestrator — jalankan `process()` |
| `app/Services/PayrollCalculationService.php` | Hitung PPh 21 + BPJS |
| `app/Services/OvertimeCalculationService.php` | Hitung lembur |
| `app/Services/ThrCalculationService.php` | Hitung THR (terpisah dari payroll bulanan) |

---

## 2. Payroll Lifecycle (Workflow Status)

Setiap payroll punya **lifecycle status** yang ketat:

```
 ┌───────┐     ┌────────────┐     ┌────────────┐     ┌──────────┐     ┌──────┐
 │ Draft │────►│ Processing │────►│ Calculated │────►│ Approved │────►│ Paid │
 └───────┘     └────────────┘     └────────────┘     └──────────┘     └──────┘
     │                                   │                │
     │              ┌────────────┐       │                │
     └─────────────►│ Cancelled  │◄──────┴────────────────┘
                    └────────────┘
```

### Guard Methods (Model `Payroll`)

```php
// File: app/Models/Payroll.php

public function canBeCalculated(): bool
{
    return $this->status === 'draft';
}

public function canBeApproved(): bool
{
    return $this->status === 'calculated';
}

public function canBePaid(): bool
{
    return $this->status === 'approved';
}

public function canBeCancelled(): bool
{
    return in_array($this->status, ['draft', 'calculated', 'approved']);
}
```

**Penting**: Payroll yang sudah `paid` TIDAK bisa dibatalkan. Ini by design — karena sudah ada implikasi keuangan riil.

### Payroll Number Format

```
PAY{company_id_4digit}{YYYYMM}{sequence_3digit}
Contoh: PAY0001202604001
```

### Constraint Unik

Satu perusahaan hanya bisa punya **satu payroll per bulan**:

```php
$table->unique(['company_id', 'period_month', 'period_year']);
```

---

## 3. Data yang Masuk ke Payroll

### 3.1 Data Kehadiran (Attendance)

Setiap hari, sistem mencatat clock-in/clock-out karyawan. Data ini diagregasi saat payroll diproses:

```php
// File: app/Http/Controllers/PayrollController.php → calculateAttendanceData()

$attendanceData = [
    'working_days'    => 22,   // Hari kerja dalam periode
    'present_days'    => 20,   // Hadir (termasuk telat)
    'absent_days'     => 1,    // Tidak hadir
    'late_days'       => 3,    // Hadir tapi telat
    'half_days'       => 0,    // Setengah hari
    'leave_days'      => 1,    // Cuti (approved)
    'overtime_hours'  => 8.5,  // Total jam lembur
];
```

**Cara hitung `working_days`**: Iterasi setiap hari dalam periode → cek jadwal karyawan (`resolveScheduleForDate()`). Kalau tidak punya jadwal, default Senin-Jumat.

**Cara hitung `overtime_hours`**: Dari `attendances.overtime_minutes` yang dicatat otomatis saat clock-out melewati jadwal selesai.

### 3.2 Komponen Gaji (Salary Components)

Setiap karyawan punya **satu gaji aktif** (`EmployeeSalary`) yang berisi:

```
EmployeeSalary (is_active = true)
├── basic_salary: Rp 10.000.000
├── EmployeeSalaryComponent #1: Tunjangan Jabatan (earning) = Rp 2.000.000
├── EmployeeSalaryComponent #2: Tunjangan Transport (earning, attendance-based) = Rp 500.000
├── EmployeeSalaryComponent #3: BPJS Kesehatan (deduction) = auto-calculated
└── ...
```

#### Komponen Berbasis Kehadiran (Attendance-Based)

Ada komponen yang jumlahnya dihitung berdasarkan kehadiran:

```php
// File: app/Models/SalaryComponent.php

// Mode "daily": hitung per hari hadir
$amount = $dailyRate * $effectivePresentDays;

// Mode "monthly": gaji bulanan dikurangi hari absen
$amount = $monthlyAmount - ($dailyRate * $absentDays);
```

**Contoh**: Tunjangan Transport Rp 500.000/bulan (attendance-based, mode monthly)
- Karyawan A hadir 22/22 hari → dapat Rp 500.000
- Karyawan B absen 3 hari → dapat Rp 500.000 - (500.000/22 × 3) = Rp 431.818

### 3.3 Setting Payroll

```php
// File: app/Models/PayrollSetting.php

PayrollSetting::where('company_id', $tenant->id)->first();

// Berisi konfigurasi:
// - auto_deduct_late (bool)
// - late_penalty_type ('fixed', 'percentage', 'daily_rate')
// - late_penalty_amount (float)
// - late_penalty_threshold (int) → minimal berapa hari telat baru kena potongan
// - auto_deduct_absent (bool)
// - absent_deduction_type ('daily_rate', 'fixed', 'percentage')
// - absent_deduction_amount (float)
// - include_overtime_in_payroll (bool)
// - include_reimbursement_in_payroll (bool)
```

---

## 4. Proses Kalkulasi Step-by-Step

Ini adalah urutan eksak yang terjadi ketika admin klik **"Proses Payroll"**:

```php
// File: app/Http/Controllers/PayrollController.php → process()
// Semua dalam DB::transaction() — kalau gagal, rollback semua
```

### Step 1: Ambil Semua Karyawan Aktif

```php
$employees = Employee::where('company_id', $payroll->company_id)
    ->where('is_active', true)
    ->with(['currentSalary.components.salaryComponent', 'department', 'position'])
    ->get();
```

**Catatan**: Hanya karyawan yang punya `currentSalary` (gaji aktif) yang diproses. Yang belum di-setup gajinya → di-skip.

### Step 2: Per Karyawan — Hitung Attendance Data

```php
$attendanceData = $this->calculateAttendanceData(
    $employee,
    Carbon::parse($payroll->period_start),
    Carbon::parse($payroll->period_end)
);
```

### Step 3: Hitung Salary Components

Loop semua `EmployeeSalaryComponent`:

```php
foreach ($salary->components as $component) {
    $salaryComponent = $component->salaryComponent;
    $amount = $component->amount;

    // Kalau attendance-based → recalculate berdasarkan kehadiran
    if ($salaryComponent->isAttendanceBased()) {
        $amount = $salaryComponent->calculateAttendanceBasedAmount(
            $attendanceForComponents, $component->amount
        );
    }

    if ($salaryComponent->type === 'earning') {
        $totalEarnings += $amount;
        if ($salaryComponent->is_taxable) {
            $taxableEarnings += $amount;
        }
    } else {
        $totalComponentDeductions += $amount;
    }
}
```

### Step 4: Hitung Lembur (jika aktif)

```php
if ($overtimeSetting && $payrollSetting->include_overtime_in_payroll && $overtimeHours > 0) {
    $overtimeData = $this->overtimeService->calculate(
        $employee, $overtimeHours, 'weekday', $overtimeSetting
    );
    $overtimeAmount = $overtimeData['overtime_amount'];
    $totalEarnings += $overtimeAmount;     // Lembur = earning
    $taxableEarnings += $overtimeAmount;   // Lembur = kena pajak
}
```

### Step 5: Hitung Denda Telat

```php
if ($payrollSetting->auto_deduct_late && $lateDays >= $threshold) {
    $latePenalty = match ($setting->late_penalty_type) {
        'fixed'      => $penaltyAmount * $lateDays,
        'percentage' => ($basicSalary * ($penaltyAmount / 100)) * $lateDays,
        'daily_rate' => ($basicSalary / $workingDays) * $lateDays,
    };
    $totalComponentDeductions += $latePenalty;
}
```

### Step 6: Hitung Potongan Absen

```php
if ($payrollSetting->auto_deduct_absent && $absentDays > 0) {
    $absentDeduction = match ($setting->absent_deduction_type) {
        'daily_rate'  => ($basicSalary / $workingDays) * $absentDays,
        'fixed'       => $deductionAmount * $absentDays,
        'percentage'  => ($basicSalary * ($deductionAmount / 100)) * $absentDays,
    };
    $totalComponentDeductions += $absentDeduction;
}
```

### Step 7: Reimbursement (Non-Taxable)

```php
$reimbursements = Reimbursement::where('employee_id', $employee->id)
    ->where('status', 'approved')
    ->whereNull('payroll_item_id')  // Belum pernah dibayar
    ->where('approved_at', '<=', $payroll->period_end)
    ->get();

$reimbursementAmount = $reimbursements->sum('amount');
$totalEarnings += $reimbursementAmount;
// TIDAK ditambahkan ke $taxableEarnings → reimbursement bebas pajak
```

### Step 8: Hitung Gaji Kotor

```php
$grossSalary = $basicSalary + $totalEarnings;
```

**Formula**:
```
Gross Salary = Gaji Pokok + Tunjangan + Lembur + Reimbursement
```

### Step 9: Hitung PPh 21 + BPJS

```php
$calculation = $this->calculationService->calculate($employee, [
    'gross_salary'     => $grossSalary,
    'basic_salary'     => $basicSalary,
    'total_earnings'   => $totalEarnings,
    'taxable_earnings' => $taxableEarnings,
    'total_deductions' => $totalComponentDeductions,
], $payroll->company_id);
```

### Step 10: Hitung Gaji Bersih

```php
$totalDeductions = $totalComponentDeductions + $bpjsKesEmployee + $bpjsTkEmployee;
$netSalary = $grossSalary - $totalDeductions - $taxAmount;
```

**Formula Final**:
```
Net Salary = Gross Salary - Potongan Komponen - BPJS (porsi karyawan) - PPh 21
```

### Step 11: Simpan PayrollItem + Details

Untuk setiap karyawan, dibuat:
- 1 `PayrollItem` — summary per karyawan
- N `PayrollItemDetail` — rincian per komponen (tunjangan, potongan, BPJS, lembur, dll)

---

## 5. PPh 21 — Pajak Penghasilan

### File: `app/Services/PayrollCalculationService.php`

GajiPro mendukung **dua metode** perhitungan PPh 21:

### 5.1 Metode TER (Tarif Efektif Rata-rata) — Default

Mulai 2024, pemerintah Indonesia menerapkan metode TER yang lebih sederhana. Rumusnya:

```
PPh 21 = Gross Salary × TER Rate
```

**Langkah-langkah**:

#### a) Tentukan Kategori TER

Berdasarkan status PTKP karyawan:

| Kategori | Status PTKP |
|----------|-------------|
| **A** | TK/0, TK/1, K/0 |
| **B** | TK/2, TK/3, K/1, K/2 |
| **C** | K/3, K/I/0, K/I/1, K/I/2, K/I/3 |

```php
// File: PayrollCalculationService.php → getTerCategoryByPtkpStatus()

$categoryA = ['TK/0', 'TK/1', 'K/0'];
$categoryB = ['TK/2', 'TK/3', 'K/1', 'K/2'];
$categoryC = ['K/3', 'K/I/0', 'K/I/1', 'K/I/2', 'K/I/3'];
```

**Keterangan status**:
- **TK** = Tidak Kawin, angka = jumlah tanggungan
- **K** = Kawin, angka = jumlah tanggungan
- **K/I** = Kawin + istri bekerja (digabung penghasilannya)

#### b) Cari Rate dari Tabel TER

```php
$terRate = Pph21TerRate::where('company_id', $companyId)
    ->where('category', $category)        // A, B, atau C
    ->where('is_active', true)
    ->where('min_income', '<=', $grossSalary)
    ->where(function ($query) use ($grossSalary) {
        $query->whereNull('max_income')
            ->orWhere('max_income', '>=', $grossSalary);
    })
    ->first();
```

**Contoh Tabel TER Kategori A**:

| Range Penghasilan Bruto | TER Rate |
|-------------------------|----------|
| Rp 0 - Rp 5.400.000 | 0.00% |
| Rp 5.400.001 - Rp 5.650.000 | 0.25% |
| Rp 5.650.001 - Rp 5.950.000 | 0.50% |
| Rp 5.950.001 - Rp 6.300.000 | 0.75% |
| Rp 6.300.001 - Rp 6.750.000 | 1.00% |
| ... | ... |
| Rp 10.050.001 - Rp 10.350.000 | 3.00% |
| ... | ... |
| > Rp 1.400.000.000 | 34.00% |

#### c) Hitung PPh 21

```php
$pph21 = $grossSalary * ($terRate / 100);

// Contoh: Gaji bruto Rp 10.000.000, TER 2.50%
// PPh 21 = 10.000.000 × 2.50% = Rp 250.000
```

### 5.2 Metode Progresif (Fallback)

Jika metode TER tidak aktif atau tidak ditemukan rate yang cocok:

```
PPh 21 = ((Gross - PTKP_bulanan) × 12 → tarif progresif) ÷ 12
```

#### a) PTKP (Penghasilan Tidak Kena Pajak) — 2024

```php
// File: PayrollCalculationService.php → getPtkpMonthly()

$ptkpAnnual = match ($status) {
    'TK/0' => 54_000_000,    // Rp 54 juta/tahun = Rp 4.5 juta/bulan
    'TK/1' => 58_500_000,
    'TK/2' => 63_000_000,
    'TK/3' => 67_500_000,
    'K/0'  => 58_500_000,
    'K/1'  => 63_000_000,
    'K/2'  => 67_500_000,
    'K/3'  => 72_000_000,
    'K/I/0' => 112_500_000,
    'K/I/1' => 117_000_000,
    'K/I/2' => 121_500_000,
    'K/I/3' => 126_000_000,
};
```

#### b) Penghasilan Kena Pajak (PKP)

```php
$pkp = max(0, $grossSalary - $ptkpMonthly);
$annualPkp = $pkp * 12;
```

**Contoh**: Gaji Rp 10.000.000, status TK/0
```
PTKP bulanan = 54.000.000 / 12 = Rp 4.500.000
PKP bulanan = 10.000.000 - 4.500.000 = Rp 5.500.000
PKP tahunan = 5.500.000 × 12 = Rp 66.000.000
```

#### c) Tarif Progresif (UU HPP 2024)

```
┌─────────────────────────────────┬────────┐
│ Lapisan PKP Tahunan             │ Tarif  │
├─────────────────────────────────┼────────┤
│ Rp 0 - Rp 60.000.000           │   5%   │
│ Rp 60.000.001 - Rp 250.000.000 │  15%   │
│ Rp 250.000.001 - Rp 500.000.000│  25%   │
│ Rp 500.000.001 - Rp 5 Miliar   │  30%   │
│ > Rp 5 Miliar                   │  35%   │
└─────────────────────────────────┴────────┘
```

**Lanjutan contoh** (PKP tahunan = Rp 66.000.000):
```
Lapisan 1: 60.000.000 × 5%  = Rp 3.000.000
Lapisan 2:  6.000.000 × 15% = Rp   900.000
                               ────────────
Total pajak tahunan           = Rp 3.900.000
PPh 21 bulanan                = 3.900.000 / 12 = Rp 325.000
```

### 5.3 Penalti Tanpa NPWP

Karyawan yang **tidak punya NPWP** dikenakan pajak lebih tinggi:

```php
if (!$hasNpwp) {
    $npwpPenalty = $settings->npwp_discount_rate ?? 20; // Default 20%
    $pph21 = $pph21 * (1 + ($npwpPenalty / 100));
}

// Contoh: PPh 21 = Rp 250.000, tanpa NPWP
// PPh 21 final = 250.000 × 1.20 = Rp 300.000
```

### 5.4 Decision Tree PPh 21

```
┌─ Ada Pph21Setting? ──────────────────────────────┐
│                                                    │
├─ TIDAK → fallback: gross × 5%                     │
│                                                    │
├─ YA, use_ter = true ─┐                            │
│                       ├─ TER rate ditemukan → PPh 21 = gross × TER_rate
│                       └─ Tidak ditemukan → fallback ke progresif
│                                                    │
└─ YA, use_ter = false → hitung progresif            │
                                                     │
                    └─ Punya NPWP? ──┐               │
                                     ├─ YA → selesai │
                                     └─ TIDAK → × 1.2│
```

---

## 6. BPJS — Jaminan Sosial

### File: `app/Services/PayrollCalculationService.php`

BPJS terbagi dua program besar:

### 6.1 BPJS Kesehatan

Iuran jaminan kesehatan nasional (JKN).

```php
// File: app/Models/BpjsKesSetting.php → calculateContribution()

$calculationBasis = clamp($grossSalary, $minSalaryBasis, $maxSalaryBasis);
$company  = $calculationBasis × (company_rate / 100);
$employee = $calculationBasis × (employee_rate / 100);
```

| Parameter | Default (2024) |
|-----------|---------------|
| Company Rate | **4%** |
| Employee Rate | **1%** |
| Min Salary Basis | Rp 2.900.000 (≈ UMK) |
| Max Salary Basis | Rp 12.000.000 |

**Penting**: Ada batas atas (cap) Rp 12.000.000. Jadi karyawan bergaji Rp 50 juta tetap bayar BPJS Kes berdasarkan Rp 12 juta.

**Contoh**: Gaji bruto Rp 10.000.000
```
Salary Basis    = Rp 10.000.000 (dalam range min-max)
BPJS Kes (perusahaan) = 10.000.000 × 4% = Rp 400.000
BPJS Kes (karyawan)   = 10.000.000 × 1% = Rp 100.000  ← dipotong dari gaji
```

### 6.2 BPJS Ketenagakerjaan

Terdiri dari **4 program**:

```
┌─────────────────────────────────────────────────────────────────┐
│                    BPJS KETENAGAKERJAAN                          │
├─────────────────────┬──────────────┬──────────────┬─────────────┤
│ Program             │ Karyawan     │ Perusahaan   │ Catatan     │
├─────────────────────┼──────────────┼──────────────┼─────────────┤
│ JHT (Hari Tua)      │    2.00%     │    3.70%     │             │
│ JP  (Pensiun)       │    1.00%     │    2.00%     │ Salary-cap  │
│ JKK (Kecelakaan)    │      -       │ 0.24-1.74%   │ Risk-based  │
│ JKM (Kematian)      │      -       │    0.30%     │             │
├─────────────────────┼──────────────┼──────────────┼─────────────┤
│ TOTAL (min)         │    3.00%     │    6.24%     │             │
│ TOTAL (max)         │    3.00%     │    7.74%     │             │
└─────────────────────┴──────────────┴──────────────┴─────────────┘
```

**Yang dipotong dari gaji karyawan**: Hanya **JHT + JP** = 3%

**Yang ditanggung perusahaan**: JHT + JP + JKK + JKM = 6.24% - 7.74% (tergantung risiko)

#### JP (Jaminan Pensiun) — Salary Cap

JP punya batas upah maksimal:

```php
// File: app/Models/BpjsTkSetting.php

$jpSalary = min($grossSalary, $this->jp_max_salary);
// Default jp_max_salary = Rp 10.042.300 (2024)

$jpEmployee = $jpSalary * ($this->jp_employee_rate / 100);
$jpCompany  = $jpSalary * ($this->jp_company_rate / 100);
```

**Contoh**: Gaji Rp 15.000.000 → JP dihitung dari Rp 10.042.300 (cap)
```
JP karyawan = 10.042.300 × 1% = Rp 100.423
JP perusahaan = 10.042.300 × 2% = Rp 200.846
```

#### JKK (Jaminan Kecelakaan Kerja) — Risk-Based

Rate JKK tergantung tingkat risiko industri perusahaan:

```php
// File: app/Models/JkkRiskRate.php

| Tingkat Risiko   | Contoh Industri      | Rate   |
|------------------|---------------------|--------|
| very_low (I)     | Kantor, jasa        | 0.24%  |
| low (II)         | Retail, perdagangan | 0.54%  |
| medium (III)     | Manufaktur          | 0.89%  |
| high (IV)        | Konstruksi          | 1.27%  |
| very_high (V)    | Pertambangan        | 1.74%  |
```

#### Contoh Lengkap BPJS TK (Gaji Rp 10.000.000, risiko rendah)

```
JHT karyawan  = 10.000.000 × 2.00% = Rp 200.000  ← potong gaji
JHT perusahaan = 10.000.000 × 3.70% = Rp 370.000

JP karyawan   = 10.000.000 × 1.00% = Rp 100.000   ← potong gaji
JP perusahaan  = 10.000.000 × 2.00% = Rp 200.000

JKK perusahaan = 10.000.000 × 0.24% = Rp  24.000
JKM perusahaan = 10.000.000 × 0.30% = Rp  30.000

TOTAL potong karyawan  = Rp 300.000 (JHT + JP)
TOTAL beban perusahaan = Rp 624.000 (JHT + JP + JKK + JKM)
```

### 6.3 Ringkasan BPJS dalam Payroll

Di sistem, BPJS dicatat sebagai `PayrollItemDetail` terpisah:

```php
// BPJS Kesehatan → category: 'bpjs', is_taxable: false
$payrollItem->addDetail(null, 'BPJS Kesehatan', 'BPJS-KES', 'deduction', 'bpjs', $bpjsKesEmployee, false);

// BPJS JHT → category: 'bpjs', is_taxable: false
$payrollItem->addDetail(null, 'BPJS JHT', 'BPJS-JHT', 'deduction', 'bpjs', $bpjsTkJhtEmployee, false);

// BPJS JP → category: 'bpjs', is_taxable: false
$payrollItem->addDetail(null, 'BPJS JP', 'BPJS-JP', 'deduction', 'bpjs', $bpjsTkJpEmployee, false);
```

**BPJS tidak kena pajak** (is_taxable = false).

---

## 7. Lembur (Overtime)

### File: `app/Services/OvertimeCalculationService.php`

### 7.1 Menghitung Upah Per Jam

Berdasarkan regulasi ketenagakerjaan Indonesia, standar jam kerja per bulan = **173 jam**.

```php
// File: app/Models/OvertimeSetting.php → calculateHourlyRate()

$hourlyRate = $monthlySalary / $workingHoursPerMonth;

// Contoh: Rp 10.000.000 / 173 = Rp 57.803/jam
```

### 7.2 Rumus Lembur per Tipe

#### Hari Kerja (Weekday)

Jam pertama punya rate berbeda dari jam berikutnya:

```php
// Jam ke-1:         hourlyRate × 1.5
// Jam ke-2 dst:     hourlyRate × 2.0 × (hours - 1)

public function calculateWeekdayOvertime(float $hourlyRate, float $hours): float
{
    $amount = $hourlyRate * $this->weekday_rate_first_hour;         // 1.5x

    if ($hours > 1) {
        $amount += $hourlyRate * $this->weekday_rate_next_hours * ($hours - 1);  // 2.0x
    }

    return round($amount, 2);
}
```

**Contoh**: Lembur 3 jam hari kerja (hourly rate = Rp 57.803)
```
Jam 1:     57.803 × 1.5 = Rp  86.705
Jam 2-3:   57.803 × 2.0 × 2 = Rp 231.212
                                ───────────
Total lembur               = Rp 317.917
```

#### Hari Libur / Weekend

```php
$amount = $hourlyRate × $weekendRate × $hours;
// Default: 2.0x flat untuk semua jam

// Contoh: 3 jam weekend
// 57.803 × 2.0 × 3 = Rp 346.818
```

#### Hari Libur Nasional (Holiday)

```php
$amount = $hourlyRate × $holidayRate × $hours;
// Default: 3.0x flat untuk semua jam

// Contoh: 3 jam holiday
// 57.803 × 3.0 × 3 = Rp 520.227
```

### 7.3 Default Multiplier

| Tipe | Jam 1 | Jam 2+ |
|------|-------|--------|
| Weekday | 1.5× | 2.0× |
| Weekend | 2.0× | 2.0× |
| Holiday | 3.0× | 3.0× |

### 7.4 Batas Maksimal Lembur

```php
// OvertimeSetting
'max_overtime_hours_per_day'  => 4,   // Maks 4 jam/hari
'max_overtime_hours_per_week' => 14,  // Maks 14 jam/minggu
```

### 7.5 Dua Jalur Lembur Masuk Payroll

```
1. OTOMATIS (dari attendance)
   Clock-out melewati jadwal → overtime_minutes tercatat otomatis
   Diagregasi saat payroll → type 'weekday' (default)

2. MANUAL (via OvertimeRequest)
   Karyawan submit request → HR approve
   Bisa pilih type: weekday / weekend / holiday
   Amount sudah dihitung saat request
```

### 7.6 Lembur Kena Pajak

```php
$totalEarnings += $overtimeAmount;
$taxableEarnings += $overtimeAmount;  // Overtime IS taxable
```

Lembur masuk penghasilan bruto dan **dikenakan PPh 21**.

---

## 8. THR — Tunjangan Hari Raya

### File: `app/Services/ThrCalculationService.php`

**THR dihitung TERPISAH** dari payroll bulanan. THR punya controller dan flow sendiri.

### 8.1 Syarat Kelayakan

```php
$serviceMonths = $employee->hire_date->diffInMonths(now());

if ($serviceMonths < $setting->min_service_months) {
    // Tidak eligible
    return ['eligible' => false, 'reason' => 'Masa kerja kurang'];
}
```

Default: Minimal **1 bulan** masa kerja.

### 8.2 Metode Perhitungan

#### a) Satu Bulan Gaji (one_month_salary)

```php
if ($setting->calculation_method === 'one_month_salary') {
    $thrAmount = $totalSalary; // = basic_salary + allowances
}
```

Karyawan yang masa kerjanya **≥ 12 bulan** biasanya dapat THR penuh = 1 bulan gaji.

#### b) Prorata (untuk masa kerja < 12 bulan)

```php
if ($setting->calculation_method === 'prorata') {
    $monthsForCalculation = min($serviceMonths, 12);
    $thrAmount = ($monthsForCalculation / 12) * $totalSalary;
}
```

**Contoh**: Karyawan baru 6 bulan, gaji Rp 10.000.000
```
THR = (6 / 12) × 10.000.000 = Rp 5.000.000
```

### 8.3 Include Allowances?

Setting `include_allowances` menentukan apakah tunjangan tetap masuk perhitungan THR:

```php
$baseSalary = $salary->basic_salary;
$allowances = 0;

if ($setting->include_allowances) {
    $allowances = $salary->getTotalEarnings(); // Semua komponen earning
}

$totalSalary = $baseSalary + $allowances;
```

**Contoh**:
```
Gaji Pokok          = Rp 10.000.000
Tunjangan Jabatan   = Rp  2.000.000
Tunjangan Transport = Rp    500.000

include_allowances = true  → THR = Rp 12.500.000
include_allowances = false → THR = Rp 10.000.000
```

### 8.4 Model ThrPayment

```php
// File: app/Models/ThrPayment.php

// Unique constraint → 1 karyawan hanya dapat 1 THR per tahun per hari raya
$table->unique(['company_id', 'employee_id', 'year', 'religious_holiday']);

// Status: pending → paid / cancelled
// Religious holidays: idul_fitri, christmas, nyepi, waisak, imlek
```

### 8.5 Diagram THR

```
ThrSetting (per company)
├── calculation_method: 'one_month_salary' atau 'prorata'
├── prorata_formula: 'months_worked_per_12'
├── min_service_months: 1
├── include_allowances: true/false
│
└── ThrPayment (per employee per year per holiday)
    ├── employee_id
    ├── year: 2026
    ├── religious_holiday: 'idul_fitri'
    ├── thr_amount: Rp 10.000.000
    ├── status: 'pending' → 'paid'
    └── paid_at: timestamp
```

---

## 9. Potongan Otomatis (Telat & Absen)

### 9.1 Denda Keterlambatan

Dikonfigurasi via `PayrollSetting`:

```php
// Hanya berlaku jika:
// 1. auto_deduct_late = true
// 2. lateDays >= late_penalty_threshold (misal: 3 hari)

$latePenalty = match ($setting->late_penalty_type) {
    'fixed'      => $penaltyAmount * $lateDays,
    'percentage' => ($basicSalary * ($penaltyAmount / 100)) * $lateDays,
    'daily_rate' => ($basicSalary / $workingDays) * $lateDays,
};
```

**Contoh** (gaji Rp 10.000.000, 22 hari kerja, telat 4 hari):

| Tipe | Setting | Perhitungan | Potongan |
|------|---------|-------------|----------|
| fixed | Rp 50.000/hari | 50.000 × 4 | Rp 200.000 |
| percentage | 0.5% | (10.000.000 × 0.5%) × 4 | Rp 200.000 |
| daily_rate | — | (10.000.000 / 22) × 4 | Rp 1.818.182 |

### 9.2 Potongan Absen

```php
$absentDeduction = match ($setting->absent_deduction_type) {
    'daily_rate'  => ($basicSalary / $workingDays) * $absentDays,
    'fixed'       => $deductionAmount * $absentDays,
    'percentage'  => ($basicSalary * ($deductionAmount / 100)) * $absentDays,
};
```

**Catatan penting**: Potongan absen ini berlaku untuk karyawan dengan **gaji tetap (fixed)**. Karyawan yang gajinya sudah attendance-based tidak perlu potongan ini (sudah ter-handle di komponen gaji).

---

## 10. Reimbursement dalam Payroll

Reimbursement yang sudah di-approve akan **otomatis masuk** ke payroll berikutnya:

```php
$reimbursements = Reimbursement::where('employee_id', $employee->id)
    ->where('status', 'approved')
    ->whereNull('payroll_item_id')       // Belum pernah dibayar
    ->where('approved_at', '<=', $payroll->period_end)
    ->get();
```

**Karakteristik**:
- Masuk sebagai **earning** (menambah gross salary)
- **TIDAK kena pajak** (is_taxable = false) → karena ini penggantian biaya
- Setelah masuk payroll, reimbursement di-mark `paid` dan di-link ke `payroll_item_id`

---

## 11. Contoh Simulasi Lengkap

### Data Karyawan: Budi Santoso

| Data | Nilai |
|------|-------|
| Gaji Pokok | Rp 10.000.000 |
| Tunjangan Jabatan | Rp 2.000.000 (earning, taxable) |
| Tunjangan Transport | Rp 500.000 (earning, attendance-based, taxable) |
| Status PTKP | TK/0 (belum kawin) |
| NPWP | Ada |
| Hari kerja | 22 hari |
| Hadir | 20 hari |
| Telat | 2 hari |
| Absen | 0 hari |
| Lembur | 5 jam (weekday) |
| Reimbursement approved | Rp 350.000 |

### Perhitungan

```
STEP 1: Attendance-Based Component
  Transport = 500.000 - (500.000/22 × 0 absen) = Rp 500.000 (full, karena 0 absen)

STEP 2: Salary Components
  Total Earnings       = 2.000.000 + 500.000 = Rp 2.500.000
  Taxable Earnings     = Rp 2.500.000

STEP 3: Overtime (5 jam weekday)
  Hourly Rate = 10.000.000 / 173 = Rp 57.803
  Jam 1:   57.803 × 1.5 = Rp 86.705
  Jam 2-5: 57.803 × 2.0 × 4 = Rp 462.424
  Total Overtime = Rp 549.129

  Total Earnings   += 549.129 = Rp 3.049.129
  Taxable Earnings += 549.129 = Rp 3.049.129

STEP 4: Late Penalty (2 hari, threshold = 3)
  2 < 3 → TIDAK kena denda (di bawah threshold)

STEP 5: Reimbursement
  Total Earnings += 350.000 = Rp 3.399.129
  (taxable TIDAK bertambah — reimbursement bebas pajak)

STEP 6: Gross Salary
  Gross = 10.000.000 + 3.399.129 = Rp 13.399.129

STEP 7: PPh 21 (TER method, kategori A)
  Misal TER rate untuk 13.399.129 = 2.50%
  PPh 21 = 13.399.129 × 2.50% = Rp 334.978

STEP 8: BPJS Kesehatan
  Basis = Rp 12.000.000 (capped from 13.399.129)
  Employee = 12.000.000 × 1% = Rp 120.000

STEP 9: BPJS Ketenagakerjaan
  JHT karyawan = 13.399.129 × 2% = Rp 267.983
  JP karyawan  = 10.042.300 × 1% = Rp 100.423 (capped)
  Total BPJS TK employee = Rp 368.406

STEP 10: Net Salary
  Total Deductions = 0 (komponen) + 120.000 (BPJS Kes) + 368.406 (BPJS TK)
                   = Rp 488.406

  Net = 13.399.129 - 488.406 - 334.978
      = Rp 12.575.745
```

### Slip Gaji Budi Santoso

```
╔══════════════════════════════════════════════════════════════╗
║                    SLIP GAJI - April 2026                    ║
║                  PT. Contoh Indonesia                        ║
╠══════════════════════════════════════════════════════════════╣
║ Nama      : Budi Santoso           No. Karyawan: EMP20260001║
║ Jabatan   : Staff IT                Departemen : IT          ║
║ Status    : TK/0                    NPWP       : Ada         ║
╠══════════════════════════════════════════════════════════════╣
║ PENDAPATAN                                                   ║
║ ────────────────────────────────────────────────────────────  ║
║ Gaji Pokok                              Rp  10.000.000      ║
║ Tunjangan Jabatan                       Rp   2.000.000      ║
║ Tunjangan Transport                     Rp     500.000      ║
║ Lembur (5 jam weekday)                  Rp     549.129      ║
║ Reimbursement                           Rp     350.000      ║
║                                         ─────────────────    ║
║ GAJI KOTOR                              Rp  13.399.129      ║
╠══════════════════════════════════════════════════════════════╣
║ POTONGAN                                                     ║
║ ────────────────────────────────────────────────────────────  ║
║ PPh 21                                  Rp     334.978      ║
║ BPJS Kesehatan (1%)                     Rp     120.000      ║
║ BPJS JHT (2%)                           Rp     267.983      ║
║ BPJS JP (1%)                            Rp     100.423      ║
║                                         ─────────────────    ║
║ TOTAL POTONGAN                          Rp     823.384      ║
╠══════════════════════════════════════════════════════════════╣
║ GAJI BERSIH (Take Home Pay)             Rp  12.575.745      ║
╚══════════════════════════════════════════════════════════════╝

  Kehadiran: 20/22 hari | Telat: 2 hari | Lembur: 5 jam
```

---

## 12. Arsitektur Kode (File Map)

### Services (Business Logic)

```
app/Services/
├── PayrollCalculationService.php    ← PPh 21 + BPJS calculation
├── OvertimeCalculationService.php   ← Overtime amount calculation
└── ThrCalculationService.php        ← THR calculation (separate flow)
```

### Models (Data & Rules)

```
app/Models/
├── Payroll.php                ← Lifecycle, status transitions
├── PayrollItem.php            ← Per-employee payroll result
├── PayrollItemDetail.php      ← Line items (components, BPJS, tax)
├── PayrollSetting.php         ← Company payroll config
├── SalaryComponent.php        ← Component definitions + attendance calc
├── EmployeeSalary.php         ← Active salary per employee
├── EmployeeSalaryComponent.php ← Employee's salary breakdown
├── Pph21Setting.php           ← Tax config (TER flag, NPWP rate)
├── Pph21TerRate.php           ← TER rate table (category A/B/C)
├── Pph21Rate.php              ← Progressive rate reference
├── PtkpSetting.php            ← PTKP amounts
├── BpjsKesSetting.php         ← BPJS Kesehatan rates
├── BpjsTkSetting.php          ← BPJS TK rates (JHT, JP, JKK, JKM)
├── JkkRiskRate.php            ← JKK risk levels
├── OvertimeSetting.php        ← Overtime multipliers + limits
├── OvertimeRequest.php        ← Manual overtime submissions
├── ThrSetting.php             ← THR calculation config
├── ThrPayment.php             ← THR payment records
├── Attendance.php             ← Daily clock data
└── Employee.php               ← Employee master data
```

### Controllers

```
app/Http/Controllers/
├── PayrollController.php         ← CRUD + process + approve + pay
├── PayrollItemController.php     ← Individual payslip views
├── OvertimeRequestController.php ← Manual overtime CRUD
└── ThrPaymentController.php      ← THR management
```

### Relationship Diagram

```
Company (1)
├── PayrollSetting (1)
├── Pph21Setting (1) ──→ Pph21TerRate (many, per category/range)
├── BpjsKesSetting (1)
├── BpjsTkSetting (1)
├── OvertimeSetting (1)
├── ThrSetting (1)
│
├── Employee (many)
│   ├── EmployeeSalary (1 active)
│   │   └── EmployeeSalaryComponent (many) ──→ SalaryComponent
│   ├── Attendance (many, per day)
│   ├── OvertimeRequest (many)
│   └── ThrPayment (many, per year per holiday)
│
└── Payroll (1 per month)
    └── PayrollItem (1 per employee)
        └── PayrollItemDetail (many per component)
```

---

## 13. Latihan Praktik

### Latihan 1: Tracing Payroll Calculation

Buka file `app/Http/Controllers/PayrollController.php` method `process()`. Trace step-by-step:
1. Identifikasi di baris berapa `attendance data` dihitung
2. Identifikasi di baris berapa `salary components` di-loop
3. Identifikasi di baris berapa `overtime` dihitung
4. Identifikasi di baris berapa `PPh 21 + BPJS` dihitung
5. Identifikasi di baris berapa `net salary` dihitung

### Latihan 2: Hitung Manual PPh 21

Hitung PPh 21 untuk karyawan dengan data berikut:
- Gaji bruto: Rp 15.000.000
- Status PTKP: K/1 (kawin, 1 tanggungan)
- NPWP: Ada
- Metode: Progressive

**Jawab**: Hitung PTKP → PKP → Annualize → Apply bracket → Monthly

### Latihan 3: Hitung BPJS Lengkap

Untuk karyawan dengan gaji bruto Rp 20.000.000, hitung:
1. BPJS Kesehatan (employee + company) — perhatikan cap!
2. BPJS JHT (employee + company)
3. BPJS JP (employee + company) — perhatikan cap!
4. JKK (risiko medium = 0.89%)
5. JKM
6. Total potongan dari gaji karyawan
7. Total beban perusahaan

### Latihan 4: Simulasi Lembur

Karyawan bergaji Rp 8.000.000:
1. Hitung hourly rate (÷ 173)
2. Hitung lembur 2 jam weekday
3. Hitung lembur 4 jam weekend
4. Hitung lembur 3 jam holiday

### Latihan 5: Simulasi THR Prorata

Karyawan join tanggal 1 Oktober 2025:
1. Berapa bulan masa kerja per April 2026?
2. Gaji pokok Rp 8.000.000, tunjangan Rp 1.500.000
3. Hitung THR dengan metode prorata (include_allowances = true)
4. Hitung THR dengan metode prorata (include_allowances = false)

### Latihan 6: Code Reading Challenge

Buka `PayrollCalculationService.php` dan jawab:
1. Apa yang terjadi kalau `Pph21Setting` tidak ditemukan di database?
2. Apa default BPJS Kes jika `BpjsKesSetting` tidak ada?
3. Berapa penalty pajak kalau karyawan tidak punya NPWP?
4. Apa perbedaan kategori TER A, B, C?

---

## Kunci Jawaban Singkat

### Latihan 2
```
PTKP K/1 tahunan = Rp 63.000.000 → bulanan = Rp 5.250.000
PKP bulanan = 15.000.000 - 5.250.000 = Rp 9.750.000
PKP tahunan = 9.750.000 × 12 = Rp 117.000.000

Bracket:
  60.000.000 × 5%  = 3.000.000
  57.000.000 × 15% = 8.550.000
  Total = 11.550.000

PPh 21/bulan = 11.550.000 / 12 = Rp 962.500
```

### Latihan 3
```
BPJS Kes employee = 12.000.000 × 1% = Rp 120.000 (capped at 12 jt)
BPJS Kes company  = 12.000.000 × 4% = Rp 480.000

JHT employee = 20.000.000 × 2%   = Rp 400.000
JHT company  = 20.000.000 × 3.7% = Rp 740.000

JP employee = 10.042.300 × 1% = Rp 100.423 (capped)
JP company  = 10.042.300 × 2% = Rp 200.846

JKK company = 20.000.000 × 0.89% = Rp 178.000
JKM company = 20.000.000 × 0.30% = Rp  60.000

Total potong karyawan  = 120.000 + 400.000 + 100.423 = Rp 620.423
Total beban perusahaan = 480.000 + 740.000 + 200.846 + 178.000 + 60.000 = Rp 1.658.846
```

### Latihan 4
```
Hourly Rate = 8.000.000 / 173 = Rp 46.243

Weekday 2 jam: 46.243 × 1.5 + 46.243 × 2.0 × 1 = 69.365 + 92.486 = Rp 161.851
Weekend 4 jam: 46.243 × 2.0 × 4 = Rp 369.944
Holiday 3 jam: 46.243 × 3.0 × 3 = Rp 416.187
```

### Latihan 5
```
Masa kerja = Oct 2025 → Apr 2026 = 6 bulan

include_allowances = true:
  Total salary = 8.000.000 + 1.500.000 = 9.500.000
  THR = (6/12) × 9.500.000 = Rp 4.750.000

include_allowances = false:
  THR = (6/12) × 8.000.000 = Rp 4.000.000
```

### Latihan 6
```
1. Fallback: gross_salary × 5%
2. Default: company 4%, employee 1% (tanpa cap)
3. 20% lebih tinggi (PPh 21 × 1.20)
4. A = TK/0, TK/1, K/0 | B = TK/2, TK/3, K/1, K/2 | C = K/3, K/I/*
```

---

> **Next Session**: Sesi 8 — Face Recognition Integration (integrasi face recognition untuk attendance clock-in/clock-out)
