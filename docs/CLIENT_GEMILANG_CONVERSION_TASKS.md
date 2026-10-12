# Conversion Tasks: GajiPro → Single-Tenant untuk PT Gemilang Sari Husada

> **Reference**: `CLIENT_GEMILANG_MASTER_PLAN.md`
> **Pendekatan**: Opsi B — Locked Single Company (hide multi-tenant, keep schema)
> **Target**: Aplikasi production-ready untuk PT Gemilang Sari Husada

Gunakan checklist ini sebagai master tracker. Centang `[x]` setiap task selesai. Tulis PR/commit hash di kolom catatan untuk trail.

---

## Phase 1: Persiapan Project (Week 1, Day 1-2)

### 1.1 Setup Repository & Branch
- [ ] Fork/clone GajiPro ke repo baru: `gajipro-gemilang` (private)
- [ ] Buat branch utama `production` & `staging`
- [ ] Buat `.env.production.example` dengan placeholder untuk client
- [ ] Tambah `CLIENT.md` di root dengan nama client + versi edisi
- [ ] Setup CI/CD pipeline (GitHub Actions atau alternatif) untuk auto-test

### 1.2 Environment Setup Development
- [ ] Clone repo ke local dev
- [ ] Jalankan `composer install && npm install && npm run build`
- [ ] Setup `.env` untuk dev local
- [ ] `php artisan migrate:fresh --seed` — verifikasi baseline jalan
- [ ] Jalankan `php artisan test --compact` — semua baseline test harus pass

### 1.3 Baseline Audit
- [ ] Jalankan full test suite, catat hasil di `docs/internal/baseline-test-result.md`
- [ ] Catat versi dependencies (`composer.lock`, `package-lock.json`)
- [ ] Identifikasi TODO/FIXME di codebase (`grep -rn "TODO\|FIXME" app/`)
- [ ] Buat list known issues di `docs/internal/known-issues.md`

---

## Phase 2: Konversi Single-Tenant (Week 1, Day 3-5)

### 2.1 Lock Company ID
- [ ] Tambah config baru `config/tenant.php`:
  ```php
  return [
      'single_mode' => env('SINGLE_TENANT_MODE', true),
      'company_id' => env('SINGLE_TENANT_COMPANY_ID', 1),
  ];
  ```
- [ ] Tambah env var: `SINGLE_TENANT_MODE=true`, `SINGLE_TENANT_COMPANY_ID=1` di `.env.example`
- [ ] Buat service/helper `app('tenant')` auto-resolve ke company locked
- [ ] Update `SetTenant` middleware: jika single_mode, skip subscription check, selalu set company_id lock

### 2.2 Disable Multi-Tenant Registration
- [ ] Hapus route `/register` (atau redirect ke `/login`)
- [ ] Hapus link "Daftar" di halaman login
- [ ] Hapus `RegisterController` atau disable public method
- [ ] Update `/landing` page — hapus CTA register, ganti jadi "Hubungi Admin"
- [ ] Test: akses `/register` → harus 404 atau redirect

### 2.3 Disable Billing & Subscription
- [ ] Hapus/disable route group `/settings/billing/*`
- [ ] Hapus route `/subscription-expired`
- [ ] Hapus sidebar menu "Billing" di `layouts/admin.blade.php`
- [ ] Update `SetTenant` middleware: skip check `isSubscriptionActive()` saat `single_mode=true`
- [ ] Set company subscription ke forever active di seeder (atau hapus dependency ke subscription)
- [ ] Hapus link "Upgrade Plan" dari dashboard

### 2.4 Hide/Disable Superadmin Panel
- [ ] Hapus route group `/superadmin/*` (atau gate via env var `APP_ADMIN_MODE=dev`)
- [ ] Hapus sidebar "Superadmin" link
- [ ] Buat user dengan role `admin` (bukan superadmin) sebagai level tertinggi
- [ ] Hapus halaman `/superadmin/login`
- [ ] Test: akses `/superadmin` → 404

