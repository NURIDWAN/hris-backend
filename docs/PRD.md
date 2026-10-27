# Product Requirements Document (PRD)
# GajiPro — HRIS & Payroll System
### Single-Tenant Edition untuk PT Gemilang Sari Husada

---

> **Versi**: 1.0.0-gemilang
> **Tanggal**: Oktober 2026
> **Vendor**: AcademyJF
> **Client**: PT Gemilang Sari Husada
> **Status**: In Development

---

## 1. Ringkasan Eksekutif

GajiPro adalah sistem HRIS (Human Resource Information System) dan Payroll berbasis web yang dirancang khusus untuk pasar Indonesia. Edisi ini adalah single-tenant deployment yang didedikasikan untuk PT Gemilang Sari Husada, berjalan di server on-premise / dedicated milik client.

**Tujuan utama:**
- Otomatisasi proses HR dan penggajian yang sebelumnya dilakukan manual
- Memastikan kepatuhan perpajakan (PPh 21 / TER) dan BPJS sesuai regulasi Indonesia
- Memberikan transparansi kehadiran karyawan dengan validasi GPS + Face Recognition
- Menyediakan Employee Self-Service (ESS) melalui mobile app Flutter

**Target pengguna:** ~100–500 karyawan PT Gemilang Sari Husada.

---

## 2. Konteks & Latar Belakang

### 2.1 Masalah yang Diselesaikan

| Masalah | Dampak |
|---------|--------|
| Absensi manual / titip absen | Data kehadiran tidak akurat, fraud |
| Payroll dihitung manual di Excel | Error manusia, proses lambat, tidak audit-proof |
| PPh 21 & BPJS dihitung terpisah | Risiko salah hitung, denda pajak |
| Pengajuan cuti/lembur via chat/kertas | Tidak ada audit trail, lambat |
| Slip gaji dibagikan manual | Bocor data gaji, tidak efisien |

### 2.2 Ruang Lingkup (In Scope)

- Web admin dashboard (HR, Finance, Manager, Owner)
- Mobile app Flutter (Employee Self-Service)
- Semua modul inti: Karyawan, Kehadiran, Cuti, Lembur, Payroll, Pajak, BPJS, THR, Reimbursement, Laporan
- Deployment on-premise di server client

### 2.3 Di Luar Ruang Lingkup (Out of Scope)

- Integrasi dengan sistem ERP / akuntansi eksternal
- Custom report builder di luar yang sudah ada
- Migrasi data dari sistem lama (assessment terpisah)
- SLA support 24/7 (dibahas di kontrak maintenance)
- Multi-subsidiary / multi-company

---

## 3. Pengguna & Roles

### 3.1 Hierarki Role

```
Company Owner
├── Admin HR
│   └── (opsional) Finance
├── Manager / Supervisor
└── Employee
```

### 3.2 Detail Akses per Role

| Role | Scope | Akses Utama |
|------|-------|-------------|
| **Company Owner** | Seluruh perusahaan | Full access semua modul + approval payroll |
| **Admin HR** | Seluruh perusahaan | Employee, Attendance, Leave, Payroll (generate), Reports |
| **Finance** *(opsional)* | Data finansial | Payroll full, laporan pajak & BPJS, tanpa akses data pribadi karyawan |
| **Manager** | Tim sendiri | Approval cuti/lembur tim, lihat kehadiran tim |
| **Employee** | Data diri sendiri | ESS via mobile: absen, cuti, lembur, slip gaji |

---

## 4. Fitur & Persyaratan Fungsional

### 4.1 Manajemen Karyawan

**Prioritas: High**

- CRUD data karyawan lengkap (52+ field): informasi pribadi, kontak, kepegawaian, keluarga, darurat
- Status karyawan: Tetap, Kontrak, Probation, Magang
- Upload foto profil & dokumen (KTP, NPWP, dll., max 10MB)
- Employee ID auto-generate
- Masa kerja auto-kalkulasi
- Org chart visual (hierarki departemen & jabatan)
- Activity log setiap perubahan data
- Import bulk karyawan dari Excel dengan validasi
- Export ke Excel & PDF
- Lifecycle karyawan: onboarding → aktif → resign / PHK (EmployeeExit)

