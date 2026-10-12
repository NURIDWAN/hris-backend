# JAGOGAJI System - Comprehensive Analysis & Feature Specification

## Executive Summary

Dokumen ini berisi analisis lengkap sistem HRIS (Human Resource Information System) berdasarkan referensi GajiHub, mencakup fitur, flow, dan rekomendasi untuk implementasi JagoGaji.

---

## 1. MODUL UTAMA SISTEM

### 1.1 Dashboard (Beranda)
**Deskripsi:** Halaman utama yang menampilkan ringkasan data perusahaan

**Fitur:**
- Statistik jumlah karyawan (Baru, Aktif, Berhenti Kerja)
- Chart "Lama Kerja" karyawan (0-5 tahun, 5-10 tahun, dst)
- Total Gaji Bulan Ini & Rata-rata Gaji
- Gaji per Organisasi/Departemen
- Jumlah Karyawan Berdasarkan Status
- Quick access ke fitur utama

---

### 1.2 Manajemen Karyawan
**Deskripsi:** Pengelolaan data lengkap karyawan

#### 1.2.1 Data Karyawan
**Informasi Pribadi:**
- ID Karyawan (unique)
- Nama Lengkap
- Jenis Kelamin
- Tempat & Tanggal Lahir
- Status Perkawinan
- Agama
- Golongan Darah
- Kewarganegaraan
- Identitas (KTP/Passport)
- Pendidikan Terakhir (SD, SMP, SMA, D1-D4, S1-S3)
- Foto Profil
- Lampiran Dokumen (max 10MB)

**Informasi Kontak:**
- Nomor HP
- Email
- Alamat KTP
- Alamat Domisili

**Informasi Kepegawaian:**
- Nomor Karyawan
- Status Karyawan (Tetap, Kontrak, Probation, Magang)
- Tanggal Bergabung
- Masa Kerja (auto-calculated)
- Organisasi/Departemen
- Jabatan (Job Title)
- Pangkat/Level
- Jadwal Kerja
- Atasan Langsung

**Informasi Keluarga:**
- Data Pasangan (Nama, Pekerjaan, No HP)
- Data Anak (Nama, Tanggal Lahir, Jenis Kelamin)
- Kontak Darurat

#### 1.2.2 Filter View Karyawan
- Informasi Pribadi
- Kontak
- Kepegawaian
- Payroll
- Login ESS (Employee Self Service)

---

### 1.3 Kehadiran (Attendance)

#### 1.3.1 Presensi/Clock In-Out
**Mobile App:**
- Clock In dengan GPS & Selfie
- Clock Out dengan GPS & Selfie
- Lokasi real-time tracking
- Validasi radius lokasi kerja
- Riwayat presensi harian/bulanan

**Web Admin:**
- Dashboard kehadiran real-time
- Rekap kehadiran per karyawan
- Filter berdasarkan tanggal/departemen
- Export data kehadiran

#### 1.3.2 Jadwal Kerja (Schedule)
- Pengaturan jadwal shift
- Jadwal kerja mingguan/bulanan
- Jadwal libur nasional
- Pengaturan jam kerja fleksibel

#### 1.3.3 Izin & Cuti
**Jenis Cuti:**
- Cuti Tahunan (12 hari/tahun default)
- Cuti Khusus (Nikah, Melahirkan, Keluarga Meninggal, dll)
- Sakit (dengan/tanpa surat dokter)
- Izin (keperluan pribadi)
- Unpaid Leave

**Fitur:**
- Sisa cuti periode ini
- Cuti periode ini diambil
- Sisa cuti periode lalu
- Pengajuan cuti dengan approval workflow
- Progress persetujuan bertingkat
- Lampiran dokumen pendukung

#### 1.3.4 Lembur (Overtime)
- Pengajuan lembur
- Approval lembur
- Perhitungan otomatis uang lembur
- Tarif lembur konfigurabel

---

### 1.4 Payroll (Penggajian)

#### 1.4.1 Komponen Pendapatan
- Gaji Pokok
- Uang Lembur (auto dari data lembur)
- Tunjangan Pulsa
- Tunjangan Transportasi
- Tunjangan Makan
- Tunjangan Jabatan
- Tunjangan Lainnya (custom)
- THR (Tunjangan Hari Raya)
- Tunjangan Cuti