### 2.5 Remove Payment Gateway
- [ ] Hapus `PaymentGatewayService`, `PaymentController`, `WebhookController`
- [ ] Hapus `api/webhooks/xendit` & `api/webhooks/midtrans` routes
- [ ] Hapus `PaymentGatewaySetting` model + migration (atau biarkan kosong)
- [ ] Hapus `Payment`, `Invoice` (opsional — bisa dipertahankan untuk internal record)
- [ ] Hapus `payment_gateways` dari seeder
- [ ] Hapus halaman setting payment gateway

### 2.6 Simplify Sidebar & Menu
- [ ] Hapus menu Superadmin, Billing, Subscription
- [ ] Hapus menu "Company Selector" (tidak ada multi-company)
- [ ] Reorder menu sesuai prioritas: Dashboard, Karyawan, Kehadiran, Cuti, Payroll, Laporan, Setting
- [ ] Sesuaikan permission check jika ada menu yang tadinya cek superadmin

### 2.7 Update Seeder untuk Single Company
- [ ] Buat seeder baru: `GemilangCompanySeeder`
  - Nama: `PT Gemilang Sari Husada`
  - Slug: `gemilang-sari-husada`
  - Email: (dari client)
  - Alamat: (dari client)
  - NPWP: (dari client)
  - Logo: placeholder, upload pas onboarding
- [ ] Register seeder ke `DatabaseSeeder::run()`
- [ ] Hapus/comment `DemoBillingSeeder` dan `PaymentGatewaySeeder`
- [ ] Buat `GemilangAdminUserSeeder` untuk admin awal:
  - Email: `admin@gemilangsarihusada.co.id` (atau sesuai client)
  - Password default: random, ditampilkan di output seeder
- [ ] Test: `php artisan migrate:fresh --seed` → verifikasi 1 company, 1 admin

### 2.8 Company Profile Lock
- [ ] Di `/settings/company` — admin boleh UPDATE profil, tapi TIDAK BOLEH delete company
- [ ] Hapus fitur "switch company" (jika ada)
- [ ] Tambah validation: hanya boleh ada 1 record di `companies` table (via observer)

### 2.9 Update Config
- [ ] `config/app.php` — set `APP_NAME` ke nama aplikasi (misal "HR Gemilang" atau tetap "GajiPro")
- [ ] `config/mail.php` — defaultkan ke SMTP client
- [ ] `config/session.php` — sesuaikan lifetime (default 120 menit OK)
- [ ] `config/cache.php` — pakai database/redis sesuai server client

### 2.10 Clean-up Test Suite
- [ ] Hapus test yang terkait multi-tenant registration
- [ ] Hapus test yang terkait billing/subscription/payment
- [ ] Hapus test superadmin
- [ ] Update tenant isolation test → tetap pertahankan (defense in depth)
- [ ] Semua test harus pass: `php artisan test --compact`

---

## Phase 3: Rebrand & Customization (Week 2)

### 3.1 Branding Assets (Coordination dengan Client)
- [ ] Dapatkan dari client:
  - Logo (SVG + PNG berbagai ukuran)
  - Favicon
  - Warna brand (primary, secondary)
  - Font preferensi (jika ada)
  - Tone of voice/copywriting preferensi
- [ ] Simpan di `public/images/brand/`
- [ ] Update `resources/css/app.css` dengan warna brand
- [ ] Update `resources/views/components/logo.blade.php`
- [ ] Update favicon di layout

### 3.2 Rebrand UI
- [ ] Login page — pasang logo Gemilang
- [ ] Dashboard header — pasang logo
- [ ] Email template — update header dengan logo
- [ ] PDF slip gaji — update dengan logo
- [ ] PDF tax form (1721-A1) — update dengan logo
- [ ] Footer copyright — "© PT Gemilang Sari Husada — Powered by [Dev Name]"

### 3.3 Kustomisasi Konten
- [ ] Homepage (`/` atau `/login`) — pesan welcome khusus
- [ ] Email template (welcome, reset password) — ganti nama app, logo
- [ ] Error pages (404, 500, 503) — dengan branding client
- [ ] Maintenance page — dengan logo client