### 4.2 Kehadiran (Attendance)

**Prioritas: High**

#### Clock In / Out
- Wajib selfie foto saat clock in & out
- Timestamp dari server (tidak bisa dimanipulasi)
- Validasi GPS dengan Haversine formula — default radius 100m, konfigurabel 50m–5000m per lokasi
- Support unlimited lokasi kantor
- Auto-tag: `on_time`, `late`, `early_leave`, `overtime`
- Kalkulasi telat = clock in - jadwal masuk (jika positif)

#### Face Recognition
- Face embedding dari 3 sudut (depan, kiri, kanan)
- Confidence score 0–100%, threshold default 85%
- Liveness detection (anti-foto foto)
- Re-enrollment jika wajah berubah

#### Jadwal Kerja
- Tipe: Reguler, Shift Pagi/Siang/Malam, Flexible
- Konfigurasi: jam masuk/pulang, durasi istirahat, grace period, hari kerja
- Kalender libur nasional

#### Web Admin
- Dashboard kehadiran real-time
- Rekap & koreksi kehadiran manual
- Filter per tanggal, departemen, karyawan
- Export laporan kehadiran (Excel/PDF)

### 4.3 Cuti & Izin (Leave Management)

**Prioritas: High**

- Jenis cuti: Tahunan, Sakit, Nikah, Melahirkan, Keluarga Meninggal, Izin, Unpaid Leave, dan custom
- Kuota cuti per jenis, konfigurabel per karyawan
- Carry-forward sisa cuti periode lalu
- Cuti setengah hari (half-day)
- Upload lampiran pendukung (surat dokter, dll.)
- Approval workflow single-level (ke atasan langsung)
- Notifikasi push & email ke approver dan karyawan
- Auto-update saldo cuti setelah approved
- Sinkronisasi otomatis ke data kehadiran

### 4.4 Lembur (Overtime)

**Prioritas: High**

- Pengajuan lembur via mobile / web
- Approval oleh manager
- Kalkulasi otomatis uang lembur berdasarkan UU Ketenagakerjaan:
  - Weekday: 1.5× tarif jam pertama, 2× jam berikutnya
  - Weekend/libur: 2× jam pertama, 3× jam berikutnya (setelah 8 jam)
- Tarif lembur konfigurabel per perusahaan
- Hasil lembur otomatis masuk ke payroll

### 4.5 Penggajian (Payroll)

**Prioritas: High (Critical)**

#### Komponen Gaji
- **Pendapatan**: Gaji Pokok, Tunjangan Jabatan, Transportasi, Makan, Pulsa, Lembur, THR, Reimbursement, dan komponen custom
- **Potongan**: BPJS Kesehatan, BPJS Ketenagakerjaan (JHT, JKK, JKM, JP), PPh 21, Kasbon/Pinjaman, Koperasi, dan potongan custom

#### Proses Payroll
1. Buat periode payroll
2. Generate batch otomatis (ambil data kehadiran, lembur, cuti)
3. Hitung: gaji pokok + tunjangan + lembur - potongan - pajak - BPJS
4. Review & adjustment manual per karyawan
5. Approve (oleh Owner)
6. Export file transfer bank
7. Set status "Sudah Bayar"

#### Status Payroll: `draft` → `generated` → `approved` → `paid`

#### Slip Gaji
- Generate PDF per karyawan
- Template slip gaji dengan logo perusahaan
- Email otomatis ke karyawan
- Karyawan bisa download via mobile app
- Riwayat slip per bulan

#### Export Bank
- Format CSV/Excel untuk transfer massal via internet banking
- Support format bank umum Indonesia

### 4.6 Perpajakan (PPh 21)

**Prioritas: High (Critical)**

- **Metode TER** (Tarif Efektif Rata-rata) — sesuai PMK 168/2023, default
- **Metode Progresif** — fallback jika TER tidak applicable
- PTKP otomatis sesuai status kawin (TK/K/K/I) dan jumlah tanggungan
- Penalti NPWP: tarif 20% lebih tinggi jika tidak punya NPWP
- SPT 1721 — laporan pajak tahunan (export)
- Bukti Potong 1721-A1 per karyawan (PDF)
- Laporan PPh 21 bulanan