#### 1.4.2 Komponen Potongan
- BPJS Kesehatan
- BPJS Ketenagakerjaan
- PPh 21/26
- Koperasi
- Pinjaman
- Potongan Lainnya (custom)

#### 1.4.3 Proses Payroll
**Status Pembayaran:**
- Belum Siap
- Siap Bayar
- Sudah Bayar

**Fitur:**
- Generate slip gaji bulanan
- Perhitungan otomatis berdasarkan kehadiran
- Batch payment processing
- Integrasi bank untuk transfer
- Cetak/Download slip gaji (PDF)
- Riwayat gaji per karyawan

#### 1.4.4 Pengaturan Payroll
- Slip Gaji (komponen pendapatan & potongan)
- THR (masa kerja minimal, perhitungan pro-rata)
- Tunjangan Cuti
- Lembur (tarif per jam)
- Pajak (PPh 21/26, NPWP)
- BPJS (Kesehatan & Ketenagakerjaan)
- Rekening Perusahaan

---

### 1.5 Laporan (Reports)

#### 1.5.1 Laporan Karyawan
- Daftar karyawan aktif/non-aktif
- Karyawan per departemen
- Karyawan per status

#### 1.5.2 Laporan Kehadiran
- Rekap kehadiran bulanan
- Keterlambatan
- Absensi
- Lembur

#### 1.5.3 Laporan Payroll
- Slip gaji bulanan
- Rekap gaji per departemen
- Laporan PPh 21
- Laporan BPJS

#### 1.5.4 Export Format
- PDF
- Excel
- CSV

---

### 1.6 Akuntansi (Accounting)
- Journal entries untuk gaji
- Integrasi dengan sistem akuntansi
- Laporan biaya karyawan

---

### 1.7 Pengaturan (Settings)

#### 1.7.1 Perusahaan
- Nama & Logo Perusahaan
- Alamat
- NPWP Perusahaan
- Industri

#### 1.7.2 Organisasi
- Struktur organisasi
- Departemen
- Sub-departemen
- Jabatan & Level

#### 1.7.3 Alur Bisnis (Business Flow)
- Approval workflow untuk cuti
- Approval workflow untuk lembur
- Approval workflow untuk reimburse

#### 1.7.4 Billing
- Paket langganan
- Perpanjangan
- Invoice

---

## 2. MOBILE APP (ESS - Employee Self Service)

### 2.1 Fitur Karyawan

#### Home
- Greeting dengan nama karyawan
- Tanggal & jam real-time
- Status kehadiran hari ini
- Quick action buttons

#### Clock In/Out
- Tombol Clock In (pagi)
- Tombol Clock Out (sore)
- Selfie camera dengan face detection
- GPS location capture
- Validasi lokasi (dalam radius kantor)
- Catatan/notes opsional

#### Jadwal (Schedule)
- Kalender bulanan
- Jadwal kerja mingguan
- Jadwal shift
- Hari libur

#### Presensi (Attendance)
- Riwayat kehadiran
- Filter per bulan
- Detail: jam masuk, jam keluar, total jam kerja
- Status: Hadir, Terlambat, Alpha, Cuti

#### Pengajuan (Request)
- Pengajuan Cuti
- Pengajuan Izin
- Pengajuan Lembur
- Pengajuan Reimbursement
- Status pengajuan (Pending, Approved, Rejected)

#### Slip Gaji (Payslip)
- Lihat slip gaji per bulan
- Detail pendapatan & potongan
- Download PDF
- Riwayat slip gaji

#### Profil
- Lihat data pribadi
- Edit data pribadi (terbatas)
- Ganti password
- Pengaturan notifikasi
- Logout

### 2.2 Fitur Approval (untuk Manager/Atasan)
- Daftar pengajuan bawahan
- Approve/Reject dengan catatan
- Notifikasi pengajuan baru

---

## 3. FLOW DIAGRAM

### 3.1 Flow Registrasi & Setup Awal
```
1. Admin mendaftar akun perusahaan
2. Verifikasi email
3. Setup profil perusahaan
4. Setup struktur organisasi (departemen, jabatan)
5. Setup pengaturan payroll
6. Input data karyawan
7. Kirim undangan ke karyawan untuk akses ESS
```