### 3.4 Default Data untuk Client
- [ ] Departemen default sesuai struktur Gemilang (koordinasi dengan HR Gemilang)
- [ ] Posisi default
- [ ] Leave types default:
  - Cuti Tahunan (12 hari)
  - Cuti Sakit
  - Cuti Melahirkan
  - Cuti Menikah
  - Cuti Duka
  - Izin
- [ ] Work schedules default:
  - Shift Pagi (08:00 - 17:00)
  - Shift Malam (jika applicable)
- [ ] Holiday default 2026 (libur nasional Indonesia)
- [ ] Salary components default:
  - Gaji Pokok
  - Tunjangan Jabatan
  - Tunjangan Transport
  - Tunjangan Makan
  - BPJS TK (potongan)
  - BPJS Kes (potongan)
  - PPh 21 (potongan)
- [ ] Pph21 settings (TER method, tahun 2026)
- [ ] PTKP settings (TK/0, K/0, K/1, K/2, K/3)
- [ ] BPJS settings (persentase terbaru)

### 3.5 Buat Seeder Lengkap
- [ ] `GemilangInitialDataSeeder` gabung semua seeder di atas
- [ ] Jalankan & verify: semua data default muncul di UI
- [ ] Dokumentasi: apa saja default data yang di-seed

---

## Phase 4: Security Hardening (Week 3)

### 4.1 Code Security Audit
- [ ] Review semua controller — pastikan `$tenant->id` tetap di-query (defense in depth)
- [ ] Review semua Form Request — unique/exists di-scope ke company
- [ ] Audit `DB::raw()` dan query raw — escape properly
- [ ] Cek file upload validation (MIME type, size, path tenant isolation)
- [ ] Pastikan `.env` tidak accidentally di-commit
- [ ] Rotate semua API key di `.env.example` (jangan bocor di git)

### 4.2 Attack Detection Active
- [ ] `DetectAttack` middleware aktif di web group
- [ ] `CheckBlockedIp` middleware aktif
- [ ] Security logs aktif dengan retention minimal 90 hari
- [ ] IP auto-block threshold: 5 critical attack dalam 1 jam
- [ ] Set up alert email saat ada critical attack

### 4.3 Authentication Hardening
- [ ] Password policy: min 8 char, mix upper/lower/number
- [ ] Rate limiting login: max 5 percobaan per menit per IP
- [ ] Account lockout: 10x gagal → lock 30 menit
- [ ] Session timeout: 120 menit idle
- [ ] Force HTTPS di production (env: `APP_FORCE_HTTPS=true`)
- [ ] Cookie flags: `secure`, `httponly`, `samesite=lax`
- [ ] CSRF token di semua form (Laravel default, verify aktif)

### 4.4 Add 2FA (Optional tapi Recommended)
- [ ] Integrasi `laravel/fortify` atau `pragmarx/google2fa`
- [ ] UI setup 2FA di profile page
- [ ] Paksa 2FA untuk role admin (optional)
- [ ] Backup codes generator

### 4.5 File Upload Security
- [ ] Validate MIME type (whitelist, bukan blacklist)
- [ ] Max file size limits
- [ ] Random filename generation (hindari collision & enumeration)
- [ ] Path traversal prevention
- [ ] Antivirus scan (opsional, pakai ClamAV di server)

### 4.6 Dependency Security
- [ ] `composer audit` — fix semua vulnerable packages
- [ ] `npm audit fix` — fix semua vulnerable npm packages
- [ ] Pin versi dependency di production (no wildcard `*`)
- [ ] Setup Dependabot/Renovate untuk auto-update notification

### 4.7 HTTP Security Headers
- [ ] `Content-Security-Policy`
- [ ] `X-Frame-Options: SAMEORIGIN`
- [ ] `X-Content-Type-Options: nosniff`
- [ ] `Strict-Transport-Security` (HSTS)
- [ ] `Referrer-Policy: strict-origin-when-cross-origin`
- [ ] Pakai package `spatie/laravel-csp` atau custom middleware