### 4.7 BPJS

**Prioritas: High (Critical)**

#### BPJS Kesehatan
- Iuran karyawan: 1% gaji (capped sesuai regulasi)
- Iuran perusahaan: 4% gaji
- Konfigurabel batas atas gaji dasar

#### BPJS Ketenagakerjaan
- **JHT**: Karyawan 2%, Perusahaan 3.7%
- **JKK**: Perusahaan 0.24%–1.74% (tergantung risiko industri, model `JkkRiskRate`)
- **JKM**: Perusahaan 0.3%
- **JP**: Karyawan 1%, Perusahaan 2% (capped)
- Laporan iuran BPJS per bulan (Excel)
- Summary BPJS per karyawan

### 4.8 THR (Tunjangan Hari Raya)

**Prioritas: High**

- Hitung otomatis: 1× gaji untuk masa kerja ≥ 12 bulan
- Pro-rata untuk masa kerja < 12 bulan: (bulan kerja / 12) × gaji
- Konfigurasi masa kerja minimal
- Batch processing untuk semua karyawan
- THR slip terpisah dari payroll reguler
- Export untuk transfer bank

### 4.9 Reimbursement

**Prioritas: Medium**

- Kategori reimbursement konfigurabel (pulsa, transportasi, kesehatan, dll.)
- Batas maksimal per kategori
- Upload bukti pembayaran / struk
- Approval workflow
- Integrasi ke payroll bulan berjalan
- Laporan reimbursement

### 4.10 Laporan & Export

**Prioritas: High**

| Laporan | Format |
|---------|--------|
| Laporan Karyawan (list, per dept, per status) | Excel, PDF |
| Rekap Kehadiran Bulanan | Excel, PDF |
| Laporan Keterlambatan | Excel |
| Laporan Cuti (saldo, penggunaan, per jenis) | Excel, PDF |
| Laporan Payroll (summary, per dept) | Excel, PDF |
| Laporan PPh 21 Bulanan & Tahunan | Excel |
| SPT 1721 | Excel (format DJP) |
| Bukti Potong 1721-A1 | PDF |
| Laporan BPJS | Excel |
| File Transfer Bank | CSV |

### 4.11 Employee Self-Service (Mobile App Flutter)

**Prioritas: High**

- **Platform**: Android (Google Play), iOS
- **Auth**: Login dengan token Sanctum, auto-logout
- **Fitur Karyawan**:
  - Clock in/out dengan kamera selfie + GPS
  - Riwayat kehadiran & kalender bulanan
  - Pengajuan cuti, izin, lembur, reimbursement
  - Status pengajuan (Pending / Approved / Rejected)
  - Lihat & download slip gaji PDF
  - Profil & ubah password
- **Fitur Manager** (conditional):
  - Daftar pengajuan bawahan
  - Approve / Reject dengan catatan
- **Push Notification** via FCM untuk semua status pengajuan
- **Offline support**: riwayat kehadiran & slip gaji bisa dilihat offline

### 4.12 Settings & Konfigurasi

**Prioritas: High**

- **Company Profile**: Nama, logo, NPWP perusahaan, alamat, industri
- **Organisasi**: Departemen, sub-departemen, jabatan, level/grade
- **Lokasi Kantor**: GPS koordinat, radius per lokasi
- **Jadwal Kerja**: Template shift, hari kerja, kalender libur
- **Payroll Settings**: Komponen gaji, metode pajak, BPJS, rekening perusahaan
- **Leave Settings**: Jenis cuti, kuota, carry-forward rules
- **Lembur Settings**: Tarif, hari kerja vs libur
- **Approval Workflow**: Konfigurasi alur approval multi-level per modul
- **Roles & Permissions**: Custom permission per role/user

---

## 5. Persyaratan Non-Fungsional

### 5.1 Performa
- Response time dashboard < 2 detik (target)
- Uptime > 99% dalam 30 hari pertama go-live
- Support 100–500 karyawan simultan

