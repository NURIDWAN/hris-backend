# Sesi 2: Memahami Struktur Project & Multi-Tenant Architecture

> **Durasi**: 2-3 jam
> **Prasyarat**: Sudah clone repository BE & FE, environment siap
> **Tujuan**: Memahami arsitektur GajiPro secara menyeluruh (Backend + Frontend)

---

## Daftar Isi

1. [Overview Arsitektur GajiPro](#1-overview-arsitektur-gajipro)
2. [Backend (Laravel) - Struktur Direktori](#2-backend-laravel---struktur-direktori)
3. [Multi-Tenant Architecture](#3-multi-tenant-architecture)
4. [Middleware Pipeline](#4-middleware-pipeline)
5. [Route Architecture](#5-route-architecture)
6. [Model & Database Layer](#6-model--database-layer)
7. [Service Layer - Business Logic](#7-service-layer---business-logic)
8. [Controller Architecture](#8-controller-architecture)
9. [View Layer (Blade + Alpine.js + Tailwind)](#9-view-layer-blade--alpinejs--tailwind)
10. [Frontend (Flutter) - Struktur Direktori](#10-frontend-flutter---struktur-direktori)
11. [Flutter Clean Architecture & BLoC Pattern](#11-flutter-clean-architecture--bloc-pattern)
12. [API Contract - Komunikasi BE & FE](#12-api-contract---komunikasi-be--fe)
13. [Security Architecture](#13-security-architecture)
14. [Testing Architecture](#14-testing-architecture)
15. [Latihan Praktik](#15-latihan-praktik)

---

## 1. Overview Arsitektur GajiPro

### Apa itu GajiPro?

GajiPro (Gaji Professional) adalah HRIS/Payroll SaaS multi-tenant untuk pasar Indonesia. Sistem ini mengelola:
- **Employee Management** - Data karyawan, dokumen, organisasi
- **Attendance & Scheduling** - Absensi GPS + face recognition, jadwal shift
- **Leave Management** - Cuti dengan approval workflow
- **Payroll & Tax** - Gaji, PPh 21, BPJS, THR (spesifik Indonesia)
- **Employee Portal** - Self-service untuk karyawan

### Tech Stack

```
┌─────────────────────────────────────────────────────────┐
│                    FRONTEND (Mobile)                     │
│         Flutter + BLoC + Clean Architecture              │
│              (Employee Self-Service App)                  │
└──────────────────────┬──────────────────────────────────┘
                       │ REST API (JSON)
                       │ Auth: Laravel Sanctum (Bearer Token)
┌──────────────────────▼──────────────────────────────────┐
│                    BACKEND (Server)                       │
│      Laravel 12 + Blade + Alpine.js + Tailwind CSS 4     │
│              (Admin Dashboard + API)                      │
├──────────────────────────────────────────────────────────┤
│  Database: MySQL  │  Cache: Database  │  Queue: Database  │
└──────────────────────────────────────────────────────────┘
```

### Dua Interface Utama

| Interface | Technology | User | Akses |
|-----------|-----------|------|-------|
| **Web Dashboard** | Blade + Alpine.js + Tailwind | Admin, HR, Payroll Manager | Browser |
| **Mobile App** | Flutter | Employee (Self-Service) | Android/iOS |
| **API** | Laravel Sanctum | Mobile App & Third-party | REST JSON |

---

## 2. Backend (Laravel) - Struktur Direktori

### Root Directory

```
ultimate-jagogaji-system/
├── app/                    # Source code utama (247 PHP files)
├── bootstrap/              # Application bootstrapping
│   ├── app.php            # ⭐ Middleware & route registration
│   └── providers.php      # Service provider registration
├── config/                 # Konfigurasi (14 files)
├── database/               # Migrations (72), seeders, factories
├── lang/                   # Localization (id, en)
├── public/                 # Public assets (entry point: index.php)
├── resources/              # Views, CSS, JavaScript
├── routes/                 # Route definitions
│   ├── web.php            # ⭐ Web routes (~200 endpoints)
│   ├── api.php            # ⭐ API routes (~50 endpoints)
│   └── console.php        # Console/scheduled commands
├── storage/                # Logs, cache, uploads
├── tests/                  # Pest PHP test suites
├── vendor/                 # Composer dependencies
├── .env                    # Environment variables (JANGAN di-commit!)
├── composer.json           # PHP dependencies
├── package.json            # Node.js dependencies
└── vite.config.js          # Frontend build config
```

### App Directory (Inti Aplikasi)

```
app/
├── Console/Commands/              # Artisan commands custom
├── Events/                        # Event classes (4 files)
│   ├── AttendanceClockIn.php
│   ├── AttendanceClockOut.php
│   ├── LeaveRequestApproved.php
│   └── LeaveRequestRejected.php
├── Exports/                       # Data export (Excel/PDF)
├── Helpers/                       # Helper classes
│   └── UserAgentParser.php
├── Http/
│   ├── Controllers/               # ⭐ 105 controllers (6 grup)
│   │   ├── Api/V1/               #    API controllers (36 files)
│   │   ├── Auth/                  #    Authentication (3 files)
│   │   ├── Import/                #    Bulk import (6 files)
│   │   ├── Portal/                #    Employee portal (10 files)
│   │   ├── Reports/               #    Report generation (4 files)
│   │   ├── Settings/              #    Company settings (8 files)
│   │   ├── Superadmin/            #    System admin (15 files)
│   │   └── *.php                  #    Main admin controllers (39 files)
│   ├── Middleware/                 # ⭐ 7 middleware
│   │   ├── SetTenant.php         #    Multi-tenant context
│   │   ├── DetectAttack.php      #    Security: attack detection
│   │   ├── CheckBlockedIp.php    #    Security: IP blocking
│   │   ├── EnsureSuperadmin.php  #    Auth: superadmin only
│   │   ├── EnsureUserIsEmployee.php
│   │   ├── RedirectEmployeeToPortal.php
│   │   └── LogRateLimitHit.php
│   └── Requests/                  # ⭐ Form validation (23 files)
├── Imports/                       # Excel import classes (6 files)
├── Listeners/                     # Event listeners (4 files)
├── Models/                        # ⭐ 59 Eloquent models
├── Notifications/                 # Notification classes
├── Policies/                      # Authorization policies (6 files)
├── Providers/
│   └── AppServiceProvider.php     # Service provider utama
├── Services/                      # ⭐ Business logic (16 services)
├── Scopes/                        # Eloquent query scopes
└── Traits/
    └── LogsActivityTrait.php      # Audit trail trait
```

### Kenapa Strukturnya Begini?

**Prinsip Separation of Concerns:**

```
Request masuk
    ↓
[Middleware] → Security check, tenant context, auth
    ↓
[Controller] → Handle request, orchestrate logic
    ↓
[Form Request] → Validate input
    ↓
[Service] → Business logic (payroll calc, tax, dll)
    ↓
[Model] → Database operations via Eloquent ORM
    ↓
[View/Response] → Blade template atau JSON API
```

---

## 3. Multi-Tenant Architecture

### Konsep Multi-Tenant

Multi-tenant artinya **satu aplikasi melayani banyak perusahaan** (tenant). Setiap perusahaan punya data terpisah dan tidak bisa melihat data perusahaan lain.

```
┌─────────────────────────────────────────────┐
│              GajiPro Application              │
├─────────────┬──────────────┬────────────────┤
│  Company A  │  Company B   │  Company C     │
│  (tenant)   │  (tenant)    │  (tenant)      │
│             │              │                │
│  employees  │  employees   │  employees     │
│  payrolls   │  payrolls    │  payrolls      │
│  attendance │  attendance  │  attendance    │
│  ...        │  ...         │  ...           │
└─────────────┴──────────────┴────────────────┘
         Shared Database (MySQL)
         Isolated by company_id
```

### Strategi Isolasi: Shared Database, Shared Schema

GajiPro menggunakan pendekatan **shared database** dengan kolom `company_id` di setiap tabel tenant-scoped.

```sql
-- Setiap tabel tenant-scoped WAJIB punya company_id
CREATE TABLE employees (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,  -- ⭐ Tenant isolation
    employee_id VARCHAR(20),
    full_name VARCHAR(255),
    ...
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,
    UNIQUE KEY (company_id, employee_id)  -- Unique per tenant
);
```

### SetTenant Middleware - Jantung Multi-Tenant

File: `app/Http/Middleware/SetTenant.php`

```
HTTP Request masuk
    ↓
Auth check: user sudah login?
    ↓
Ambil company_id dari user
    ↓
Load Company model
    ↓
Cek subscription status (aktif/expired?)
    ↓
Set app('tenant', $company) ke container
    ↓
Set Spatie Permission team context
    ↓
Request dilanjutkan ke controller
```

**Cara pakai di Controller:**

```php
// ⭐ SELALU ambil tenant context
$tenant = app('tenant');

// ⭐ SELALU filter by company_id
$employees = Employee::where('company_id', $tenant->id)->get();

// ⭐ SELALU verifikasi ownership untuk show/edit/delete
if ($employee->company_id !== $tenant->id) {
    abort(404); // Jangan 403, pakai 404 agar tidak expose data
}
```

### Mengapa Pakai 404, Bukan 403?

```php
// ❌ SALAH - memberi tahu bahwa data ADA tapi tidak bisa diakses
abort(403, 'Forbidden');

// ✅ BENAR - seolah-olah data tidak ada
abort(404);
```

Pakai 404 agar tenant lain tidak tahu bahwa data tersebut eksis di sistem.

### Tabel yang TIDAK Perlu company_id

Beberapa tabel bersifat global (system-wide):

| Tabel | Alasan |
|-------|--------|
| `users` | Punya company_id, tapi superadmin tidak |
| `subscription_plans` | Plan berlaku untuk semua company |
| `pph21_rates` | Tarif pajak berlaku nasional |
| `ptkp_settings` | PTKP berlaku nasional |
| `blocked_ips` | System-wide security |
| `security_logs` | System-wide logging |

---

## 4. Middleware Pipeline

### Registrasi Middleware

File: `bootstrap/app.php`

```
Setiap HTTP Request melewati middleware pipeline:

Request
  │
  ▼
[DetectAttack]         → Cek SQL injection, XSS, path traversal, dll
  │                       56 regex patterns, 6 jenis serangan
  ▼
[CheckBlockedIp]       → Cek apakah IP di-block
  │
  ▼
[SetTenant]            → Set company context + cek subscription
  │
  ▼
[LogRateLimitHit]      → Log jika rate limit terlampaui
  │
  ▼
[Auth Middleware]       → Cek autentikasi (login)
  │
  ▼
[Role/Permission]      → Cek role & permission (Spatie)
  │
  ▼
Controller
```

### Middleware Aliases

```php
// Di bootstrap/app.php
'tenant'     → SetTenant::class
'superadmin' → EnsureSuperadmin::class
'employee'   → EnsureUserIsEmployee::class
'admin'      → RedirectEmployeeToPortal::class
```

### Penggunaan di Routes

```php
// Admin routes - perlu auth + admin middleware
Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('employees', EmployeeController::class);
});

// Employee portal - perlu auth + employee middleware
Route::middleware(['auth', 'employee'])->prefix('portal')->group(function () {
    Route::get('/dashboard', [Portal\DashboardController::class, 'index']);
});

// Superadmin - perlu auth + superadmin middleware
Route::middleware(['auth', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::resource('companies', Superadmin\CompanyController::class);
});

// API routes - perlu sanctum auth
Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    Route::get('/attendance/today', [Api\AttendanceController::class, 'today']);
});
```

---

## 5. Route Architecture

### 5 Grup Route Utama

```
routes/
├── web.php     → Web dashboard (Blade views)
├── api.php     → REST API (JSON responses)
└── console.php → Artisan scheduled commands
```

### Web Routes (~200 endpoints)

```
┌──────────────────────────────────────────────────────────┐
│                      WEB ROUTES                           │
├──────────────────────────────────────────────────────────┤
│                                                           │
│  1. PUBLIC (tanpa auth)                                   │
│     GET /              → Landing page                     │
│     GET /pricing       → Halaman harga                    │
│     GET /terms         → Syarat & ketentuan               │
│     GET /privacy       → Kebijakan privasi                │
│                                                           │
│  2. GUEST (belum login)                                   │
│     GET/POST /login    → Login (throttle: 5/min)          │
│     GET/POST /register → Register (throttle: 5/min)       │
│     GET/POST /forgot-password (throttle: 3/min)           │
│                                                           │
│  3. ADMIN (auth + admin middleware)                        │
│     GET /dashboard                                        │
│     Resource: employees, departments, positions            │
│     Resource: work-schedules, attendances, holidays        │
│     Resource: leave-types, leave-balances, leave-requests  │
│     Resource: salary-components, employee-salaries         │
│     Resource: payrolls, overtime-requests                  │
│     Resource: reimbursements, announcements                │
│     Settings: company, users, roles, permissions           │
│     Reports: employees, attendance, leave, payroll         │
│     Tax: 1721-A1, SPT 1721                                │
│     Import: bulk data import                               │
│                                                           │
│  4. EMPLOYEE PORTAL (auth + employee middleware)           │
│     GET /portal/dashboard                                  │
│     Attendance: clock in/out, history                      │
│     Leave: request, balance                                │
│     Schedule: view assigned shifts                         │
│     Payslip: view & download                               │
│     Overtime & Reimbursement: request                      │
│     Announcements: view                                    │
│                                                           │
│  5. SUPERADMIN (auth + superadmin middleware)               │
│     Companies, Plans, Subscriptions, Payments              │
│     System: health, queue, email logs, audit               │
│     Security: attack logs, blocked IPs                     │
│                                                           │
└──────────────────────────────────────────────────────────┘
```

### API Routes (~50 endpoints)

```
/api/v1/
├── auth/
│   ├── POST /login              → Login, dapat token
│   ├── POST /demo-register      → Demo registration
│   ├── POST /logout             → Logout, revoke token
│   ├── GET  /profile            → Get user profile
│   └── POST /change-password    → Ganti password
│
├── dashboard/
│   ├── GET  /                   → Dashboard data
│   ├── GET  /attendance-chart   → Chart attendance
│   └── GET  /quick-stats        → Quick statistics
│
├── attendance/
│   ├── GET  /today              → Status attendance hari ini
│   ├── POST /clock-in           → Clock in (GPS + face)
│   ├── POST /clock-out          → Clock out
│   ├── GET  /history            → Riwayat attendance
│   └── GET  /summary            → Summary per bulan
│
├── leaves/                      → CRUD leave requests
├── payslips/                    → View & download payslips
├── overtime/                    → CRUD overtime requests
├── reimbursements/              → CRUD reimbursement requests
├── approvals/                   → Approval workflow
├── announcements/               → Company announcements
├── face-recognition/            → Enroll & verify face
├── office-locations/            → GPS & office data
├── device-tokens/               → Push notification tokens
├── tax-forms/                   → Tax form 1721-A1
└── schedule/                    → Employee schedules
```

---

## 6. Model & Database Layer

### Database Overview

**63+ tabel** terorganisir dalam kategori:

```
┌────────────────────────────────────────────────────┐
│                  DATABASE SCHEMA                    │
├──────────────────┬─────────────────────────────────┤
│ Core             │ companies, users                │
├──────────────────┼─────────────────────────────────┤
│ Organization     │ departments, positions,          │
│                  │ employees, office_locations      │
├──────────────────┼─────────────────────────────────┤
│ Attendance       │ work_schedules, attendances,     │
│                  │ holidays, shift_rotation_*,      │
│                  │ employee_face_embeddings         │
├──────────────────┼─────────────────────────────────┤
│ Leave            │ leave_types, leave_balances,     │
│                  │ leave_requests                   │
├──────────────────┼─────────────────────────────────┤
│ Payroll          │ salary_components,               │
│                  │ employee_salaries, payrolls,     │
│                  │ payroll_items, payroll_settings   │
├──────────────────┼─────────────────────────────────┤
│ Tax & BPJS       │ pph21_settings, pph21_rates,     │
│ (Indonesia)      │ ptkp_settings, bpjs_*_settings,  │
│                  │ thr_settings, tax_form_1721a1,   │
│                  │ spt_1721_*                       │
├──────────────────┼─────────────────────────────────┤
│ Overtime &       │ overtime_settings,               │
│ Reimbursement    │ overtime_requests,               │
│                  │ reimbursements                   │
├──────────────────┼─────────────────────────────────┤
│ Approval         │ approval_workflows,              │
│                  │ approval_workflow_steps           │
├──────────────────┼─────────────────────────────────┤
│ Communication    │ announcements, notifications,    │
│                  │ device_tokens                    │
├──────────────────┼─────────────────────────────────┤
│ Billing          │ subscription_plans,              │
│                  │ subscriptions, payments,         │
│                  │ invoices                         │
├──────────────────┼─────────────────────────────────┤
│ Security         │ security_logs, blocked_ips,      │
│                  │ activity_log, email_logs         │
├──────────────────┼─────────────────────────────────┤
│ Permissions      │ roles, permissions,              │
│ (Spatie)         │ model_has_roles,                 │
│                  │ model_has_permissions             │
└──────────────────┴─────────────────────────────────┘
```

### 59 Models - Relasi Utama

```
Company (tenant)
├── has many → Department
│   └── has many → Position
│       └── has many → Employee
├── has many → Employee
│   ├── belongs to → Department
│   ├── belongs to → Position
│   ├── has many → Attendance
│   ├── has many → LeaveRequest
│   ├── has many → LeaveBalance
│   ├── has one  → EmployeeSalary
│   │   └── has many → EmployeeSalaryComponent
│   ├── has many → OvertimeRequest
│   ├── has many → Reimbursement
│   ├── has many → EmployeeDocument
│   └── has many → EmployeeFaceEmbedding
├── has many → WorkSchedule
├── has many → SalaryComponent
├── has many → Payroll
│   └── has many → PayrollItem
│       └── has many → PayrollItemDetail
├── has many → LeaveType
├── has many → Holiday
├── has many → Announcement
├── has one  → PayrollSetting
├── has one  → Pph21Setting
├── has one  → BpjsTkSetting
├── has one  → BpjsKesSetting
├── has one  → OvertimeSetting
└── has one  → ThrSetting
```

### Konvensi Kolom Database

| Pattern | Contoh | Tipe |
|---------|--------|------|
| `{table}_id` | `company_id`, `department_id` | Foreign key |
| `is_*` | `is_active`, `is_annual` | Boolean |
| `*_at` | `approved_at`, `paid_at` | Timestamp |
| `*_date` | `join_date`, `birth_date` | Date |
| `*_amount` | `base_amount`, `net_amount` | DECIMAL(15,2) |
| `*_rate` | `tax_rate`, `overtime_rate` | DECIMAL(5,2) |
| `status` | `'draft'`, `'approved'` | VARCHAR/Enum |

### Contoh Model Pattern

```php
class Employee extends Model
{
    use HasFactory, SoftDeletes, LogsActivityTrait;

    protected $fillable = [
        'company_id', 'employee_id', 'full_name', 'email',
        'department_id', 'position_id', 'join_date', 'is_active',
    ];

    // ⭐ Laravel 12: gunakan method casts(), bukan property $casts
    protected function casts(): array
    {
        return [
            'join_date' => 'date',
            'birth_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    // ⭐ Auto-generate employee ID saat create
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->employee_id)) {
                $model->employee_id = self::generateEmployeeId($model->company_id);
            }
        });
    }

    // Relationships dengan return type hint
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
```

---

## 7. Service Layer - Business Logic

### 16 Services

Service layer memisahkan **business logic kompleks** dari controller agar controller tetap tipis.

```
app/Services/
├── PayrollCalculationService.php      # ⭐ Kalkulasi gaji lengkap
├── TaxForm1721A1Service.php           # Generate bukti potong
├── Spt1721Service.php                 # SPT 1721 tahunan
├── ThrCalculationService.php          # Kalkulasi THR
├── OvertimeCalculationService.php     # Rate lembur
├── FaceRecognitionService.php         # Face enrollment & verify
├── GpsValidationService.php           # Validasi GPS attendance
├── ScheduleResolverService.php        # Resolve shift per tanggal
├── ShiftRotationGeneratorService.php  # Generate rotasi shift
├── ShiftSwapService.php               # Tukar shift antar karyawan
├── DashboardAnalyticsService.php      # KPI & analytics
├── SystemHealthService.php            # System monitoring
├── PaymentGatewayService.php          # Integrasi payment
├── FcmService.php                     # Firebase Cloud Messaging
├── PushNotificationService.php        # Push notification
└── DemoDataService.php                # Data demo
```

### Contoh Penggunaan Service

```php
// Controller tetap TIPIS - hanya orchestrate
class PayrollController extends Controller
{
    public function process(Payroll $payroll, PayrollCalculationService $service)
    {
        $tenant = app('tenant');

        if ($payroll->company_id !== $tenant->id) {
            abort(404);
        }

        // Business logic di service, bukan di controller
        $service->calculatePayroll($payroll);

        return redirect()
            ->route('payrolls.show', $payroll)
            ->with('success', 'Payroll berhasil diproses.');
    }
}
```

### Kapan Pakai Service vs Controller?

| Logika | Taruh di |
|--------|----------|
| Validasi input | Form Request |
| CRUD sederhana | Controller |
| Kalkulasi kompleks (gaji, pajak) | Service |
| Query database | Model (Eloquent) |
| Authorization | Policy / Middleware |
| Event handling | Listener |

---

## 8. Controller Architecture

### 6 Grup Controller

```
Controllers/
├── [Root]         → 39 admin controllers (CRUD modules)
├── Api/V1/        → 36 API controllers (REST JSON)
├── Auth/          → 3 authentication controllers
├── Portal/        → 10 employee portal controllers
├── Settings/      → 8 settings controllers
├── Reports/       → 4 report controllers
├── Import/        → 6 bulk import controllers
└── Superadmin/    → 15 superadmin controllers
```

### Pattern Controller Standar

```php
class EmployeeController extends Controller
{
    // INDEX - List dengan pagination & filter
    public function index(Request $request): View
    {
        $tenant = app('tenant');  // ⭐ Always get tenant

        $employees = Employee::with(['department', 'position'])
            ->where('company_id', $tenant->id)  // ⭐ Always filter
            ->when($request->search, fn ($q, $s) =>
                $q->where('full_name', 'like', "%{$s}%")
            )
            ->latest()
            ->paginate(15);

        return view('employees.index', compact('employees'));
    }

    // STORE - Validasi via Form Request
    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $tenant = app('tenant');

        Employee::create([
            'company_id' => $tenant->id,  // ⭐ Always set company_id
            ...$request->validated(),
        ]);

        return redirect()
            ->route('employees.index')
            ->with('success', 'Karyawan berhasil ditambahkan.');
    }

    // SHOW - Verifikasi ownership
    public function show(Employee $employee): View
    {
        $tenant = app('tenant');

        if ($employee->company_id !== $tenant->id) {  // ⭐ Always verify
            abort(404);
        }

        return view('employees.show', compact('employee'));
    }
}
```

### Form Request Pattern

```php
class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization di middleware/policy
    }

    public function rules(): array
    {
        $tenant = app('tenant');

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email',
                Rule::unique('employees')->where('company_id', $tenant->id)
            ],
            // ⭐ Validasi foreign key juga scoped per tenant
            'department_id' => ['required',
                Rule::exists('departments', 'id')->where('company_id', $tenant->id)
            ],
        ];
    }
}
```

---

## 9. View Layer (Blade + Alpine.js + Tailwind)

### Layout System

```
resources/views/layouts/
├── admin.blade.php       → Dashboard admin (sidebar + topbar)
├── portal.blade.php      → Employee portal (simplified)
├── guest.blade.php       → Auth pages (login, register)
└── superadmin.blade.php  → Superadmin panel
```

### Struktur View

```
resources/views/
├── layouts/              # Layout templates
├── components/           # Reusable Blade components
│   ├── alert.blade.php
│   ├── badge.blade.php
│   ├── table.blade.php
│   └── confirm-dialog.blade.php
├── dashboard.blade.php   # Main dashboard
├── employees/            # CRUD views per module
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   └── show.blade.php
├── portal/               # Employee portal views
├── superadmin/           # Superadmin views
├── settings/             # Settings views
├── reports/              # Report views
└── landing/              # Public landing pages
```

### Component Standar

```blade
{{-- Buttons --}}
<button class="btn btn-primary">Simpan</button>
<button class="btn btn-danger btn-sm">Hapus</button>

{{-- Badges --}}
<x-badge type="success">Aktif</x-badge>
<x-badge type="warning">Pending</x-badge>

{{-- Alerts --}}
<x-alert type="success" dismissible>Berhasil!</x-alert>

{{-- Tables --}}
<x-table>
    <x-slot name="header"><th>Nama</th></x-slot>
    @foreach($items as $item)
        <tr><td>{{ $item->name }}</td></tr>
    @endforeach
</x-table>

{{-- Forms - SELALU pakai class .input --}}
<input type="text" class="input w-full" name="name">
<select class="input w-full" name="dept">...</select>
```

### Alpine.js untuk Interaktivitas

```blade
{{-- Tab navigation --}}
<div x-data="{ tab: 'general' }">
    <button @click="tab = 'general'">General</button>
    <button @click="tab = 'salary'">Salary</button>
    <div x-show="tab === 'general'">...</div>
    <div x-show="tab === 'salary'">...</div>
</div>

{{-- Confirmation dialog --}}
<button @click="$dispatch('confirm-dialog', {
    title: 'Hapus Karyawan',
    message: 'Yakin ingin menghapus?',
    type: 'danger',
    formAction: '{{ route('employees.destroy', $emp) }}'
})">Hapus</button>
```

---

## 10. Frontend (Flutter) - Struktur Direktori

### Root Directory

```
flutter_jagogajian_app/
├── lib/                  # Source code utama
│   ├── core/            # Core layer (shared)
│   ├── data/            # Data layer (API + models)
│   └── presentation/    # UI layer (19 feature modules)
├── assets/              # Images & ML models
├── android/             # Android native config
├── ios/                 # iOS native config
├── test/                # Tests
└── pubspec.yaml         # Dependencies
```

### Clean Architecture - 3 Layer

```
┌──────────────────────────────────────────┐
│          PRESENTATION LAYER               │
│  (BLoC + Pages + Widgets)                 │
│  19 feature modules                       │
├──────────────────────────────────────────┤
│            DATA LAYER                     │
│  (Datasources + Models)                   │
│  Remote datasources (API calls)           │
│  Request/Response models (DTOs)           │
├──────────────────────────────────────────┤
│            CORE LAYER                     │
│  (Services + Components + Constants)      │
│  ApiService, StorageService, etc.         │
│  Reusable UI components                   │
└──────────────────────────────────────────┘
```

### Core Layer Detail

```
lib/core/
├── constants/
│   ├── variables.dart     # ⭐ API endpoints (SSOT)
│   ├── colors.dart        # Color palette
│   ├── text_styles.dart   # Typography
│   └── spacing.dart       # Spacing constants
├── services/
│   ├── api_service.dart   # ⭐ HTTP client + auth interceptor
│   ├── storage_service.dart    # SharedPreferences wrapper
│   ├── session_service.dart    # Session management
│   ├── location_service.dart   # GPS/Geolocator
│   ├── camera_service.dart     # Camera access
│   ├── face_recognition_service.dart  # TFLite ML
│   └── notification_service.dart      # Firebase FCM
├── components/            # Reusable widgets
│   ├── jago_button.dart
│   ├── jago_input.dart
│   ├── jago_card.dart
│   └── jago_app_bar.dart
└── ml/                    # Machine learning
    └── recognizer.dart    # Face recognition model
```

### Data Layer Detail

```
lib/data/
├── datasources/           # API call implementations
│   ├── auth_remote_datasource.dart
│   ├── attendance_remote_datasource.dart
│   ├── leave_remote_datasource.dart
│   ├── payslip_remote_datasource.dart
│   ├── overtime_remote_datasource.dart
│   ├── reimbursement_remote_datasource.dart
│   ├── approval_remote_datasource.dart
│   ├── announcement_remote_datasource.dart
│   ├── schedule_remote_datasource.dart
│   ├── tax_form_remote_datasource.dart
│   └── ... (15+ datasources)
└── models/
    ├── requests/          # Request DTOs
    │   ├── login_request_model.dart
    │   ├── leave_request_model.dart
    │   └── ...
    └── responses/         # Response DTOs
        ├── auth_response_model.dart
        ├── attendance_today_model.dart
        ├── payslip_model.dart
        └── ...
```

### Presentation Layer - 19 Feature Modules

```
lib/presentation/
├── splash/           # Splash screen + session check
├── auth/             # Login, register, profile (5 BLoCs)
├── home/             # Main navigation
├── dashboard/        # Dashboard stats & charts (3 BLoCs)
├── attendance/       # Clock in/out, history (4 BLoCs)
├── face_recognition/ # Face enrollment & verify (3 BLoCs)
├── schedule/         # View work schedules (1 BLoC)
├── office_location/  # GPS validation & maps (2 BLoCs)
├── leave/            # Leave request & balance (4 BLoCs)
├── overtime/         # Overtime requests (3 BLoCs)
├── payslip/          # Payslip view & download (4 BLoCs)
├── reimbursement/    # Reimbursement requests (5 BLoCs)
├── approval/         # Manager approval (3 BLoCs)
├── announcement/     # Company news (4 BLoCs)
├── notification/     # Push notifications (1 BLoC)
├── tax_form/         # Tax form 1721-A1 (2 BLoCs)
├── profile/          # User profile (2 BLoCs)
├── settings/         # App settings (1 BLoC)
└── loan/             # Loan feature (1 BLoC)
```

---

## 11. Flutter Clean Architecture & BLoC Pattern

### State Management: BLoC (Business Logic Component)

```
User tap "Clock In"
    ↓
UI dispatch → ClockInEvent
    ↓
BLoC handle event:
    emit(AttendanceLoading)
    ↓
    call datasource.clockIn(data)
    ↓
    result.fold(
      (error) => emit(AttendanceError(error)),
      (data)  => emit(AttendanceSuccess(data)),
    )
    ↓
UI rebuild based on state
```

### Contoh BLoC Pattern

```dart
// Events
abstract class AttendanceEvent {}
class ClockInRequested extends AttendanceEvent {
  final double latitude;
  final double longitude;
  final String? faceImage;
}

// States
abstract class AttendanceState {}
class AttendanceInitial extends AttendanceState {}
class AttendanceLoading extends AttendanceState {}
class AttendanceSuccess extends AttendanceState {
  final AttendanceTodayModel data;
}
class AttendanceError extends AttendanceState {
  final String message;
}

// BLoC
class AttendanceBloc extends Bloc<AttendanceEvent, AttendanceState> {
  final AttendanceRemoteDatasource datasource;

  AttendanceBloc(this.datasource) : super(AttendanceInitial()) {
    on<ClockInRequested>(_onClockIn);
  }

  Future<void> _onClockIn(ClockInRequested event, Emitter emit) async {
    emit(AttendanceLoading());

    final result = await datasource.clockIn(
      latitude: event.latitude,
      longitude: event.longitude,
    );

    result.fold(
      (error) => emit(AttendanceError(error)),
      (data) => emit(AttendanceSuccess(data)),
    );
  }
}
```

### Error Handling dengan dartz (Either)

```dart
// Datasource mengembalikan Either<String, Model>
Future<Either<String, AttendanceTodayModel>> clockIn({...}) async {
  try {
    final response = await apiService.post('/attendance/clock-in', body: {...});
    return Right(AttendanceTodayModel.fromJson(response));
  } catch (e) {
    return Left(e.toString());
  }
}
```

### Model Generation dengan Freezed

```dart
@freezed
class PayslipModel with _$PayslipModel {
  const factory PayslipModel({
    required int id,
    required String period,
    @JsonKey(name: 'net_salary') required double netSalary,
    required String status,
  }) = _PayslipModel;

  factory PayslipModel.fromJson(Map<String, dynamic> json) =>
      _$PayslipModelFromJson(json);
}
```

---

## 12. API Contract - Komunikasi BE & FE

### Authentication Flow

```
Flutter App                          Laravel API
    │                                    │
    │  POST /api/v1/auth/login           │
    │  { email, password }               │
    │ ──────────────────────────────────> │
    │                                    │ Validate credentials
    │                                    │ Generate Sanctum token
    │  { token, user, company }          │
    │ <────────────────────────────────── │
    │                                    │
    │  GET /api/v1/attendance/today       │
    │  Header: Authorization: Bearer {token}
    │ ──────────────────────────────────> │
    │                                    │ Validate token
    │                                    │ Set tenant context
    │  { data: {...} }                   │
    │ <────────────────────────────────── │
```

### API Response Format

```json
// Success
{
  "data": { "id": 1, "full_name": "John Doe", ... },
  "message": "Success"
}

// Success dengan pagination
{
  "data": [...],
  "meta": { "current_page": 1, "total": 50, "per_page": 15 }
}

// Error validasi (422)
{
  "message": "Validation failed",
  "errors": { "email": ["Email sudah terdaftar."] }
}

// Unauthorized (401)
{
  "message": "Unauthenticated."
}
```

### Endpoint Mapping (BE ↔ FE)

| Feature | Laravel Route | Flutter Datasource |
|---------|--------------|-------------------|
| Login | `POST /api/v1/auth/login` | `AuthRemoteDatasource.login()` |
| Clock In | `POST /api/v1/attendance/clock-in` | `AttendanceRemoteDatasource.clockIn()` |
| Leave List | `GET /api/v1/leaves` | `LeaveRemoteDatasource.getLeaves()` |
| Payslip | `GET /api/v1/payslips` | `PayslipRemoteDatasource.getPayslips()` |
| Approve | `POST /api/v1/approvals/{id}/approve` | `ApprovalRemoteDatasource.approve()` |

### API Base URL Config

```dart
// Flutter: lib/core/constants/variables.dart
class Variables {
  // Production
  static const String baseUrl = 'https://gajipro.jagoflutter.com';

  // Local development (uncomment saat dev)
  // static const String baseUrl = 'http://192.168.1.100:8000';

  // Endpoints
  static const String login = '/api/v1/auth/login';
  static const String attendanceToday = '/api/v1/attendance/today';
  static const String clockIn = '/api/v1/attendance/clock-in';
  // ... 40+ endpoints
}
```

---

## 13. Security Architecture

### Defense in Depth (Berlapis)

```
Internet
    │
    ▼
[Rate Limiting]          → Laravel throttle (5/min login, 60/min API)
    │
    ▼
[DetectAttack Middleware] → 56 regex patterns
    │                       SQL Injection, XSS, Path Traversal,
    │                       Command Injection, LDAP, XXE
    ▼
[CheckBlockedIp]         → Auto-block setelah 5 serangan kritis/jam
    │
    ▼
[SetTenant]              → Tenant isolation
    │
    ▼
[Auth + Sanctum]         → Token-based authentication
    │
    ▼
[Spatie Permission]      → Role-based access control (RBAC)
    │
    ▼
[Form Request]           → Input validation
    │
    ▼
[Policy]                 → Model-level authorization
    │
    ▼
[Eloquent + company_id]  → Data isolation per tenant
```

### Role-Based Access Control

```
Superadmin ──────────────── System-wide (semua company)
    │
Admin ───────────────────── Full company management
    │
HR Manager ──────────────── Employees, attendance, leave, organization
    │
Payroll Manager ─────────── Salary, payroll, tax, BPJS
    │
Employee ────────────────── Portal self-service only
```

### Audit Trail

```php
// LogsActivityTrait otomatis mencatat:
// - CREATE: siapa membuat apa, kapan
// - UPDATE: field apa yang berubah (before → after)
// - DELETE: siapa menghapus apa, kapan
// - company_id: konteks tenant

// Semua tercatat di tabel activity_log
```

---

## 14. Testing Architecture

### Pest PHP 3 - Test Framework

```
tests/
├── Feature/              # Integration tests
│   ├── Employee/
│   │   └── EmployeeControllerTest.php
│   ├── Attendance/
│   ├── Leave/
│   ├── Payroll/
│   └── ...
├── Unit/                 # Unit tests
│   ├── Services/
│   └── Models/
└── Pest.php             # Pest configuration
```

### Test Pattern

```php
uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup tenant & user
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->user->assignRole('admin');
});

describe('Employee Management', function () {
    it('can list employees', function () {
        Employee::factory()->count(3)->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('employees.index'))
            ->assertOk()
            ->assertViewHas('employees');
    });

    // ⭐ WAJIB: Test tenant isolation
    it('cannot access other tenant data', function () {
        $otherCompany = Company::factory()->create();
        $otherEmployee = Employee::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('employees.show', $otherEmployee))
            ->assertNotFound();
    });
});
```

### Menjalankan Test

```bash
# Semua test
php artisan test --compact

# File spesifik
php artisan test --compact tests/Feature/Employee/EmployeeControllerTest.php

# Filter by name
php artisan test --compact --filter="can create employee"
```

---

## 15. Latihan Praktik

### Latihan 1: Trace Request Flow (15 menit)

Trace alur request dari browser ke database untuk:
1. Buka file `routes/web.php`, cari route `employees.index`
2. Buka controller `EmployeeController@index`
3. Perhatikan bagaimana `app('tenant')` digunakan
4. Lihat view `resources/views/employees/index.blade.php`
5. Gambar diagram alurnya

### Latihan 2: Trace API Flow (15 menit)

Trace alur API call dari Flutter ke Laravel:
1. Buka `lib/core/constants/variables.dart` di Flutter project
2. Cari endpoint attendance clock-in
3. Buka datasource `AttendanceRemoteDatasource`
4. Buka BLoC yang memanggilnya
5. Buka API controller di Laravel `Api/V1/AttendanceController@clockIn`

### Latihan 3: Explore Multi-Tenant (10 menit)

1. Buka `app/Http/Middleware/SetTenant.php`
2. Pahami cara tenant di-set ke container
3. Buka 3 controller berbeda, perhatikan pattern `app('tenant')`
4. Buka 3 model, perhatikan `company_id` di `$fillable`

### Latihan 4: Explore Database Schema (10 menit)

```bash
# Jalankan di terminal
php artisan tinker
> \DB::select('SHOW TABLES');
> \Schema::getColumnListing('employees');
> \Schema::getColumnListing('payrolls');
```

Atau gunakan tool database-schema dari Laravel Boost.

### Latihan 5: Jalankan Test (10 menit)

```bash
# Jalankan semua test
php artisan test --compact

# Lihat test yang ada
ls tests/Feature/
ls tests/Unit/

# Jalankan satu file test
php artisan test --compact tests/Feature/Employee/EmployeeControllerTest.php
```

---

## Rangkuman

### Key Takeaways

1. **Multi-Tenant**: Setiap query HARUS di-filter `company_id` via `app('tenant')`
2. **Separation of Concerns**: Controller tipis → Service untuk logic → Model untuk data
3. **Security Berlapis**: Attack detection → IP blocking → Auth → RBAC → Tenant isolation
4. **2 Interface**: Web (Blade) untuk admin + Mobile (Flutter) untuk employee
5. **API Contract**: REST JSON dengan Sanctum token, format response konsisten
6. **Clean Architecture Flutter**: Core → Data → Presentation dengan BLoC pattern
7. **TDD Mandatory**: Setiap fitur HARUS punya test, terutama test tenant isolation
8. **Indonesia-Specific**: PPh 21, BPJS, THR, PTKP - semua sudah built-in

### Checklist Pemahaman

- [ ] Saya memahami struktur direktori backend (app/, routes/, database/)
- [ ] Saya memahami konsep multi-tenant dan peran `company_id`
- [ ] Saya memahami middleware pipeline (DetectAttack → SetTenant → Auth)
- [ ] Saya memahami flow: Route → Controller → Service → Model → View
- [ ] Saya memahami 5 grup route (public, guest, admin, portal, superadmin)
- [ ] Saya memahami struktur Flutter (core, data, presentation)
- [ ] Saya memahami BLoC pattern dan cara Flutter berkomunikasi dengan API
- [ ] Saya memahami security architecture (RBAC, tenant isolation, audit)
- [ ] Saya bisa trace request dari UI ke database dan sebaliknya
- [ ] Saya bisa menjalankan test dengan Pest PHP

---

> **Sesi Selanjutnya**: Sesi 3 - Hands-on Development: Membuat Fitur Baru dengan TDD