### 4.8 Database Security
- [ ] User database app TIDAK superuser (minimal privilege)
- [ ] Backup user database dengan password strong
- [ ] Disable remote root login MySQL
- [ ] Enable SSL untuk MySQL connection (jika server terpisah)
- [ ] Enkripsi kolom sensitif (jika perlu: `casts` dengan `encrypted`)

---

## Phase 5: Feature Maturity & Bug Fix (Week 3, paralel 4.x)

### 5.1 Module Feature Completion Check

Per modul, verify semua CRUD + edge cases jalan:

#### Employee Management
- [ ] CRUD karyawan
- [ ] Import bulk (Excel template)
- [ ] Export CSV
- [ ] Upload dokumen (KTP, NPWP, ijazah)
- [ ] Assignment lokasi kantor
- [ ] History employee (promotion, transfer)
- [ ] Exit/termination flow
- [ ] Struktur organisasi viewer

#### Attendance
- [ ] Clock in/out web
- [ ] Clock in/out mobile (API)
- [ ] GPS validation (office location radius)
- [ ] Face recognition (jika client pakai)
- [ ] Manual attendance entry (admin)
- [ ] Attendance report (harian, bulanan)
- [ ] Late report
- [ ] Overtime calculation (auto dari shift)
- [ ] Multi-shift support (pagi/siang/malam)
- [ ] Weekly schedule per karyawan
- [ ] Holiday handling (libur nasional tidak dihitung absen)

#### Leave Management
- [ ] Leave type CRUD
- [ ] Leave balance per karyawan
- [ ] Leave request employee portal
- [ ] Approval workflow (multi-step)
- [ ] Leave calendar view
- [ ] Balance reset tahunan (prorata)
- [ ] Carry over saldo (jika kebijakan client)

#### Payroll
- [ ] Salary component setup
- [ ] Employee salary assignment
- [ ] Create payroll bulan
- [ ] Process payroll (hitung net)
- [ ] PPh 21 TER method (2026)
- [ ] BPJS TK (JHT, JP, JKK, JKM)
- [ ] BPJS Kesehatan
- [ ] THR calculation
- [ ] Payslip generate PDF
- [ ] Email payslip ke karyawan
- [ ] Approval workflow payroll
- [ ] Mark as paid
- [ ] Export bank transfer file (CSV/Excel)

#### Tax & Compliance
- [ ] Bukti Potong 1721-A1 generate
- [ ] SPT 1721 tahunan
- [ ] Export e-Faktur (jika client butuh)

#### Overtime
- [ ] Request overtime (portal)
- [ ] Approval
- [ ] Auto calculation (hari biasa, libur, libur panjang)
- [ ] Include di payroll

#### Reimbursement
- [ ] Category setup
- [ ] Request + upload bukti
- [ ] Approval
- [ ] Include di payroll

#### Employee Portal
- [ ] Dashboard personal
- [ ] Profile edit
- [ ] Attendance history + clock in/out
- [ ] Leave request + balance
- [ ] Payslip download
- [ ] Overtime request
- [ ] Reimbursement request
- [ ] Pengumuman perusahaan

#### Reports
- [ ] Laporan karyawan per departemen
- [ ] Laporan kehadiran harian/bulanan
- [ ] Laporan cuti
- [ ] Laporan payroll
- [ ] Laporan pajak
- [ ] Export PDF & Excel

#### Communication
- [ ] Announcement create & publish
- [ ] Notification (in-app + email)
- [ ] Push notification mobile (opsional)

### 5.2 Bug Fixes
- [ ] Fix semua issue yang tercatat di `known-issues.md`
- [ ] Fix issue UAT round 1 (nanti saat UAT)
- [ ] Fix issue UAT round 2

### 5.3 Performance Tuning
- [ ] Enable query cache MySQL
- [ ] Eager load semua relasi di index pages (no N+1)
- [ ] Add database indexes yang sering di-query:
  - `attendances` (company_id, employee_id, date)
  - `leave_requests` (company_id, status, employee_id)
  - `payroll_items` (payroll_id, employee_id)