### 5.2 Keamanan
- HTTPS wajib (SSL)
- Laravel Sanctum untuk API authentication
- Spatie Permission untuk RBAC
- Rate limiting & IP blocking (`BlockedIp`, `RateLimitLog`)
- Security log (`SecurityLog`) untuk semua aksi sensitif
- Multi-tenant company scope — semua query di-scope by `company_id`
- Tidak ada public registration (admin creates all users)
- Password policy (min length, reset flow)

### 5.3 Ketersediaan Data
- Backup database otomatis (jadwal harian, retensi 7 hari)
- Rollback plan saat go-live

### 5.4 Skalabilitas
- Queue untuk proses berat: generate payroll, kirim email, push notif (Laravel Queue + Redis)
- Cache (Redis) untuk data yang sering diakses

### 5.5 Compliance
- PPh 21 sesuai PMK 168/2023 (metode TER)
- BPJS sesuai regulasi terbaru
- Penyimpanan data karyawan sesuai UU PDP

---

## 6. Tech Stack

### Backend & Web
| Komponen | Teknologi | Versi |
|----------|-----------|-------|
| Framework | Laravel | 12.x |
| Language | PHP | 8.3 |
| Frontend | Blade + Alpine.js + Tailwind CSS | v3 / v4 |
| Auth | Laravel Sanctum | — |
| RBAC | Spatie Permission | — |
| PDF | DomPDF | — |
| Excel | Laravel Excel | — |
| Queue | Redis | — |
| Storage | S3 / MinIO | — |

### Database
| Komponen | Teknologi |
|----------|-----------|
| Primary DB | MySQL 8.x |
| Cache & Queue | Redis |

### Mobile
| Komponen | Teknologi |
|----------|-----------|
| Framework | Flutter 3.x |
| HTTP Client | Dio |
| State Management | Riverpod / Provider |
| Notifikasi | Firebase FCM |

### Testing
| Komponen | Teknologi |
|----------|-----------|
| PHP Testing | Pest PHP v3 |
| Test Files | 129 file |

---

## 7. Arsitektur Utama

```
                    ┌─────────────┐
                    │  Web Admin  │
                    │  (Blade)    │
                    └──────┬──────┘
                           │
┌──────────────┐    ┌──────▼──────┐    ┌─────────────┐
│ Flutter App  │────│   Laravel   │────│  MySQL DB   │
│ (Mobile ESS) │    │   Backend   │    │             │
└──────────────┘    └──────┬──────┘    └─────────────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
         ┌────▼───┐  ┌─────▼───┐  ┌────▼───┐
         │ Redis  │  │  S3/    │  │  FCM   │
         │(Cache/ │  │ MinIO   │  │ (Push) │
         │ Queue) │  │(Storage)│  └────────┘
         └────────┘  └─────────┘
```

### Single-Tenant Lock
- `SINGLE_TENANT_MODE=true` di `.env`
- `CompanyObserver` mencegah pembuatan > 1 company
- Public registration dinonaktifkan
- Billing / subscription UI dinonaktifkan
- Superadmin panel tersembunyi

---

## 8. Database — Model Utama

| Domain | Model Kunci |
|--------|-------------|
| Auth | `User`, `Company` |
| Karyawan | `Employee`, `Department`, `Position`, `EmployeeDocument`, `EmployeeExit` |
| Kehadiran | `Attendance`, `OfficeLocation`, `WorkSchedule`, `Holiday`, `EmployeeFaceEmbedding`, `FaceVerificationLog` |
| Cuti | `LeaveRequest`, `LeaveType`, `LeaveBalance` |
| Lembur | `OvertimeRequest`, `OvertimeSetting` |
| Payroll | `Payroll`, `PayrollItem`, `PayrollItemDetail`, `SalaryComponent`, `EmployeeSalary`, `PayrollSetting` |
| Pajak | `Pph21Setting`, `Pph21TerRate`, `Pph21Rate`, `PtkpSetting`, `Spt1721`, `TaxForm1721A1` |
| BPJS | `BpjsKesSetting`, `BpjsTkSetting`, `JkkRiskRate` |
| THR | `ThrSetting`, `ThrPayment` |
| Reimbursement | `Reimbursement`, `ReimbursementCategory` |
| Approval | `ApprovalWorkflow`, `ApprovalWorkflowStep`, `ApprovalRecord` |
| Notifikasi | `Notification`, `DeviceToken` |

