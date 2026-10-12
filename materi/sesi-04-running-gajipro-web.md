# Sesi 4: Running GajiPro Web — Tenant Management & Admin Dashboard

> **Durasi**: 2-3 jam
> **Tanggal**: 14 April 2026 (Minggu 2)
> **Prasyarat**: Sudah paham struktur project (Sesi 2), sudah setup AI IDE (Sesi 3)
> **Tujuan**: Menjalankan GajiPro web, memahami multi-tenant flow, dan eksplorasi admin dashboard

---

## Daftar Isi

1. [Persiapan Environment](#1-persiapan-environment)
2. [Menjalankan GajiPro Web](#2-menjalankan-gajipro-web)
3. [Seeding Demo Data](#3-seeding-demo-data)
4. [Login Flow — Dari Browser ke Dashboard](#4-login-flow--dari-browser-ke-dashboard)
5. [Memahami Middleware Pipeline](#5-memahami-middleware-pipeline)
6. [Deep Dive: SetTenant Middleware](#6-deep-dive-settenant-middleware)
7. [Eksplorasi Admin Dashboard](#7-eksplorasi-admin-dashboard)
8. [Navigasi Sidebar & Module Tour](#8-navigasi-sidebar--module-tour)
9. [Role-Based Access — 4 Akun, 4 Pengalaman](#9-role-based-access--4-akun-4-pengalaman)
10. [Superadmin Panel — Melihat Lintas Tenant](#10-superadmin-panel--melihat-lintas-tenant)
11. [Tenant Isolation — Kenapa Ini Kritis](#11-tenant-isolation--kenapa-ini-kritis)
12. [Company Model — Pusat Data Tenant](#12-company-model--pusat-data-tenant)
13. [Registrasi Tenant Baru — Flow Lengkap](#13-registrasi-tenant-baru--flow-lengkap)
14. [Security Layer — Attack Detection](#14-security-layer--attack-detection)
15. [Latihan Praktik](#15-latihan-praktik)

---

## 1. Persiapan Environment

### Pastikan Semua Sudah Terinstall

```bash
# Cek PHP
php -v
# Output: PHP 8.3.x

# Cek Composer
composer -V

# Cek Node.js
node -v
# Output: v20.x atau lebih baru

# Cek npm
npm -v

# Cek MySQL
mysql --version
```

### Clone & Setup (Jika Belum)

```bash
# Clone repository
git clone <repository-url> ultimate-jagogaji-system
cd ultimate-jagogaji-system

# Install dependencies
composer install
npm install

# Copy environment
cp .env.example .env
php artisan key:generate
```

### Konfigurasi Database

Edit file `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gajipro
DB_USERNAME=root
DB_PASSWORD=your_password
```

```bash
# Buat database
mysql -u root -p -e "CREATE DATABASE gajipro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### Setup Cepat (One Command)

```bash
# Jalankan setup otomatis
composer run setup
```

Command ini menjalankan:
1. `composer install`
2. Copy `.env.example` → `.env` (jika belum ada)
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install`
6. `npm run build`

---

## 2. Menjalankan GajiPro Web

### Cara 1: Development Mode (Recommended)

```bash
composer run dev
```

Ini menjalankan **4 proses sekaligus** dengan `npx concurrently`:

| Proses | Command | Fungsi |
|--------|---------|--------|
| 🟢 Web Server | `php artisan serve` | Laravel development server (port 8000) |
| 🟡 Queue Worker | `php artisan queue:listen` | Background job processor |
| 🔵 Log Viewer | `php artisan pail` | Real-time log tail |
| 🟣 Vite HMR | `npm run dev` | Hot Module Replacement untuk CSS/JS |

Buka browser: **http://localhost:8000**

### Cara 2: Manual (Buka Multiple Terminal)

```bash
# Terminal 1 - Web Server
php artisan serve

# Terminal 2 - Vite (untuk CSS/JS changes)
npm run dev

# Terminal 3 - Queue Worker (opsional)
php artisan queue:listen
```

### Cara 3: Laravel Sail (Docker)

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

### Troubleshooting Umum

| Problem | Solusi |
|---------|--------|
| Port 8000 sudah dipakai | `php artisan serve --port=8001` |
| CSS/JS tidak muncul | Jalankan `npm run build` atau `npm run dev` |
| Database error | Pastikan MySQL running & `.env` benar |
| Migration error | `php artisan migrate:fresh --seed` |
| Permission error | `chmod -R 775 storage bootstrap/cache` |

---

## 3. Seeding Demo Data

### Fresh Database + Seed

```bash
# Drop semua tabel, migrate ulang, jalankan seeder
php artisan migrate:fresh --seed
```

### Apa yang Di-seed?

`DatabaseSeeder` membuat data demo lengkap:

#### 1. Roles & Permissions
```
5 roles: super-admin, admin, hr-manager, payroll-manager, employee
40+ permissions: manage employees, manage payroll, manage leave, dll.
```

#### 2. Super Admin Account
```
Email: superadmin@gajipro.com
Password: password
Role: Super Admin (is_superadmin = true)
Company: TIDAK ada (company_id = null)
```

#### 3. Demo Company
```
Nama: PT Demo GajiPro
Demo Mode: Active
5 Departemen: HR, Engineering, Marketing, Finance, Operations
5 Jabatan: Manager, Senior Staff, Staff, Junior Staff, Intern
26 Karyawan (6 dengan user account, 20 random)
```

#### 4. Demo User Accounts

| Email | Password | Roles | Akses |
|-------|----------|-------|-------|
| `admin@demo.gajipro.com` | password | Admin + Employee | Full dashboard |
| `hr@demo.gajipro.com` | password | HR Manager + Employee | HR modules |
| `payroll@demo.gajipro.com` | password | Payroll Manager + Employee | Payroll modules |
| `karyawan@demo.gajipro.com` | password | Employee | Portal only |

### Seed Tambahan (Opsional)

```bash
# Seed data demo lengkap semua fitur
php artisan db:seed --class=DemoAllFeaturesSeeder

# Seed data attendance saja
php artisan db:seed --class=DemoAttendanceDataSeeder

# Seed data payroll saja
php artisan db:seed --class=DemoPayrollDataSeeder

# Seed struktur organisasi
php artisan db:seed --class=DemoOrganizationStructureSeeder
```

---

## 4. Login Flow — Dari Browser ke Dashboard

### Step-by-Step: Apa yang Terjadi Saat Login?

```
Browser                    Laravel
   │                          │
   ├── GET /login ──────────►│ Guest middleware check
   │                          │ Return login form
   │◄──── Login Page ────────│
   │                          │
   ├── POST /login ─────────►│ 1. Validate credentials
   │    email + password      │ 2. Auth::attempt()
   │                          │ 3. Check user.is_active
   │                          │ 4. Check company.is_active
   │                          │ 5. Session regenerate
   │                          │ 6. Determine redirect:
   │                          │    - Employee only → /portal
   │                          │    - Admin/HR/Payroll → /dashboard
   │◄──── 302 Redirect ──────│
   │                          │
   ├── GET /dashboard ──────►│ Middleware Pipeline:
   │                          │   1. DetectAttack ✓
   │                          │   2. CheckBlockedIp ✓
   │                          │   3. SetTenant ← SET CONTEXT
   │                          │   4. 'admin' middleware ✓
   │                          │
   │◄──── Dashboard View ────│ DashboardController@index
```

### Kode Login Controller

**File:** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

```php
public function store(Request $request): RedirectResponse
{
    $request->validate([
        'email' => ['required', 'email'],
        'password' => ['required'],
    ]);

    if (! Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
        throw ValidationException::withMessages([
            'email' => __('auth.failed'),
        ]);
    }

    $user = Auth::user();

    // Cek apakah user aktif
    if (! $user->is_active) {
        Auth::logout();
        throw ValidationException::withMessages([
            'email' => 'Akun Anda telah dinonaktifkan.',
        ]);
    }

    // Cek apakah company aktif
    if ($user->company && ! $user->company->is_active) {
        Auth::logout();
        throw ValidationException::withMessages([
            'email' => 'Perusahaan Anda telah dinonaktifkan.',
        ]);
    }

    $request->session()->regenerate();

    // Redirect berdasarkan role
    if ($user->hasRole('employee') &&
        ! $user->hasAnyRole(['admin', 'hr-manager', 'payroll-manager'])) {
        return redirect()->route('portal.dashboard');
    }

    return redirect()->intended('/dashboard');
}
```

### Poin Penting untuk Dipahami

1. **Session regenerate** — Mencegah session fixation attack
2. **Double check** — User aktif DAN company aktif
3. **Role-based redirect** — Employee murni ke portal, yang lain ke dashboard
4. **Tenant context BELUM di-set** saat login — baru di-set pada request berikutnya via `SetTenant` middleware

---

## 5. Memahami Middleware Pipeline

### Apa itu Middleware?

Middleware adalah "filter" yang dilewati setiap HTTP request sebelum sampai ke controller. Bayangkan seperti pos satpam:

```
Request masuk
    │
    ▼
┌──────────────────┐
│  DetectAttack    │  ← Cek apakah request mengandung serangan
└────────┬─────────┘
         │
┌────────▼─────────┐
│  CheckBlockedIp  │  ← Cek apakah IP di-block
└────────┬─────────┘
         │
┌────────▼─────────┐
│  SetTenant       │  ← Set konteks perusahaan
└────────┬─────────┘
         │
┌────────▼─────────┐
│  Auth            │  ← Cek apakah sudah login
└────────┬─────────┘
         │
┌────────▼─────────┐
│  Admin/Employee  │  ← Cek role & redirect
└────────┬─────────┘
         │
         ▼
    Controller
```

### Registrasi Middleware di Laravel 12

**File:** `bootstrap/app.php`

```php
->withMiddleware(function (Middleware $middleware) {
    // Middleware yang jalan di SEMUA web request
    $middleware->web(append: [
        DetectAttack::class,      // Security: deteksi serangan
        CheckBlockedIp::class,    // Security: blokir IP jahat
        SetTenant::class,         // Multi-tenant: set company context
        LogRateLimitHit::class,   // Logging: catat rate limit
    ]);

    // Middleware yang jalan di SEMUA API request
    $middleware->api(append: [
        DetectAttack::class,
        CheckBlockedIp::class,
        LogRateLimitHit::class,
        // Note: API punya SetTenantApi terpisah
    ]);

    // Alias untuk dipakai di route
    $middleware->alias([
        'tenant'     => SetTenant::class,
        'superadmin' => EnsureSuperadmin::class,
        'employee'   => EnsureUserIsEmployee::class,
        'admin'      => RedirectEmployeeToPortal::class,
    ]);
})
```

### Perbedaan Laravel 12 vs Sebelumnya

| Aspek | Laravel 10/11 | Laravel 12 |
|-------|---------------|------------|
| File konfigurasi | `app/Http/Kernel.php` | `bootstrap/app.php` |
| Registrasi | Property `$middleware` | Method `withMiddleware()` |
| Group | Array manual | `$middleware->web()`, `->api()` |
| Alias | Array `$middlewareAliases` | `$middleware->alias()` |

---

## 6. Deep Dive: SetTenant Middleware

### Ini Jantung Multi-Tenancy!

**File:** `app/Http/Middleware/SetTenant.php`

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    // Belum login? Skip.
    if (! $user) {
        return $next($request);
    }

    // Super admin tidak punya tenant
    if ($user->company_id === null) {
        return $next($request);
    }

    $company = $user->company;

    // Subscription expired? Redirect!
    if (! $company->isSubscriptionActive()) {
        return redirect('/subscription-expired');
    }

    // ⭐ INI YANG PENTING:
    // 1. Set company sebagai "tenant" di service container
    app()->instance('tenant', $company);

    // 2. Set team context untuk Spatie Permission
    setPermissionsTeamId($company->id);

    return $next($request);
}
```

### Bagaimana Controller Menggunakan Tenant?

```php
// Di SETIAP controller yang butuh data tenant:
public function index(): View
{
    $tenant = app('tenant');  // ← Ambil company dari container

    // Query HARUS di-scope oleh company_id
    $employees = Employee::where('company_id', $tenant->id)
        ->with(['department', 'position'])
        ->paginate(15);

    return view('employees.index', compact('employees'));
}
```

### Visualisasi: 2 Perusahaan, 1 Database

```
┌─────────────────────────────────────────────────┐
│                    DATABASE                       │
│                                                   │
│  employees table:                                 │
│  ┌─────┬────────────┬───────────────┬──────────┐ │
│  │ id  │ company_id │ full_name     │ dept     │ │
│  ├─────┼────────────┼───────────────┼──────────┤ │
│  │  1  │     1      │ Budi Santoso  │ HR       │ │ ← PT Demo GajiPro
│  │  2  │     1      │ Siti Rahayu   │ Finance  │ │ ← PT Demo GajiPro
│  │  3  │     2      │ John Doe      │ IT       │ │ ← PT ABC Corp
│  │  4  │     2      │ Jane Smith    │ HR       │ │ ← PT ABC Corp
│  └─────┴────────────┴───────────────┴──────────┘ │
│                                                   │
│  Login sebagai admin PT Demo GajiPro:            │
│  → SetTenant: app('tenant') = Company(id=1)      │
│  → Query: WHERE company_id = 1                    │
│  → Hanya lihat Budi & Siti ✅                     │
│  → John & Jane TIDAK terlihat 🔒                  │
└─────────────────────────────────────────────────┘
```

### Kenapa `app()->instance('tenant', $company)`?

Ini memanfaatkan **Laravel Service Container**:

```php
// SetTenant middleware (awal request):
app()->instance('tenant', $company);
// → Simpan object Company ke container dengan key 'tenant'

// Controller (saat handle request):
$tenant = app('tenant');
// → Ambil object Company yang sama dari container

// Bahkan di Service class yang di-inject:
class PayrollService {
    public function calculate() {
        $tenant = app('tenant');  // Bisa diakses dari mana saja!
    }
}
```

---

## 7. Eksplorasi Admin Dashboard

### Login ke Dashboard

1. Buka **http://localhost:8000/login**
2. Masukkan:
   - Email: `admin@demo.gajipro.com`
   - Password: `password`
3. Klik **Login**
4. Anda akan diarahkan ke **http://localhost:8000/dashboard**

### Apa yang Ada di Dashboard?

**File:** `app/Http/Controllers/DashboardController.php`

Dashboard menampilkan 7 section utama:

#### 1. Stat Cards (Ringkasan)

| Stat | Penjelasan |
|------|-----------|
| Total Karyawan | `Employee::where('company_id', ...)->count()` |
| Hadir Hari Ini | Jumlah yang sudah clock in |
| Cuti Pending | Leave request menunggu approval |
| Total Gaji Bulan Ini | Sum payroll items bulan berjalan |
| Karyawan Baru | Karyawan yang join bulan ini |
| Persentase Kehadiran | (Hadir / Total) × 100 |

#### 2. Kehadiran Hari Ini (Real-time)

```php
// Query efisien dengan satu SQL aggregate:
$attendanceToday = Attendance::where('company_id', $tenant->id)
    ->whereDate('date', $company->today())
    ->selectRaw("
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late,
        COUNT(CASE WHEN status = 'on_leave' THEN 1 END) as on_leave,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent
    ")
    ->first();
```

#### 3. Recent Clock-ins
5 karyawan terakhir yang clock in hari ini.

#### 4. Karyawan Baru
5 karyawan terakhir yang ditambahkan.

#### 5. Approval Pending
5 pengajuan cuti menunggu persetujuan.

#### 6. Ulang Tahun Bulan Ini
Karyawan yang berulang tahun bulan ini.

#### 7. Kontrak Hampir Habis
Karyawan dengan kontrak yang expired dalam 30 hari ke depan.

#### 8. Analytics Charts
Data dari `DashboardAnalyticsService`:
- Chart kehadiran (7 hari terakhir)
- Chart karyawan per departemen
- Trend payroll (6 bulan terakhir)

### Dashboard View

**File:** `resources/views/dashboard.blade.php` — extends `layouts/admin.blade.php`

```blade
@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="stat-card">
            <div class="stat-card-icon bg-primary-100">...</div>
            <div>
                <div class="stat-card-value">{{ $stats['total_employees'] }}</div>
                <div class="stat-card-label">Total Karyawan</div>
            </div>
        </div>
        {{-- ... more stat cards --}}
    </div>

    {{-- Charts & Tables --}}
    {{-- ... --}}
@endsection
```

---

## 8. Navigasi Sidebar & Module Tour

### Struktur Sidebar

Sidebar menggunakan Alpine.js dengan accordion pattern:

```blade
<div x-data="{
    sidebarOpen: false,
    sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true'
}">
```

### Kelompok Menu

#### 🏢 Manajemen

| Menu | Sub-menu | Route | Permission |
|------|----------|-------|------------|
| **Dashboard** | - | `dashboard` | Semua admin |
| **Karyawan** | Daftar Karyawan | `employees.index` | `manage employees` |
| | Departemen | `departments.index` | `manage departments` |
| | Jabatan | `positions.index` | `manage positions` |
| | Lokasi Kantor | `office-locations.index` | `manage office locations` |
| | Exit Management | `employee-exits.index` | `manage employees` |
| **Kehadiran** | Daftar Kehadiran | `attendances.index` | `manage attendances` |
| | Jadwal Kerja | `work-schedules.index` | `manage work schedules` |
| | Hari Libur | `holidays.index` | `manage holidays` |
| | Pengajuan Lembur | `overtime-requests.index` | `manage overtime` |
| **Cuti & Izin** | Pengajuan Cuti | `leave-requests.index` | `manage leaves` |
| | Saldo Cuti | `leave-balances.index` | `manage leaves` |
| | Jenis Cuti | `leave-types.index` | `manage leaves` |

#### 💰 Keuangan

| Menu | Sub-menu | Route | Permission |
|------|----------|-------|------------|
| **Payroll** | Proses Gaji | `payrolls.index` | `manage payroll` |
| | Komponen Gaji | `salary-components.index` | `manage payroll` |
| | Pengaturan Gaji | `employee-salaries.index` | `manage payroll` |
| **Reimbursement** | Pengajuan | `reimbursements.index` | `manage reimbursements` |
| | Kategori | `reimbursement-categories.index` | `manage reimbursements` |
| **Pajak & BPJS** | PPh 21 | `pph21-settings.index` | `manage tax settings` |
| | BPJS TK | `bpjs-tk-settings.index` | `manage bpjs settings` |
| | BPJS Kesehatan | `bpjs-kes-settings.index` | `manage bpjs settings` |
| | THR | `thr.index` | `manage thr` |

#### ⚙️ Sistem

| Menu | Sub-menu | Route |
|------|----------|-------|
| **Pengumuman** | - | `announcements.index` |
| **Pengaturan** | Profil Perusahaan | `settings.company-profile` |
| | Pengguna | `settings.users.index` |
| | Role & Hak Akses | `settings.roles.index` |
| | Alur Persetujuan | `settings.approval-workflows.index` |
| | Billing | `settings.billing.index` |

### Tour: Klik Setiap Menu!

**Aktivitas:** Login sebagai `admin@demo.gajipro.com` dan buka setiap menu. Perhatikan:

1. Setiap halaman punya **Breadcrumb** di atas
2. Setiap list punya **Search & Filter**
3. Setiap item punya **Action buttons** (View, Edit, Delete)
4. Data selalu **ter-scope** ke perusahaan demo

---

## 9. Role-Based Access — 4 Akun, 4 Pengalaman

### Eksperimen: Login dengan 4 Akun Berbeda

#### 1. Admin (`admin@demo.gajipro.com`)
```
Akses: SEMUA menu
Dashboard: Full stats
Bisa: CRUD karyawan, payroll, settings, semua
```

#### 2. HR Manager (`hr@demo.gajipro.com`)
```
Akses: Karyawan, Kehadiran, Cuti, Struktur Organisasi
Dashboard: HR-related stats
Bisa: Kelola karyawan, approve cuti, kelola jadwal
Tidak bisa: Payroll, Pajak, Company Settings
```

#### 3. Payroll Manager (`payroll@demo.gajipro.com`)
```
Akses: Payroll, Gaji, Pajak, BPJS, THR, Reimbursement
Dashboard: Financial stats
Bisa: Proses gaji, setting pajak, THR
Tidak bisa: CRUD karyawan, Kehadiran
```

#### 4. Employee (`karyawan@demo.gajipro.com`)
```
Akses: Portal (BUKAN Dashboard admin!)
Redirect: → /portal (bukan /dashboard)
Bisa: Lihat profil, clock in/out, ajukan cuti, lihat slip gaji
Tidak bisa: Akses admin sama sekali
```

### Bagaimana Role Mengontrol Akses?

#### Di Route (`routes/web.php`):

```php
// Hanya yang punya permission 'manage employees' bisa akses
Route::resource('employees', EmployeeController::class)
    ->middleware('permission:manage employees');
```

#### Di Blade View:

```blade
{{-- Hanya tampil jika punya permission --}}
@can('manage employees')
    <a href="{{ route('employees.create') }}" class="btn btn-primary">
        Tambah Karyawan
    </a>
@endcan
```

#### Di Middleware `RedirectEmployeeToPortal` (alias `'admin'`):

```php
public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    // Super admin → ke panel superadmin
    if ($user->isSuperAdmin()) {
        return redirect()->route('superadmin.dashboard');
    }

    // Employee murni → ke portal
    if ($user->hasRole('employee') &&
        ! $user->hasAnyRole(['admin', 'hr-manager', 'payroll-manager'])) {
        return redirect()->route('portal.dashboard');
    }

    return $next($request);
}
```

### Spatie Permission + Team Context

GajiPro menggunakan **Team Permission** dari Spatie:

```php
// Setiap role & permission TERIKAT ke company_id
// Jadi "admin" di PT A ≠ "admin" di PT B

// Di SetTenant middleware:
setPermissionsTeamId($company->id);
// → Ini membuat permission check hanya berlaku untuk company tersebut

// Di DatabaseSeeder, role dibuat PER company:
// Company 1 punya set roles sendiri
// Company 2 punya set roles sendiri
```

---

## 10. Superadmin Panel — Melihat Lintas Tenant

### Login Superadmin

1. Buka: **http://localhost:8000/superadmin/login**
2. Email: `superadmin@gajipro.com`
3. Password: `password`

### Apa yang Bisa Dilihat Superadmin?

| Menu | Isi |
|------|-----|
| **Dashboard** | Total companies, subscriptions aktif, revenue |
| **Perusahaan** | List semua company (VIEW ONLY — tidak bisa edit!) |
| **Langganan** | Subscription plans & manajemen |
| **Pembayaran** | Riwayat pembayaran semua company |
| **Keamanan** | Security logs, Blocked IPs |
| **Sistem** | Health, Queue, Email logs, Audit logs |

### Kenapa Superadmin Hanya Bisa VIEW Company?

```php
// app/Http/Controllers/Superadmin/CompanyController.php
// Hanya ada index() dan show() — TIDAK ada create, edit, delete!

class CompanyController extends Controller
{
    public function index() { /* list all companies */ }
    public function show(Company $company) { /* view company detail */ }
    // Tidak ada store(), update(), destroy()!
}
```

**Alasan keamanan:** Company dibuat saat registrasi. Superadmin tidak boleh memodifikasi data tenant — hanya monitor.

### Perbedaan Query: Superadmin vs Admin

```php
// ADMIN — Query di-scope ke tenant
$employees = Employee::where('company_id', app('tenant')->id)->get();

// SUPERADMIN — Query tanpa scope (cross-tenant)
$companies = Company::withCount('employees')->paginate(15);
$totalRevenue = Payment::where('status', 'success')->sum('amount');
```

---

## 11. Tenant Isolation — Kenapa Ini Kritis

### Skenario Horor: Tanpa Tenant Isolation

```php
// ❌ SALAH — Tidak ada company_id filter!
public function index()
{
    $employees = Employee::all();
    // → PT A bisa lihat karyawan PT B!
    // → GDPR violation, data breach, hancur!
}
```

### Skenario Benar: Dengan Tenant Isolation

```php
// ✅ BENAR — Selalu scope ke tenant
public function index()
{
    $tenant = app('tenant');
    $employees = Employee::where('company_id', $tenant->id)->get();
    // → PT A hanya lihat datanya sendiri ✅
}
```

### 3 Layer Proteksi

```
Layer 1: SetTenant Middleware
    → Set app('tenant') = Company milik user yang login

Layer 2: Controller Query Scope
    → WHERE company_id = tenant.id di SETIAP query

Layer 3: Ownership Verification (Show/Edit/Delete)
    → if ($model->company_id !== $tenant->id) abort(404);
```

### Checklist Tenant Isolation

Setiap kali bikin fitur baru, pastikan:

- [ ] Query pakai `where('company_id', $tenant->id)`
- [ ] Form Request validasi existence pakai `->where('company_id', $tenant->id)`
- [ ] Show/Edit/Delete cek `$model->company_id !== $tenant->id`
- [ ] Factory test buat 2 company, pastikan data tidak bocor
- [ ] Seeder data pakai `company_id` yang benar

---

## 12. Company Model — Pusat Data Tenant

### Struktur Company Model

**File:** `app/Models/Company.php`

```php
class Company extends Model
{
    protected $fillable = [
        'name',               // PT Demo GajiPro
        'slug',               // pt-demo-gajipro
        'email',              // admin@company.com
        'phone',              // 021-xxxxx
        'address',            // Jl. Sudirman No. 1
        'logo',               // Path ke file logo
        'npwp',               // NPWP perusahaan

        // Subscription
        'is_active',          // Boolean: company aktif
        'subscription_plan',  // 'starter', 'professional', 'enterprise'
        'subscription_ends_at', // Tanggal expired
        'max_employees',      // Batas karyawan

        // Demo
        'is_demo_mode',       // Boolean: mode demo
        'demo_started_at',    // Kapan demo dimulai

        // Settings
        'settings',           // JSON: konfigurasi lainnya
        'timezone',           // 'Asia/Jakarta', 'Asia/Makassar', 'Asia/Jayapura'

        // GPS & Face Recognition
        'gps_enabled',
        'face_recognition_enabled',
    ];
}
```

### Method Penting

```php
// Cek subscription masih aktif
public function isSubscriptionActive(): bool
{
    return $this->is_active
        && $this->subscription_ends_at
        && $this->subscription_ends_at->isFuture();
}

// Cek apakah bisa tambah karyawan (batas plan)
public function canAddEmployee(): bool
{
    $currentCount = Employee::where('company_id', $this->id)->count();
    return $currentCount < $this->max_employees;
}

// Waktu sekarang di timezone perusahaan
public function now(): Carbon
{
    return Carbon::now($this->timezone ?? 'Asia/Jakarta');
}

public function today(): Carbon
{
    return $this->now()->startOfDay();
}
```

### Kenapa Company Tidak Bisa Dihapus?

```php
protected static function booted(): void
{
    static::deleting(function (Company $company) {
        throw new \RuntimeException('Companies cannot be deleted.');
    });
}
```

**Alasan:** Menghapus company = menghapus semua data karyawan, payroll, pajak, dll. Terlalu berbahaya. Gunakan `is_active = false` sebagai gantinya.

---

## 13. Registrasi Tenant Baru — Flow Lengkap

### Apa yang Terjadi Saat Register?

**File:** `app/Http/Controllers/Auth/RegisteredUserController.php`

```
User isi form registrasi
    │
    ▼
┌─────────────────────────────────┐
│  DB::transaction() {            │
│                                  │
│  1. Create Company               │
│     - name, email, plan          │
│     - subscription_ends_at       │
│     - max_employees              │
│     - is_demo_mode = true        │
│                                  │
│  2. setPermissionsTeamId()       │
│     - Set context ke company baru│
│                                  │
│  3. Create Roles (5 roles)       │
│     - admin, hr-manager,         │
│       payroll-manager, employee  │
│     - Dengan team_id = company   │
│                                  │
│  4. Create User                  │
│     - company_id = company.id    │
│     - Assign roles:              │
│       admin + hr + employee      │
│                                  │
│  5. Create Office Location       │
│     - Default: "Kantor Pusat"    │
│                                  │
│  6. Create Employee record       │
│     - Linked ke user             │
│                                  │
│  7. Seed Demo Data               │
│     - DemoDataService::seed()    │
│     - Sample departments, etc.   │
│                                  │
│  8. Create Subscription (paid)   │
│     - If paid plan selected      │
│                                  │
│  } // end transaction            │
└─────────────────────────────────┘
    │
    ▼
Auto-login → Redirect to /dashboard
```

### Cobalah Sendiri!

1. Buka **http://localhost:8000/register**
2. Isi form:
   - Nama Perusahaan: `PT Coba Test`
   - Nama Lengkap: `Test User`
   - Email: `test@example.com`
   - Password: `password` (min 8 karakter)
   - Pilih Plan
3. Register → Otomatis login ke dashboard
4. Cek database: company baru, roles baru, user baru — semua ter-link!

---

## 14. Security Layer — Attack Detection

### DetectAttack Middleware

**File:** `app/Http/Middleware/DetectAttack.php`

Jalan di SETIAP request (web & API). Mendeteksi:

| Tipe Serangan | Contoh Pattern | Jumlah Pattern |
|----------------|---------------|---------------|
| SQL Injection | `UNION SELECT`, `OR 1=1`, `DROP TABLE` | 19 regex |
| XSS | `<script>`, `onerror=`, `javascript:` | 11 regex |
| Path Traversal | `../`, `/etc/passwd`, `%2e%2e` | 12 regex |
| Command Injection | `; ls`, `| cat`, `` `whoami` `` | 7 regex |
| LDAP Injection | `)(`, `\00` | 2 regex |
| XML/XXE | `<!ENTITY`, `<!DOCTYPE` | 4 regex |
| Scanner Tools | sqlmap, nikto, nmap, burpsuite | 17 tools |

### Auto-Blocking

```
Jika 5+ serangan KRITIKAL dalam 1 jam dari IP yang sama:
→ IP otomatis di-block selama 24 jam
→ Semua request dari IP tersebut mendapat 403 Forbidden
```

### Demo: Coba Trigger Detection

```bash
# Ini akan ter-detect sebagai SQL Injection (JANGAN di production!)
curl "http://localhost:8000/employees?search=1%27%20OR%201=1--"
```

Cek di Superadmin → Security Logs untuk melihat deteksi ini.

---

## 15. Latihan Praktik

### Latihan 1: Login dengan Semua Role (15 menit)

1. Login sebagai **admin** → Screenshot dashboard
2. Login sebagai **HR Manager** → Perhatikan menu yang hilang
3. Login sebagai **Payroll Manager** → Perhatikan menu yang tersedia
4. Login sebagai **Karyawan** → Perhatikan redirect ke Portal
5. Login sebagai **Superadmin** → Buka dari URL berbeda!

**Pertanyaan:**
- Menu apa saja yang hanya muncul untuk Admin tapi tidak HR Manager?
- Apa yang terjadi jika Employee coba akses `/dashboard` langsung?

### Latihan 2: Trace Middleware Pipeline (20 menit)

Buka file-file ini dan baca kode-nya:

1. `bootstrap/app.php` — Lihat urutan middleware
2. `app/Http/Middleware/SetTenant.php` — Pahami cara set tenant
3. `app/Http/Middleware/RedirectEmployeeToPortal.php` — Pahami role check
4. `app/Http/Middleware/EnsureSuperadmin.php` — Pahami superadmin check

**Pertanyaan:**
- Apa yang terjadi jika `SetTenant` diletakkan SEBELUM `auth` middleware?
- Apa yang terjadi jika subscription expired?

### Latihan 3: Registrasi Tenant Baru (20 menit)

1. Buka `/register`
2. Daftarkan perusahaan baru: `PT Latihan Sesi 4`
3. Setelah login, periksa:
   - Apakah dashboard kosong? (Harusnya ada sample data)
   - Berapa karyawan yang otomatis dibuat?
   - Cek menu Settings → Profil Perusahaan
4. Buka MySQL dan query:
   ```sql
   SELECT id, name, slug, is_demo_mode, max_employees
   FROM companies ORDER BY id DESC LIMIT 5;
   ```

### Latihan 4: Verifikasi Tenant Isolation (25 menit)

1. Register 2 perusahaan berbeda (beda email)
2. Login ke Perusahaan A → Tambah karyawan bernama "Khusus A"
3. Login ke Perusahaan B → Cek apakah "Khusus A" muncul? (Harusnya TIDAK!)
4. Query database langsung:
   ```sql
   SELECT e.full_name, c.name as company
   FROM employees e
   JOIN companies c ON e.company_id = c.id
   ORDER BY e.id DESC LIMIT 10;
   ```

### Latihan 5: Eksplorasi Dashboard Data (20 menit)

1. Login sebagai admin perusahaan demo
2. Buka DashboardController (`app/Http/Controllers/DashboardController.php`)
3. Identifikasi setiap query yang digunakan
4. Bandingkan data di dashboard dengan data di database:
   ```sql
   SELECT COUNT(*) FROM employees WHERE company_id = 1;
   SELECT COUNT(*) FROM attendances WHERE company_id = 1 AND date = CURDATE();
   ```

### Latihan 6: Baca Kode dengan AI IDE (15 menit)

Gunakan Claude Code atau AI IDE yang sudah di-setup di Sesi 3:

```
Prompt: "Jelaskan alur lengkap dari user klik Login sampai
dashboard tampil. Sebutkan semua middleware yang dilewati
dan apa yang dilakukan masing-masing."

Prompt: "Bagaimana SetTenant middleware memastikan data
antar perusahaan tidak bocor?"

Prompt: "Apa yang terjadi jika saya lupa menambahkan
where company_id di sebuah query controller baru?"
```

---

## Rangkuman Sesi 4

### Apa yang Sudah Dipelajari

| Topik | Key Takeaway |
|-------|-------------|
| Running app | `composer run dev` untuk jalankan 4 proses sekaligus |
| Demo data | `migrate:fresh --seed` untuk reset + seed demo |
| Login flow | Validate → Check active → Session → Redirect by role |
| Middleware | Pipeline: DetectAttack → BlockedIp → SetTenant → Auth → Admin |
| SetTenant | `app()->instance('tenant', $company)` — jantung multi-tenancy |
| Dashboard | 7 section data, semua di-scope ke `company_id` |
| Role-based | 4 role = 4 pengalaman berbeda, controlled by Spatie Permission |
| Superadmin | Cross-tenant visibility, VIEW ONLY, login terpisah |
| Isolation | SETIAP query HARUS pakai `where('company_id', $tenant->id)` |
| Security | 6 tipe serangan terdeteksi, auto-block setelah 5x |

### Konsep Paling Penting

```
🔑 Multi-tenancy GajiPro = Shared Database + company_id filter + SetTenant middleware

Setiap request melewati:
1. Security check (DetectAttack, BlockedIp)
2. Tenant context (SetTenant → app('tenant'))
3. Role check (admin/employee middleware)
4. Controller query → WHERE company_id = tenant.id
```

### Preview Sesi 5

Di sesi berikutnya kita akan:
- Menjalankan **Flutter mobile app**
- Connect ke **backend API** (Laravel Sanctum)
- Memahami **API authentication flow**
- Testing API endpoints dari Flutter

---

> **Catatan Instruktur:** Pastikan semua peserta berhasil login dengan minimal 2 akun berbeda dan memahami konsep tenant isolation sebelum lanjut ke sesi berikutnya.