### 3.2 Flow Presensi Harian
```
KARYAWAN:
1. Buka mobile app
2. Klik "Clock In"
3. Ambil selfie
4. Sistem validasi lokasi GPS
5. Jika dalam radius → Clock In berhasil
6. Jika di luar radius → Tampilkan peringatan/tolak

PULANG:
1. Klik "Clock Out"
2. Ambil selfie
3. Validasi lokasi
4. Clock Out berhasil
5. Hitung total jam kerja
```

### 3.3 Flow Pengajuan Cuti
```
1. Karyawan buka app → Pengajuan → Cuti
2. Pilih jenis cuti
3. Pilih tanggal mulai & selesai
4. Input alasan
5. Upload lampiran (jika perlu)
6. Submit pengajuan
7. Notifikasi ke atasan langsung
8. Atasan review & approve/reject
9. Jika multi-level: lanjut ke atasan berikutnya
10. Notifikasi hasil ke karyawan
11. Update sisa cuti
```

### 3.4 Flow Payroll Bulanan
```
1. Admin buka menu Payroll
2. Pilih periode gaji
3. Sistem generate data:
   - Ambil data kehadiran
   - Hitung hari kerja efektif
   - Hitung lembur
   - Hitung potongan (terlambat, alpha, dll)
4. Review slip gaji per karyawan
5. Adjust manual jika perlu
6. Set status "Siap Bayar"
7. Proses pembayaran (transfer bank)
8. Set status "Sudah Bayar"
9. Karyawan bisa akses slip gaji di app
```

---

## 4. REKOMENDASI IMPLEMENTASI JAGOGAJI

### 4.1 Prioritas Fitur (MVP - Minimum Viable Product)

#### Phase 1: Core Features
1. **Authentication & Authorization**
   - Multi-tenant (banyak perusahaan)
   - Role-based access control
   - Employee Self Service login

2. **Manajemen Karyawan**
   - CRUD karyawan
   - Data pribadi & kepegawaian
   - Struktur organisasi

3. **Kehadiran Basic**
   - Clock In/Out dengan GPS
   - Selfie verification
   - Riwayat kehadiran

4. **Cuti & Izin Basic**
   - Pengajuan cuti
   - Single-level approval
   - Sisa cuti tracking

#### Phase 2: Payroll
5. **Payroll Basic**
   - Komponen gaji konfigurabel
   - Generate slip gaji
   - Export PDF

6. **Laporan Basic**
   - Laporan kehadiran
   - Laporan cuti
   - Laporan gaji

#### Phase 3: Advanced Features
7. **Multi-level Approval**
8. **Lembur Management**
9. **BPJS & PPh 21 Integration**
10. **THR Calculation**
11. **Reimbursement**
12. **Advanced Reports & Analytics**

### 4.2 Tech Stack (FINAL)

> **Lihat juga:** [DESIGN_DIFFERENTIATION.md](./DESIGN_DIFFERENTIATION.md) untuk detail design system

#### Backend & Frontend Web
| Technology | Version | Purpose |
|------------|---------|---------|
| **Laravel** | 12.x | Backend framework |
| **Tailwind CSS** | 4.x | Styling |
| **Alpine.js** | 3.x | JavaScript interactions |
| **Blade** | - | Templating engine |
| **Laravel Sanctum** | - | API authentication |
| **Spatie Permission** | - | Role-based access control |
| **Laravel Excel** | - | Export functionality |
| **DomPDF** | - | Generate slip gaji PDF |

#### Database
| Technology | Version | Purpose |
|------------|---------|---------|
| **MySQL** | 8.x | Primary database |
| **Redis** | - | Cache & queue |

#### Mobile App (Employee Self Service)
| Technology | Version | Purpose |
|------------|---------|---------|
| **Flutter** | 3.x | Cross-platform mobile |
| **Dio** | - | HTTP client |
| **Riverpod/Provider** | - | State management |

#### Infrastructure
- S3/MinIO untuk file storage
- Queue dengan database/Redis driver
- Docker untuk development

### 4.3 Database Design Consideration

#### Core Tables
- companies (multi-tenant)
- users (authentication)
- employees (data karyawan)
- departments
- positions (jabatan)
- schedules