---

## 9. Alur Bisnis Utama

### 9.1 Payroll Bulanan
```
Admin HR buat periode → Generate batch →
  System kalkulasi (kehadiran + lembur + cuti + PPh21 + BPJS) →
  Review & adjust manual → Owner approve →
  Export file bank → Set "Sudah Bayar" →
  Karyawan akses slip via mobile
```

### 9.2 Absensi Harian
```
Karyawan buka app → Klik Clock In →
  Ambil selfie → Face Recognition (≥85%) →
  GPS capture → Validasi radius kantor →
  Berhasil: catat waktu | Gagal: peringatan
```

### 9.3 Pengajuan Cuti
```
Karyawan submit → Notifikasi ke Manager →
  Manager approve/reject + catatan →
  Notifikasi ke Karyawan →
  Jika approved: update saldo cuti + tandai absensi
```

### 9.4 Approval Flow (General)
- **Cuti / Izin**: Employee → Manager (single-level)
- **Lembur**: Employee → Manager
- **Reimbursement**: Employee → Admin HR / Manager
- **Payroll**: Admin HR generate → Owner approve

---

## 10. Timeline Proyek

| Fase | Keterangan | Durasi |
|------|-----------|--------|
| Minggu 1–2 | Konversi multi→single-tenant, disable billing, rebrand | 2 minggu |
| Minggu 3 | Security hardening, bug fix, performance tuning | 1 minggu |
| Minggu 4 | Script installer, backup automation, staging deploy | 1 minggu |
| Minggu 5 | UAT bersama admin client, bug fixing | 1 minggu |
| Minggu 6 | Go-live production, training admin (2 sesi), handover | 1 minggu |

**Start Date**: 16 April 2026
**Target Go-Live**: ~6 minggu dari kickoff

---

## 11. Kriteria Sukses

- [ ] Semua modul inti berjalan: Employee, Attendance, Leave, Overtime, Payroll, Tax, BPJS, THR
- [ ] Admin client bisa operasikan sistem mandiri tanpa bantuan harian
- [ ] Payroll bulan pertama berhasil diproses dan dibayarkan tepat waktu
- [ ] Uptime > 99% dalam 30 hari pertama
- [ ] Response time dashboard < 2 detik
- [ ] Tidak ada data breach dalam 30 hari pertama
- [ ] Feedback score admin ≥ 8/10

---

## 12. Risiko

| Risiko | Probabilitas | Impact | Mitigasi |
|--------|:---:|:---:|---------|
| Bug saat konversi single-tenant | Sedang | Tinggi | Test coverage 129 file + UAT rigid |
| Data awal client terlambat | Sedang | Sedang | Template Excel import siap dari Week 3 |
| Server spec tidak memadai | Rendah | Tinggi | Verifikasi min 4 CPU / 8GB RAM sebelum start |
| Perubahan requirements mid-project | Tinggi | Sedang | Change request formal + biaya tambahan |
| Downtime saat go-live | Rendah | Tinggi | Go-live akhir pekan, rollback plan siap |

---

## 13. Deliverables

### Kode & Aplikasi
- Source code GajiPro single-tenant (private repo)
- Mobile app Flutter (APK / Play Store)
- Script deploy (`deploy.sh`)

### Dokumentasi
- README instalasi
- Admin Manual (Bahasa Indonesia)
- Employee Manual (ESS)
- Maintenance & Backup Guide
- API Documentation (endpoint mobile)

### Konfigurasi Server
- `.env.production` template
- Nginx config
- Systemd service files (queue, scheduler)
- Backup cron config
- SSL setup

### Training
- Live training 2 sesi (admin + payroll)
- Video tutorial per modul (5–10 menit)

---

*PRD Version: 1.0*
*Dibuat: Oktober 2026*
*Author: AcademyJF / AI Project Manager*