- [ ] Enable Redis cache untuk session & cache
- [ ] Enable OPcache PHP di production
- [ ] Gzip/Brotli compression Nginx
- [ ] Laravel route cache: `php artisan route:cache`
- [ ] Config cache: `php artisan config:cache`
- [ ] View cache: `php artisan view:cache`

### 5.4 Load Testing
- [ ] Test dengan 500 karyawan (data generator)
- [ ] Simulate 50 concurrent users
- [ ] Target: dashboard load < 2 detik
- [ ] Target: payroll process 500 karyawan < 30 detik
- [ ] Fix bottleneck jika ada

### 5.5 Backup & Recovery Test
- [ ] Install `spatie/laravel-backup`
- [ ] Config backup database + files
- [ ] Test backup manual → verify file valid
- [ ] Test restore dari backup → verify data integrity
- [ ] Setup schedule backup daily
- [ ] Setup backup retention (30 hari)
- [ ] Setup off-site backup (S3 atau FTP)

---

## Phase 6: Documentation (Week 4, paralel)

### 6.1 Admin Documentation
- [ ] `docs/user-guide/admin/01-getting-started.md`
- [ ] `docs/user-guide/admin/02-company-profile.md`
- [ ] `docs/user-guide/admin/03-users-and-roles.md`
- [ ] `docs/user-guide/admin/04-employee-management.md`
- [ ] `docs/user-guide/admin/05-attendance-settings.md`
- [ ] `docs/user-guide/admin/06-leave-management.md`
- [ ] `docs/user-guide/admin/07-payroll-setup.md`
- [ ] `docs/user-guide/admin/08-payroll-process.md`
- [ ] `docs/user-guide/admin/09-tax-bpjs.md`
- [ ] `docs/user-guide/admin/10-reports.md`
- [ ] `docs/user-guide/admin/11-backup-restore.md`
- [ ] Export ke PDF untuk handout

### 6.2 Employee Documentation
- [ ] `docs/user-guide/employee/01-login.md`
- [ ] `docs/user-guide/employee/02-attendance.md`
- [ ] `docs/user-guide/employee/03-leave-request.md`
- [ ] `docs/user-guide/employee/04-payslip.md`
- [ ] `docs/user-guide/employee/05-overtime.md`
- [ ] `docs/user-guide/employee/06-reimbursement.md`
- [ ] `docs/user-guide/employee/07-profile-update.md`

### 6.3 Technical Documentation
- [ ] `README.md` — overview + quick start
- [ ] `INSTALLATION.md` — detail install (kasih juga di [`CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md`](./CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md))
- [ ] `docs/architecture.md` — overview arsitektur
- [ ] `docs/api.md` — API documentation (jika ada integrasi)
- [ ] `docs/troubleshooting.md` — common issues & solutions

### 6.4 Video Tutorial (Opsional tapi Value Add)
- [ ] Video 1: Login & Dashboard Tour (5 menit)
- [ ] Video 2: Tambah Karyawan (5 menit)
- [ ] Video 3: Proses Payroll Bulanan (10 menit)
- [ ] Video 4: Approve Cuti & Overtime (5 menit)
- [ ] Upload ke YouTube (unlisted) atau Google Drive

---

## Phase 7: Staging Deployment (Week 4, Day 5)

### 7.1 Setup Staging
- [ ] Minta akses staging server dari client
- [ ] Follow [CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md](./CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md)
- [ ] Deploy aplikasi + seeder data awal
- [ ] Verify semua modul berfungsi di staging

### 7.2 Dummy Data untuk UAT
- [ ] Buat 10-20 karyawan dummy
- [ ] Buat 1-2 bulan attendance dummy
- [ ] Buat 2-3 leave request dummy
- [ ] Buat 1 payroll dummy

### 7.3 Kirim Akses ke Client
- [ ] URL staging + SSL
- [ ] Credentials admin & 2-3 user test
- [ ] UAT scenarios document
- [ ] Channel komunikasi untuk feedback

---

## Phase 8: User Acceptance Testing (Week 5)