#### Attendance Tables
- attendances (clock in/out)
- attendance_locations
- leaves (cuti)
- leave_types
- leave_approvals
- overtimes

#### Payroll Tables
- payroll_periods
- payroll_items (slip gaji)
- salary_components
- deductions
- allowances

---

## 5. ROLES & PERMISSIONS (Akan Didiskusikan)

### 5.1 Proposed Roles

| Role | Level | Access |
|------|-------|--------|
| Super Admin | System | Full system access, multi-tenant management |
| Company Admin | Company | Full company access, settings, all modules |
| HR Manager | Company | Employee management, payroll, reports |
| HR Staff | Company | Employee data, attendance, limited payroll |
| Department Head | Department | Team management, approval, dept reports |
| Manager | Team | Team attendance, approval bawahan |
| Employee | Self | ESS only, own data |

### 5.2 Permission Categories
- **Employee**: view, create, edit, delete
- **Attendance**: view, manage, approve
- **Leave**: view, request, approve
- **Payroll**: view, generate, approve, pay
- **Report**: view, export
- **Settings**: view, manage

---

## 6. PERTANYAAN UNTUK DISKUSI

Sebelum memulai implementasi, perlu klarifikasi:

1. **Multi-tenant atau Single Company?**
   - Apakah sistem untuk satu perusahaan atau platform SaaS untuk banyak perusahaan?

2. **Mobile App Approach?**
   - Native (Flutter/React Native) atau PWA?
   - iOS dan Android atau salah satu dulu?

3. **Approval Workflow Complexity?**
   - Single level atau multi-level approval?
   - Berapa maksimal level approval?

4. **Payroll Complexity?**
   - Apakah perlu integrasi BPJS otomatis?
   - Apakah perlu perhitungan PPh 21 otomatis?
   - Apakah perlu integrasi bank untuk transfer?

5. **Attendance Validation?**
   - GPS only atau perlu Face Recognition?
   - Berapa radius toleransi lokasi?
   - Apakah perlu multiple office location?

6. **Integration Requirements?**
   - Integrasi dengan sistem accounting?
   - Integrasi dengan sistem lain?

7. **Reporting Needs?**
   - Custom report builder atau predefined reports?
   - Real-time dashboard atau periodic?

---

## 7. DESIGN DIFFERENTIATION

JagoGaji dirancang untuk **BERBEDA** dari kompetitor:

### Vs GajiHub
| Aspek | GajiHub | JagoGaji |
|-------|---------|----------|
| Warna | Pink (#E91E63) | **Ocean Blue (#3b82f6)** |
| Style | Soft, feminine | **Professional, modern** |
| Layout | Traditional | **SPA dengan Inertia** |
| Buttons | Rounded | **Pill shape gradient** |

### Vs Ultimate HRD (JagoHRIS)
| Aspek | Ultimate HRD | JagoGaji |
|-------|--------------|----------|
| Warna | Green (#10b981) | **Blue (#3b82f6)** |
| Framework | Bootstrap + jQuery | **Tailwind + Alpine.js** |
| Payroll | Tidak ada | **Full dengan PPh 21 & BPJS** |
| Multi-tenant | Tidak | **Ya (SaaS ready)** |

> **Detail lengkap:** Lihat [DESIGN_DIFFERENTIATION.md](./DESIGN_DIFFERENTIATION.md)

---

## 8. NEXT STEPS

1. Finalisasi roles & permissions
2. Setup Tailwind design system dengan Ocean Blue theme
3. Setup database schema (multi-tenant ready)
4. Implementasi authentication & authorization
5. Build core employee management
6. Build attendance module
7. Build leave management
8. Build payroll module (dengan PPh 21 & BPJS)
9. Build Flutter mobile app
10. Testing & QA
11. Deployment

---

## 9. RELATED DOCUMENTS

- [ROLES_PERMISSIONS.md](./ROLES_PERMISSIONS.md) - Detail roles & permissions
- [DESIGN_DIFFERENTIATION.md](./DESIGN_DIFFERENTIATION.md) - Design system & visual identity

---

*Document Version: 1.1*
*Created: 2026-02-11*
*Updated: 2026-02-11*
*Author: AI Project Manager*
