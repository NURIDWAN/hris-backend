# GajiPro - HRIS & Payroll SaaS untuk Indonesia

<p align="center">
  <img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/Tailwind-4.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind">
  <img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white" alt="Alpine.js">
  <img src="https://img.shields.io/badge/Flutter-Mobile_App-02569B?style=for-the-badge&logo=flutter&logoColor=white" alt="Flutter">
</p>

<p align="center">
  <a href="https://play.google.com/store/apps/details?id=com.jagoflutter.gajipro" target="_blank">
    <img src="https://play.google.com/intl/en_us/badges/static/images/badges/en_badge_web_generic.png" alt="Get it on Google Play" height="60">
  </a>
</p>

GajiPro adalah aplikasi Human Resource Information System (HRIS) dan Payroll berbasis cloud yang dirancang **khusus untuk pasar Indonesia**. Dibangun dengan arsitektur multi-tenant, mendukung berbagai perusahaan dalam satu platform.

---

## Statistik Project

| Metrik | Jumlah | Keterangan |
|--------|--------|------------|
| **Models** | 52 | Eloquent models untuk semua entitas bisnis |
| **Controllers (Web)** | 62 | Controller untuk dashboard web admin |
| **Controllers (API)** | 16 | REST API untuk mobile app Flutter |
| **Blade Views** | 198 | Template UI untuk web dashboard |
| **Database Migrations** | 63 | Schema database lengkap |
| **Test Files** | 129 | Automated testing dengan Pest PHP |
| **Services** | 12 | Business logic services |

---

## Daftar Isi - Marketing Points