### 8.1 UAT Round 1 (3-4 hari)
- [ ] Client test seluruh modul sesuai scenario
- [ ] Log bug ke issue tracker (Trello/Linear/Notion)
- [ ] Prioritasi bug: Critical, High, Medium, Low
- [ ] Daily standup dengan PIC client

### 8.2 Bug Fix UAT Round 1
- [ ] Fix semua critical & high
- [ ] Deploy ulang ke staging
- [ ] Client verifikasi ulang

### 8.3 UAT Round 2 (2 hari)
- [ ] Final verification oleh client
- [ ] Sign-off dokumen UAT (formal)

---

## Phase 9: Go-Live Preparation (Week 6, Day 1-2)

### 9.1 Pre-Production Checklist
- [ ] Refer ke [CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md](./CLIENT_GEMILANG_INSTALLATION_CHECKLIST.md) section Go-Live
- [ ] Final security audit
- [ ] Final performance check
- [ ] Backup staging sebelum migrate data

### 9.2 Data Migration (jika ada sistem lama)
- [ ] Export data karyawan dari sistem lama
- [ ] Mapping field ke format GajiPro
- [ ] Import via bulk import atau custom script
- [ ] Verify data integrity pasca import

---

## Phase 10: Go-Live & Training (Week 6, Day 3-5)

### 10.1 Production Deployment
- [ ] Deploy ke production server
- [ ] Point domain ke server
- [ ] SSL certificate aktif
- [ ] Smoke test: login, dashboard, 1 attendance, 1 leave request

### 10.2 Training Admin (2 sesi)
- [ ] Sesi 1 (2 jam): Overview, Company Profile, User Management, Employee Management, Attendance
- [ ] Sesi 2 (2 jam): Leave, Payroll Setup, Payroll Process, Tax/BPJS, Reports, Backup
- [ ] Rekaman sesi untuk referensi
- [ ] Q&A + hand-out PDF

### 10.3 Employee Onboarding
- [ ] Send email welcome ke semua karyawan dengan:
  - URL login
  - Credentials awal (atau link reset password)
  - Link panduan portal
- [ ] Pastikan karyawan bisa login & clock in hari 1

---

## Phase 11: Warranty Period (30 Hari Pasca Go-Live)

### 11.1 Active Support
- [ ] Monitoring aktif via dashboard server
- [ ] Respond ke bug report dalam 24 jam
- [ ] Bug fix critical deploy dalam 48 jam
- [ ] Weekly check-in dengan client

### 11.2 Handover Formal (Hari ke-30)
- [ ] Dokumen handover (credentials, kontak, SLA maintenance)
- [ ] Sign-off warranty period
- [ ] Tawarkan kontrak maintenance berkelanjutan

---

## Summary Task Count

| Phase | Task Count | Duration |
|-------|-----------|----------|
| 1. Persiapan | 15 | 2 hari |
| 2. Konversi Single-Tenant | 50+ | 3 hari |
| 3. Rebrand | 30 | 5 hari |
| 4. Security Hardening | 35 | 5 hari |
| 5. Feature Maturity | 100+ | 5 hari (paralel) |
| 6. Documentation | 40 | 5 hari (paralel) |
| 7. Staging | 10 | 1 hari |
| 8. UAT | 10 | 5 hari |
| 9. Go-Live Prep | 10 | 2 hari |
| 10. Go-Live & Training | 15 | 3 hari |
| 11. Warranty | Ongoing | 30 hari |
| **TOTAL** | **315+** | **~6 minggu + warranty** |

---

## Cara Pakai Checklist Ini

1. **Copy ke tool project management** (Linear, Notion, Trello, ClickUp)
2. **Assign PIC** per task
3. **Set due date** sesuai timeline master plan
4. **Daily standup** — review progress
5. **Weekly review** — update risiko, demo ke client
6. **Sign-off** setiap phase selesai sebelum lanjut phase berikutnya

---

> **Note**: Dokumen ini adalah living doc. Update seiring progress. Simpan di git dengan commit history supaya ada audit trail perubahan scope.