- [1. FITUR KEHADIRAN (Attendance)](#1-fitur-kehadiran-attendance)
- [2. FITUR PENGGAJIAN (Payroll)](#2-fitur-penggajian-payroll)
- [3. FITUR PERPAJAKAN (Tax Compliance)](#3-fitur-perpajakan-tax-compliance)
- [4. FITUR BPJS](#4-fitur-bpjs)
- [5. FITUR CUTI (Leave Management)](#5-fitur-cuti-leave-management)
- [6. FITUR LEMBUR (Overtime)](#6-fitur-lembur-overtime)
- [7. FITUR KARYAWAN (Employee Management)](#7-fitur-karyawan-employee-management)
- [8. FITUR REIMBURSEMENT](#8-fitur-reimbursement)
- [9. FITUR THR (Tunjangan Hari Raya)](#9-fitur-thr-tunjangan-hari-raya)
- [10. FITUR LAPORAN & EXPORT](#10-fitur-laporan--export)
- [11. FITUR MOBILE APP](#11-fitur-mobile-app)
- [12. FITUR EMPLOYEE SELF-SERVICE](#12-fitur-employee-self-service)
- [13. FITUR MULTI-TENANT & SaaS](#13-fitur-multi-tenant--saas)
- [14. FITUR IMPORT DATA](#14-fitur-import-data)
- [15. FITUR SETTINGS & KONFIGURASI](#15-fitur-settings--konfigurasi)

---

## 1. FITUR KEHADIRAN (Attendance)

### 1.1 Clock In/Out dengan Foto Selfie
**Marketing Point:** "Absensi anti-titip dengan foto selfie real-time"

| Fitur | Detail | Benefit untuk HR |
|-------|--------|------------------|
| Foto selfie wajib | Setiap clock in/out harus dengan foto | Bukti visual kehadiran karyawan |
| Timestamp otomatis | Waktu tercatat otomatis dari server | Tidak bisa manipulasi waktu |
| Storage terorganisir | Foto tersimpan per tanggal & karyawan | Mudah audit kapan saja |

**Flow:**
```
Buka App → Ambil Selfie → Submit → Foto + Waktu Tersimpan
```

### 1.2 GPS Location Tracking
**Marketing Point:** "Validasi lokasi dengan GPS, tidak bisa absen dari rumah"

| Fitur | Detail | Benefit untuk HR |
|-------|--------|------------------|
| Haversine formula | Kalkulasi jarak akurat dalam meter | Presisi tinggi sampai meter |
| Radius per kantor | Set radius berbeda per lokasi kantor | Fleksibel untuk kantor berbeda |
| Outside radius alert | Notifikasi jika di luar radius | Deteksi kecurangan otomatis |
| Multiple locations | Karyawan bisa assign ke banyak kantor | Cocok untuk sales/field worker |

**Konfigurasi Radius:**
- Default: 100 meter
- Bisa custom: 50m - 5000m per lokasi
- Support unlimited lokasi kantor

### 1.3 Face Recognition (Liveness Detection)
**Marketing Point:** "Verifikasi wajah dengan AI, anti-foto foto"

| Fitur | Detail | Benefit untuk HR |
|-------|--------|------------------|
| Face embedding | Wajah dikonversi ke vektor matematika | Tidak simpan foto wajah langsung |
| Confidence score | Nilai kecocokan 0-100% | Threshold bisa disesuaikan |
| Verification log | Semua verifikasi tercatat | Audit trail lengkap |
| Re-enrollment | Bisa daftar ulang wajah | Jika berubah (kacamata, dll) |

**Flow Enrollment:**
```
1. Ambil foto depan
2. Ambil foto kiri
3. Ambil foto kanan
4. Sistem generate embedding
5. Simpan untuk verifikasi
```

**Threshold Default:** 85% match

### 1.4 Multiple Office Locations
**Marketing Point:** "Dukung unlimited lokasi kantor dengan radius berbeda"

| Data per Lokasi | Contoh |
|-----------------|--------|
| Nama | Kantor Pusat Jakarta |
| Alamat | Jl. Sudirman No. 1 |
| Latitude | -6.2088 |
| Longitude | 106.8456 |
| Radius | 150 meter |
| Status | Aktif/Nonaktif |

**Use Case:**
- Kantor pusat: radius 100m
- Pabrik: radius 500m
- Site project: radius 1000m
- Client office: radius 200m

### 1.5 Late & Early Tracking
**Marketing Point:** "Tracking telat dan pulang cepat otomatis"

| Kalkulasi | Formula |
|-----------|---------|
| Telat | Clock In - Jadwal Masuk (jika positif) |
| Pulang Cepat | Jadwal Pulang - Clock Out (jika positif) |
| Total Jam Kerja | Clock Out - Clock In - Istirahat |

**Auto-tagging:**
- `on_time` - Tepat waktu
- `late` - Terlambat
- `early_leave` - Pulang cepat
- `overtime` - Lembur (jika lebih dari jam kerja)

### 1.6 Work Schedules (Jadwal Kerja)
**Marketing Point:** "Jadwal kerja fleksibel, support shift dan reguler"

| Tipe Jadwal | Contoh |
|-------------|--------|
| Reguler | 08:00 - 17:00 (istirahat 12:00-13:00) |
| Shift Pagi | 06:00 - 14:00 |
| Shift Siang | 14:00 - 22:00 |
| Shift Malam | 22:00 - 06:00 |
| Flexible | Core hours 10:00 - 15:00 |

**Konfigurasi per Jadwal:**
- Jam masuk & pulang
- Durasi istirahat
- Toleransi telat (grace period)
- Hari kerja (Senin-Jumat, dll)

---

## 2. FITUR PENGGAJIAN (Payroll)

### 2.1 Salary Components (Komponen Gaji)
**Marketing Point:** "Komponen gaji unlimited dan fleksibel"

| Tipe Komponen | Contoh | Taxable? |
|---------------|--------|----------|
| **Earnings (Pendapatan)** | | |
| Gaji Pokok | Rp 10.000.000 | Ya |
| Tunjangan Jabatan | Rp 2.000.000 | Ya |
| Tunjangan Transport | Rp 500.000 | Ya |
| Tunjangan Makan | Rp 750.000 | Ya |
| Insentif | Variable | Ya |
| Bonus | Variable | Ya |
| **Deductions (Potongan)** | | |
| Potongan Absensi | Variable | - |
| Potongan Pinjaman | Variable | - |
| Potongan Lainnya | Variable | - |

**Fitur Komponen:**
- Buat unlimited komponen
- Set taxable/non-taxable per komponen
- Komponen fixed atau percentage
- Komponen wajib atau opsional

### 2.2 Payroll Calculation Engine
**Marketing Point:** "Kalkulasi gaji otomatis dengan semua komponen"

**Formula Kalkulasi:**
```
GROSS SALARY = Gaji Pokok + Total Tunjangan + Lembur + Insentif

DEDUCTIONS = BPJS Karyawan + PPh 21 + Potongan Lain + Cicilan Pinjaman

NET SALARY (Take Home Pay) = GROSS SALARY - DEDUCTIONS
```

**Auto-Calculate:**
- PPh 21 (TER atau Progressive)
- BPJS Kesehatan (4% + 1%)
- BPJS Ketenagakerjaan (JHT, JKK, JKM, JP)
- Potongan keterlambatan
- Potongan absensi
- Cicilan pinjaman

### 2.3 Payroll Period Management
**Marketing Point:** "Kelola periode gaji bulanan dengan mudah"

| Status Payroll | Keterangan |
|----------------|------------|
| `draft` | Masih bisa edit, belum final |
| `processing` | Sedang dikalkulasi |
| `completed` | Final, tidak bisa edit |
| `paid` | Sudah dibayar ke karyawan |

**Flow Payroll:**
```
1. Buat periode baru (misal: Januari 2026)
2. Sistem tarik data kehadiran, lembur, cuti
3. Kalkulasi otomatis semua komponen
4. Review & adjust jika perlu
5. Finalize (lock)
6. Generate payslip PDF
7. Export bank file untuk transfer
```

### 2.4 Payslip PDF Generation
**Marketing Point:** "Generate slip gaji PDF otomatis"

**Konten Payslip:**
```
┌─────────────────────────────────────────┐
│           SLIP GAJI                     │
│           PT. ABC Indonesia             │
│           Periode: Januari 2026         │
├─────────────────────────────────────────┤
│ Nama      : John Doe                    │
│ NIK       : EMP20260001                 │
│ Jabatan   : Senior Developer            │
│ Department: Engineering                 │
├─────────────────────────────────────────┤
│ PENDAPATAN                              │
│ Gaji Pokok          : Rp 15.000.000     │
│ Tunj. Jabatan       : Rp  3.000.000     │
│ Tunj. Transport     : Rp    500.000     │
│ Tunj. Makan         : Rp    750.000     │
│ Lembur              : Rp  1.200.000     │
│ TOTAL PENDAPATAN    : Rp 20.450.000     │
├─────────────────────────────────────────┤
│ POTONGAN                                │
│ BPJS Kesehatan      : Rp    120.000     │
│ BPJS JHT            : Rp    400.000     │
│ BPJS JP             : Rp    200.000     │
│ PPh 21              : Rp    850.000     │
│ TOTAL POTONGAN      : Rp  1.570.000     │
├─────────────────────────────────────────┤
│ TAKE HOME PAY       : Rp 18.880.000     │
└─────────────────────────────────────────┘
```

### 2.5 Bank Export File
**Marketing Point:** "Export file transfer bank langsung"

**Format Export:**
- Excel (.xlsx)
- CSV
- Format bank specific (BCA, Mandiri, BNI, BRI)

**Data Export:**
| No | Nama | No. Rekening | Bank | Nominal |
|----|------|--------------|------|---------|
| 1 | John Doe | 1234567890 | BCA | 18.880.000 |
| 2 | Jane Doe | 0987654321 | Mandiri | 15.500.000 |

---

## 3. FITUR PERPAJAKAN (Tax Compliance)

### 3.1 PPh 21 dengan Metode TER
**Marketing Point:** "Perhitungan PPh 21 otomatis sesuai regulasi terbaru (TER 2024)"

**Apa itu TER (Tarif Efektif Rata-rata)?**
Metode perhitungan PPh 21 terbaru dari DJP yang lebih sederhana dibanding metode progressive tradisional.

| Kategori | Status PTKP yang Termasuk |
|----------|---------------------------|
| **A** | TK/0, TK/1, K/0 |
| **B** | TK/2, TK/3, K/1, K/2 |
| **C** | K/3, K/I/0, K/I/1, K/I/2, K/I/3 |

**Contoh Rate TER Kategori A:**
| Penghasilan Bruto | Tarif |
|-------------------|-------|
| 0 - 5.400.000 | 0% |
| 5.400.001 - 5.650.000 | 0.25% |
| 5.650.001 - 5.950.000 | 0.50% |
| 5.950.001 - 6.300.000 | 0.75% |
| ... | ... |
| > 60.000.000 | 34% |

**Kalkulasi:**
```
PPh 21 = Penghasilan Bruto × Tarif TER
```

### 3.2 PPh 21 Progressive (Fallback)
**Marketing Point:** "Support juga metode PPh 21 progressive untuk kasus khusus"

| Layer | PKP Tahunan | Tarif |
|-------|-------------|-------|
| 1 | 0 - 60 juta | 5% |
| 2 | 60 - 250 juta | 15% |
| 3 | 250 - 500 juta | 25% |
| 4 | 500 juta - 5 M | 30% |
| 5 | > 5 M | 35% |

### 3.3 PTKP (Penghasilan Tidak Kena Pajak)
**Marketing Point:** "PTKP 2024 sudah include, auto-update"

| Status | PTKP Tahunan | PTKP Bulanan |
|--------|--------------|--------------|
| TK/0 | Rp 54.000.000 | Rp 4.500.000 |
| TK/1 | Rp 58.500.000 | Rp 4.875.000 |
| TK/2 | Rp 63.000.000 | Rp 5.250.000 |
| TK/3 | Rp 67.500.000 | Rp 5.625.000 |
| K/0 | Rp 58.500.000 | Rp 4.875.000 |
| K/1 | Rp 63.000.000 | Rp 5.250.000 |
| K/2 | Rp 67.500.000 | Rp 5.625.000 |
| K/3 | Rp 72.000.000 | Rp 6.000.000 |
| K/I/0 | Rp 112.500.000 | Rp 9.375.000 |
| K/I/1 | Rp 117.000.000 | Rp 9.750.000 |
| K/I/2 | Rp 121.500.000 | Rp 10.125.000 |
| K/I/3 | Rp 126.000.000 | Rp 10.500.000 |

### 3.4 NPWP Penalty
**Marketing Point:** "Auto-apply penalty 20% untuk karyawan tanpa NPWP"

```
Jika karyawan tidak punya NPWP:
PPh 21 Final = PPh 21 × 1.20 (tambah 20%)
```

### 3.5 SPT 1721 (Laporan Pajak Tahunan)
**Marketing Point:** "Generate SPT 1721 otomatis untuk lapor ke DJP"

**Komponen SPT 1721:**
| Sheet | Keterangan |
|-------|------------|
| Induk | Ringkasan total pajak perusahaan |
| Lampiran I | Detail per karyawan tetap |
| Lampiran II | Detail per karyawan tidak tetap |

**Export Format:** Excel (.xlsx) sesuai format DJP

### 3.6 Bukti Potong 1721-A1
**Marketing Point:** "Generate Bukti Potong PPh 21 untuk setiap karyawan"

**Data yang Include:**
- Identitas pemotong (perusahaan)
- Identitas penerima (karyawan)
- Rincian penghasilan bruto
- Rincian potongan
- Jumlah PPh 21 terutang
- QR Code validasi

---

## 4. FITUR BPJS

### 4.1 BPJS Kesehatan
**Marketing Point:** "Kalkulasi BPJS Kesehatan otomatis dengan batas UMP"

| Komponen | Rate | Ditanggung |
|----------|------|------------|
| Company | 4% | Perusahaan |
| Employee | 1% | Karyawan |
| **Total** | **5%** | |

**Batas Perhitungan:**
- Minimum: UMP (misal Rp 4.900.000)
- Maximum: Rp 12.000.000

**Contoh Kalkulasi:**
```
Gaji: Rp 10.000.000 (dalam range)
BPJS Kes Company: Rp 10.000.000 × 4% = Rp 400.000
BPJS Kes Employee: Rp 10.000.000 × 1% = Rp 100.000

Gaji: Rp 20.000.000 (melebihi batas)
BPJS Kes Company: Rp 12.000.000 × 4% = Rp 480.000
BPJS Kes Employee: Rp 12.000.000 × 1% = Rp 120.000
```

### 4.2 BPJS Ketenagakerjaan
**Marketing Point:** "Semua program BPJS TK lengkap: JHT, JKK, JKM, JP"

#### JHT (Jaminan Hari Tua)
| Komponen | Rate |
|----------|------|
| Company | 3.7% |
| Employee | 2% |
| **Total** | **5.7%** |

#### JKK (Jaminan Kecelakaan Kerja)
| Risiko | Rate | Contoh Industri |
|--------|------|-----------------|
| Sangat Rendah | 0.24% | Kantor, IT |
| Rendah | 0.54% | Retail, F&B |
| Sedang | 0.89% | Manufaktur ringan |
| Tinggi | 1.27% | Konstruksi |
| Sangat Tinggi | 1.74% | Pertambangan |

#### JKM (Jaminan Kematian)
| Komponen | Rate |
|----------|------|
| Company | 0.3% |
| Employee | 0% |

#### JP (Jaminan Pensiun)
| Komponen | Rate |
|----------|------|
| Company | 2% |
| Employee | 1% |
| **Total** | **3%** |

**Batas Maksimum JP:** Rp 9.559.600 (2024)

### 4.3 Summary BPJS per Karyawan
**Marketing Point:** "Dashboard BPJS lengkap per karyawan"

**Contoh untuk Gaji Rp 15.000.000:**
| Program | Company | Employee | Total |
|---------|---------|----------|-------|
| BPJS Kesehatan | 480.000 | 120.000 | 600.000 |
| JHT | 555.000 | 300.000 | 855.000 |
| JKK (Sedang) | 133.500 | 0 | 133.500 |
| JKM | 45.000 | 0 | 45.000 |
| JP | 191.192 | 95.596 | 286.788 |
| **TOTAL** | **1.404.692** | **515.596** | **1.920.288** |

---

## 5. FITUR CUTI (Leave Management)

### 5.1 Leave Types (Jenis Cuti)
**Marketing Point:** "Buat unlimited jenis cuti sesuai kebijakan perusahaan"

| Jenis Cuti | Quota/Tahun | Carry Forward | Dokumen |
|------------|-------------|---------------|---------|
| Cuti Tahunan | 12 hari | Ya (max 6 hari) | Tidak |
| Cuti Sakit | 14 hari | Tidak | Surat Dokter |
| Cuti Melahirkan | 90 hari | Tidak | Surat Dokter |
| Cuti Menikah | 3 hari | Tidak | Undangan |
| Cuti Kematian Keluarga | 3 hari | Tidak | Surat Kematian |
| Cuti Khitanan Anak | 2 hari | Tidak | Tidak |
| Cuti Baptis Anak | 2 hari | Tidak | Tidak |
| Cuti Istri Melahirkan | 2 hari | Tidak | Tidak |
| Cuti Tanpa Gaji | Unlimited | Tidak | Tidak |

**Konfigurasi per Tipe:**
- Nama cuti
- Kuota tahunan
- Apakah carry forward?
- Maksimal carry forward
- Apakah perlu dokumen?
- Apakah potong gaji?
- Berlaku untuk gender mana?

### 5.2 Leave Balance
**Marketing Point:** "Saldo cuti real-time per karyawan"

| Field | Keterangan |
|-------|------------|
| `allocated` | Kuota diberikan |
| `used` | Sudah terpakai |
| `pending` | Menunggu approval |
| `remaining` | Sisa yang bisa diambil |
| `carried_forward` | Sisa tahun lalu |

**Contoh:**
```
Cuti Tahunan 2026:
- Allocated: 12 hari
- Carried Forward: 3 hari (dari 2025)
- Total Available: 15 hari
- Used: 5 hari
- Pending: 2 hari
- Remaining: 8 hari
```

### 5.3 Leave Request Flow
**Marketing Point:** "Alur pengajuan cuti dengan approval otomatis"

```
Karyawan Submit → Manager Review → HR Review (opsional) → Approved/Rejected
        ↓              ↓                    ↓                   ↓
   Notifikasi     Notifikasi          Notifikasi          Update Saldo
```

**Status Request:**
| Status | Keterangan |
|--------|------------|
| `pending` | Menunggu approval |
| `approved` | Disetujui |
| `rejected` | Ditolak |
| `cancelled` | Dibatalkan oleh karyawan |

### 5.4 Half-Day Leave
**Marketing Point:** "Dukung cuti setengah hari"

- Morning half: 08:00 - 12:00
- Afternoon half: 13:00 - 17:00
- Dihitung 0.5 hari dari kuota

### 5.5 Leave Attachment
**Marketing Point:** "Upload dokumen pendukung untuk cuti"

Supported formats:
- PDF
- JPG/PNG (gambar)
- Max size: 5MB per file

---

## 6. FITUR LEMBUR (Overtime)

### 6.1 Overtime Request
**Marketing Point:** "Pengajuan lembur dengan approval workflow"

| Field | Keterangan |
|-------|------------|
| Tanggal | Kapan lembur |
| Jam Mulai | Waktu mulai lembur |
| Jam Selesai | Waktu selesai lembur |
| Alasan | Kenapa perlu lembur |
| Approval Status | pending/approved/rejected |

### 6.2 Overtime Calculation
**Marketing Point:** "Kalkulasi upah lembur otomatis sesuai UU Ketenagakerjaan"

**Rumus Upah Lembur per Jam:**
```
Upah Lembur/Jam = (1/173) × Gaji Bulanan
```

**Rate Lembur:**
| Kondisi | Jam ke-1 | Jam ke-2+ |
|---------|----------|-----------|
| Hari Kerja | 1.5× | 2× |
| Hari Libur/Weekend | 2× | 2× (jam 1-7), 3× (jam 8), 4× (jam 9+) |
| Hari Raya | 2× | 2× (jam 1-5), 3× (jam 6-7), 4× (jam 8+) |

**Contoh Kalkulasi:**
```
Gaji Bulanan: Rp 10.000.000
Upah/Jam: Rp 10.000.000 / 173 = Rp 57.803

Lembur Hari Kerja 3 jam:
- Jam 1: 1.5 × Rp 57.803 = Rp 86.705
- Jam 2: 2 × Rp 57.803 = Rp 115.607
- Jam 3: 2 × Rp 57.803 = Rp 115.607
- Total: Rp 317.919
```

### 6.3 Overtime Settings
**Marketing Point:** "Konfigurasi kebijakan lembur sesuai perusahaan"

| Setting | Opsi |
|---------|------|
| Max lembur per hari | 3-4 jam |
| Max lembur per minggu | 14-18 jam |
| Require pre-approval | Ya/Tidak |
| Auto-calculate dari attendance | Ya/Tidak |
| Minimum durasi lembur | 1 jam |

---

## 7. FITUR KARYAWAN (Employee Management)

### 7.1 Employee Data (52+ Fields)
**Marketing Point:** "Data karyawan lengkap dalam satu sistem"

**Data Pribadi:**
| Field | Contoh |
|-------|--------|
| NIK Karyawan | EMP20260001 (auto-generate) |
| Nama Lengkap | John Doe |
| Email | john.doe@company.com |
| No. HP | 081234567890 |
| Tanggal Lahir | 15 Januari 1990 |
| Tempat Lahir | Jakarta |
| Jenis Kelamin | Laki-laki |
| Agama | Islam |
| Status Pernikahan | Menikah |
| Jumlah Tanggungan | 2 |
| Alamat | Jl. Sudirman No. 1 |
| RT/RW | 001/002 |
| Kelurahan | Menteng |
| Kecamatan | Menteng |
| Kota | Jakarta Pusat |
| Provinsi | DKI Jakarta |
| Kode Pos | 10310 |

**Data Kepegawaian:**
| Field | Contoh |
|-------|--------|
| Status Karyawan | Tetap/Kontrak/Probation |
| Tanggal Bergabung | 1 Januari 2020 |
| Tanggal Akhir Kontrak | - (kalau tetap) |
| Department | Engineering |
| Position | Senior Developer |
| Level | Senior |
| Atasan Langsung | Jane Doe |
| Office Location | Kantor Pusat Jakarta |

**Data Keuangan:**
| Field | Contoh |
|-------|--------|
| Gaji Pokok | Rp 15.000.000 |
| Tunjangan | Rp 5.000.000 |
| No. Rekening | 1234567890 |
| Nama Bank | BCA |
| Nama Pemilik Rekening | John Doe |

**Data Pajak & BPJS:**
| Field | Contoh |
|-------|--------|
| NPWP | 12.345.678.9-012.000 |
| Status PTKP | K/1 |
| No. BPJS Kesehatan | 0001234567890 |
| No. BPJS Ketenagakerjaan | 12345678901 |

### 7.2 Employee Documents
**Marketing Point:** "Kelola dokumen karyawan terpusat"

| Tipe Dokumen | Wajib? | Expired? |
|--------------|--------|----------|
| KTP | Ya | Ya |
| NPWP | Conditional | Tidak |
| Kartu Keluarga | Ya | Tidak |
| Ijazah Terakhir | Ya | Tidak |
| Sertifikat Keahlian | Tidak | Ya |
| Kontrak Kerja | Ya | Ya |
| Surat Referensi | Tidak | Tidak |
| Foto 3x4 | Ya | Tidak |
| SKCK | Tidak | Ya |
| Surat Keterangan Sehat | Tidak | Ya |

**Fitur:**
- Upload multiple files per dokumen
- Track expiry date
- Alert sebelum expired
- Download batch

### 7.3 Employee Status Lifecycle
**Marketing Point:** "Track status karyawan dari masuk sampai keluar"

```
Probation → Kontrak → Tetap → Exit (Resign/PHK/Pensiun)
```

**Exit Types:**
| Tipe | Keterangan | Hak |
|------|------------|-----|
| Resign | Mengundurkan diri | Sesuai kebijakan |
| PHK | Pemutusan hubungan kerja | Pesangon |
| Kontrak Habis | Tidak diperpanjang | Sesuai kontrak |
| Pensiun | Usia pensiun | Pensiun + JHT |
| Meninggal | - | JKM |

### 7.4 Organization Chart
**Marketing Point:** "Visualisasi struktur organisasi interaktif"

```
                    ┌──────────────┐
                    │     CEO      │
                    │  John Smith  │
                    └──────┬───────┘
           ┌───────────────┼───────────────┐
           ▼               ▼               ▼
    ┌──────────────┐┌──────────────┐┌──────────────┐
    │  CTO         ││  CFO         ││  COO         │
    │  Jane Doe    ││  Bob Wilson  ││  Amy Chen    │
    └──────┬───────┘└──────────────┘└──────┬───────┘
           ▼                               ▼
    ┌──────────────┐               ┌──────────────┐
    │  Engineering │               │  Operations  │
    │  Team        │               │  Team        │
    └──────────────┘               └──────────────┘
```

### 7.5 Activity Log
**Marketing Point:** "Audit trail lengkap setiap perubahan data"

| Event | Detail | User | Timestamp |
|-------|--------|------|-----------|
| created | Employee EMP001 created | admin@co.com | 2026-01-01 09:00 |
| updated | Salary changed: 10jt → 12jt | hr@co.com | 2026-02-15 14:30 |
| updated | Position: Staff → Senior | admin@co.com | 2026-03-01 10:00 |
| deleted | Document KTP deleted | admin@co.com | 2026-03-15 11:00 |

---

## 8. FITUR REIMBURSEMENT

### 8.1 Reimbursement Categories
**Marketing Point:** "Kategori reimbursement custom sesuai kebijakan"

| Kategori | Budget/Bulan | Perlu Approval |
|----------|--------------|----------------|
| Transport | Rp 2.000.000 | Ya |
| Makan (Client Meeting) | Rp 1.000.000 | Ya |
| Kesehatan | Rp 5.000.000 | Ya |
| Training | Rp 10.000.000 | Ya |
| Perjalanan Dinas | Unlimited | Ya |
| Parkir | Rp 500.000 | Tidak |
| Internet (WFH) | Rp 200.000 | Tidak |

### 8.2 Reimbursement Request
**Marketing Point:** "Pengajuan reimbursement dengan upload bukti"

| Field | Keterangan |
|-------|------------|
| Kategori | Pilih dari list |
| Tanggal Transaksi | Kapan pengeluaran |
| Jumlah | Nominal reimbursement |
| Deskripsi | Detail pengeluaran |
| Attachment | Upload struk/invoice |

**Status Flow:**
```
Submit → Pending → Approved/Rejected → Paid (masuk payroll)
```

### 8.3 Reimbursement in Payroll
**Marketing Point:** "Reimbursement otomatis masuk slip gaji"

Reimbursement yang di-approve akan:
1. Otomatis masuk ke payroll bulan berjalan
2. Tercatat di slip gaji sebagai "Reimbursement"
3. Tidak kena pajak (non-taxable)

---

## 9. FITUR THR (Tunjangan Hari Raya)

### 9.1 THR Calculation
**Marketing Point:** "Kalkulasi THR otomatis sesuai UU Ketenagakerjaan"

**Rumus THR:**
```
Masa Kerja ≥ 12 bulan:
THR = 1 × Gaji Bulanan

Masa Kerja < 12 bulan:
THR = (Masa Kerja / 12) × Gaji Bulanan
```

**Contoh:**
```
Gaji: Rp 10.000.000
Masa Kerja: 8 bulan
THR = (8/12) × Rp 10.000.000 = Rp 6.666.667
```

### 9.2 THR Settings
**Marketing Point:** "Konfigurasi THR sesuai kebijakan perusahaan"

| Setting | Opsi |
|---------|------|
| Basis perhitungan | Gaji pokok / Gaji + Tunjangan |
| Minimum masa kerja | 1-3 bulan |
| Include dalam PPh 21? | Ya/Tidak |
| Waktu pembayaran | H-7 Lebaran |

### 9.3 THR Batch Processing
**Marketing Point:** "Proses THR semua karyawan sekaligus"

```
1. Pilih periode THR (Lebaran 2026)
2. Sistem hitung THR semua karyawan
3. Review per karyawan
4. Approve batch
5. Export ke payroll atau terpisah
```

---

## 10. FITUR LAPORAN & EXPORT

### 10.1 Laporan Karyawan
**Marketing Point:** "Laporan data karyawan dengan berbagai filter"

**Filter Tersedia:**
- Status (aktif/nonaktif)
- Department
- Position
- Tanggal bergabung
- Status karyawan (tetap/kontrak)

**Export:** Excel (.xlsx)

### 10.2 Laporan Kehadiran
**Marketing Point:** "Laporan absensi detail per periode"

**Data Laporan:**
| Kolom | Keterangan |
|-------|------------|
| Tanggal | Tanggal absensi |
| Karyawan | Nama & NIK |
| Clock In | Waktu masuk |
| Clock Out | Waktu pulang |
| Total Jam | Durasi kerja |
| Status | on_time/late/early |
| Lokasi | Nama kantor |

**Filter:**
- Periode (tanggal mulai - selesai)
- Department
- Karyawan spesifik
- Status kehadiran

**Export:** Excel (.xlsx)

### 10.3 Laporan Cuti
**Marketing Point:** "Laporan penggunaan cuti per periode"

**Data Laporan:**
| Kolom | Keterangan |
|-------|------------|
| Karyawan | Nama & NIK |
| Tipe Cuti | Jenis cuti |
| Tanggal Mulai | - |
| Tanggal Selesai | - |
| Durasi | Jumlah hari |
| Status | approved/rejected |
| Sisa Cuti | Balance |

**Export:** Excel (.xlsx)

### 10.4 Laporan Payroll
**Marketing Point:** "Laporan penggajian lengkap per periode"

**Data Laporan:**
| Kolom | Keterangan |
|-------|------------|
| Karyawan | Nama & NIK |
| Gaji Pokok | - |
| Total Tunjangan | - |
| Lembur | - |
| Gross | Total pendapatan |
| BPJS Employee | Potongan BPJS |
| PPh 21 | Potongan pajak |
| Potongan Lain | - |
| Net (THP) | Take home pay |

**Summary:**
- Total Gross: Rp XXX
- Total Net: Rp XXX
- Total PPh 21: Rp XXX
- Total BPJS: Rp XXX

**Export:** Excel (.xlsx)

### 10.5 Export SPT 1721
**Marketing Point:** "Export SPT 1721 siap lapor ke DJP"

**Format:** Excel multi-sheet sesuai format DJP
- Sheet Induk
- Sheet Lampiran I
- Sheet Lampiran II

### 10.6 Export Bank Transfer
**Marketing Point:** "File transfer bank siap upload"

**Format:**
- CSV standard
- Excel
- Format bank specific (BCA, Mandiri, dll)

---

## 11. FITUR MOBILE APP

### 11.1 App Download
**Marketing Point:** "Aplikasi mobile tersedia di Google Play Store"

<p align="center">
  <a href="https://play.google.com/store/apps/details?id=com.jagoflutter.gajipro">
    <b>Download di Google Play Store</b>
  </a>
</p>

### 11.2 Mobile Features
**Marketing Point:** "Semua fitur HR di genggaman karyawan"

| Fitur | Deskripsi |
|-------|-----------|
| **Clock In/Out** | Absensi dengan selfie & GPS |
| **Face Recognition** | Verifikasi wajah saat absen |
| **Leave Request** | Ajukan cuti dari HP |
| **Overtime Request** | Ajukan lembur dari HP |
| **Payslip** | Lihat slip gaji bulanan |
| **Reimbursement** | Ajukan reimbursement + foto struk |
| **Announcements** | Baca pengumuman perusahaan |
| **Profile** | Update data pribadi |
| **Approvals** | Approve request (untuk manager) |

### 11.3 Push Notifications
**Marketing Point:** "Notifikasi real-time ke HP karyawan"

| Event | Notifikasi |
|-------|------------|
| Clock In Success | "Absensi masuk berhasil: 08:00" |
| Clock Out Success | "Absensi pulang berhasil: 17:00" |
| Leave Approved | "Cuti Anda disetujui" |
| Leave Rejected | "Cuti Anda ditolak" |
| Payslip Ready | "Slip gaji Januari sudah tersedia" |
| Announcement | "Pengumuman baru dari HR" |

### 11.4 Offline Support
**Marketing Point:** "Tetap bisa absen walau sinyal lemah"

- Data tersimpan lokal
- Sync otomatis saat online
- Queue system untuk reliability

---

## 12. FITUR EMPLOYEE SELF-SERVICE

### 12.1 Employee Portal
**Marketing Point:** "Portal khusus karyawan untuk self-service"

**Menu Portal:**
| Menu | Fungsi |
|------|--------|
| Dashboard | Ringkasan kehadiran, cuti, gaji |
| Attendance | Lihat riwayat absensi |
| Leave | Ajukan & track cuti |
| Overtime | Ajukan & track lembur |
| Payslips | Download slip gaji |
| Reimbursements | Ajukan & track reimbursement |
| Profile | Update data pribadi |
| Announcements | Baca pengumuman |

### 12.2 Manager Approvals
**Marketing Point:** "Manager bisa approve langsung dari portal/app"

| Yang Bisa Di-approve | Di Mana |
|---------------------|---------|
| Leave Requests | Portal + Mobile App |
| Overtime Requests | Portal + Mobile App |
| Reimbursements | Portal + Mobile App |

**Approval Flow:**
```
Karyawan Submit → Notifikasi ke Manager → Manager Review → Approve/Reject
```

---

## 13. FITUR MULTI-TENANT & SaaS

### 13.1 Multi-tenant Architecture
**Marketing Point:** "Satu platform untuk banyak perusahaan, data terisolasi"

```
┌─────────────────────────────────────────────────────────┐
│                    GajiPro SaaS                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐              │
│  │Company A │  │Company B │  │Company C │   ...        │
│  │ (Tenant) │  │ (Tenant) │  │ (Tenant) │              │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘              │
│       │             │             │                     │
│       ▼             ▼             ▼                     │
│  ┌─────────────────────────────────────────────────┐   │
│  │            Shared Database (MySQL)               │   │
│  │  - All tables have company_id for isolation      │   │
│  │  - SetTenant middleware auto-filters data        │   │
│  └─────────────────────────────────────────────────┘   │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### 13.2 Subscription Plans
**Marketing Point:** "Paket berlangganan fleksibel"

| Plan | Max Karyawan | Harga/Bulan | Harga/Tahun |
|------|--------------|-------------|-------------|
| Trial | 10 | Gratis (14 hari) | - |
| Starter | 25 | Rp 299.000 | Rp 2.990.000 |
| Professional | 100 | Rp 799.000 | Rp 7.990.000 |
| Enterprise | Unlimited | Custom | Custom |

### 13.3 Payment Gateways
**Marketing Point:** "Pembayaran mudah via berbagai metode"

| Gateway | Metode |
|---------|--------|
| Xendit | VA, QRIS, E-Wallet, Kartu Kredit |
| Midtrans | VA, QRIS, E-Wallet, Kartu Kredit |
| Manual | Transfer Bank |

### 13.4 Demo Mode
**Marketing Point:** "Mode demo dengan data sample untuk onboarding"

Saat register trial:
- Auto-generate struktur organisasi
- Auto-generate karyawan sample
- Auto-generate data kehadiran
- Siap dicoba langsung!

---

## 14. FITUR IMPORT DATA

### 14.1 Excel Import
**Marketing Point:** "Import data massal dari Excel"

| Data yang Bisa Diimport | Template |
|-------------------------|----------|
| Karyawan | ✅ Download template |
| Department | ✅ Download template |
| Position | ✅ Download template |
| Hari Libur | ✅ Download template |
| Jadwal Kerja | ✅ Download template |
| Tipe Cuti | ✅ Download template |

### 14.2 Import Flow
```
1. Download template Excel
2. Isi data sesuai format
3. Upload file
4. Preview & validasi
5. Confirm import
6. Data masuk ke sistem
```

### 14.3 Validation
**Marketing Point:** "Validasi data sebelum import"

- Cek format data
- Cek duplikasi
- Cek required fields
- Preview error rows
- Skip atau fix errors

---

## 15. FITUR SETTINGS & KONFIGURASI

### 15.1 Company Profile
| Setting | Keterangan |
|---------|------------|
| Nama Perusahaan | PT. ABC Indonesia |
| Alamat | Jl. Sudirman No. 1 |
| NPWP Perusahaan | 01.234.567.8-012.000 |
| Logo | Upload logo |
| Timezone | Asia/Jakarta |

### 15.2 Attendance Settings
| Setting | Opsi |
|---------|------|
| Photo wajib | Ya/Tidak |
| GPS wajib | Ya/Tidak |
| Face recognition wajib | Ya/Tidak |
| Default radius | 100m - 5000m |
| Grace period telat | 0-30 menit |

### 15.3 Payroll Settings
| Setting | Opsi |
|---------|------|
| Tanggal cutoff | 1-28 |
| Tanggal gajian | 25-31 |
| Metode PPh 21 | TER/Progressive |
| Include THR di PPh 21 | Ya/Tidak |

### 15.4 Leave Settings
| Setting | Opsi |
|---------|------|
| Carry forward | Ya/Tidak |
| Max carry forward | 0-12 hari |
| Pro-rata untuk karyawan baru | Ya/Tidak |

### 15.5 Approval Workflows
**Marketing Point:** "Alur approval konfigurabel"

```
Karyawan Submit
      ↓
  Manager (Level 1)
      ↓
  HR Manager (Level 2) [opsional]
      ↓
  Director (Level 3) [opsional]
      ↓
   Approved
```

### 15.6 Roles & Permissions
**Marketing Point:** "Kontrol akses berbasis peran"

**Default Roles:**
| Role | Akses |
|------|-------|
| Admin | Semua fitur |
| HR Manager | HR + Payroll + Reports |
| Manager | Approval + View team |
| Employee | Self-service only |

**Permission granular per fitur:**
- employees.view
- employees.create
- employees.edit
- employees.delete
- payroll.view
- payroll.create
- payroll.approve
- dst...

---

## Tech Stack

### Backend
| Technology | Version | Purpose |
|------------|---------|---------|
| PHP | 8.3.x | Runtime |
| Laravel | 12.x | Framework |
| MySQL | 8.x | Database |
| Laravel Sanctum | 4.x | API Authentication |
| Spatie Permission | 6.x | Role & Permission |
| L5-Swagger | 9.x | API Documentation |

### Frontend
| Technology | Version | Purpose |
|------------|---------|---------|
| Blade | - | Template Engine |
| Tailwind CSS | 4.x | Styling |
| Alpine.js | 3.x | JavaScript Framework |
| Chart.js | 4.x | Dashboard Charts |
| Vite | 6.x | Asset Bundling |

### Mobile
| Technology | Purpose |
|------------|---------|
| Flutter | Cross-platform Mobile App |
| Dart | Programming Language |
| Firebase | Push Notifications |

### Testing
| Technology | Version | Purpose |
|------------|---------|---------|
| Pest PHP | 3.x | Testing Framework |
| Laravel Pint | 1.x | Code Style |

---

## API Documentation

REST API tersedia di `/api/documentation` (Swagger UI)

**Total Endpoints:** 50+ endpoints untuk mobile app

---

## Demo & Trial

### Coba Sekarang (14 Hari Gratis)
1. Kunjungi: [gajipro.jagoflutter.com](https://gajipro.jagoflutter.com)
2. Klik "Daftar Gratis"
3. Isi data perusahaan
4. Langsung pakai!

### Demo Credentials
| Role | Email | Password |
|------|-------|----------|
| Super Admin | superadmin@gajipro.com | password |
| Company Admin | admin@demo.gajipro.com | password |
| HR Manager | hr@demo.gajipro.com | password |
| Employee | karyawan@demo.gajipro.com | password |

---

## Support

- Website Demo: [gajipro.jagoflutter.com](https://gajipro.jagoflutter.com)
- Email: support@gajipro.com
- Source Code: [jagoflutter.com/academy/gajipro](https://jagoflutter.com/academy/gajipro)

---

<p align="center">
  <strong>GajiPro</strong> - Solusi HRIS & Payroll Modern untuk Indonesia
</p>
