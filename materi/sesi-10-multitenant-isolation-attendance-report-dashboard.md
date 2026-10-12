# Sesi 10: Multi-Tenant Data Isolation, Attendance Report & Dashboard

> **Durasi**: 3-4 jam
> **Tanggal**: 28 April 2026 (Minggu 4)
> **Prasyarat**: GajiPro running, paham tenant context (Sesi 6), familiar Employee & Attendance module
> **Tujuan**: Menguasai arsitektur multi-tenant data isolation secara end-to-end, memahami middleware security stack, serta mampu menganalisis kode attendance report dan dashboard analytics yang sudah berjalan di GajiPro.
> **Branch**: `single-company` (single-tenant mode) & `main` (multi-tenant SaaS)

---

## Daftar Isi

1. [Overview: Multi-Tenant vs Single-Tenant](#1-overview-multi-tenant-vs-single-tenant)
2. [Arsitektur Middleware Security Stack](#2-arsitektur-middleware-security-stack)
3. [Deep Dive: SetTenant Middleware](#3-deep-dive-settenant-middleware)
4. [Deep Dive: DetectAttack Middleware](#4-deep-dive-detectattack-middleware)
5. [Deep Dive: CheckBlockedIp Middleware](#5-deep-dive-checkblockedip-middleware)
6. [Dual Mode: SaaS vs On-Premise](#6-dual-mode-saas-vs-on-premise)
7. [Tenant Isolation Pattern di Controller](#7-tenant-isolation-pattern-di-controller)
8. [Audit Trail dengan Tenant Context](#8-audit-trail-dengan-tenant-context)
9. [Attendance Report — Arsitektur & Kode](#9-attendance-report--arsitektur--kode)
10. [Dashboard Analytics — Arsitektur & Kode](#10-dashboard-analytics--arsitektur--kode)
11. [Chart.js Integration di Dashboard](#11-chartjs-integration-di-dashboard)
12. [Testing Multi-Tenant Isolation (Pest)](#12-testing-multi-tenant-isolation-pest)
13. [Arsitektur Kode (File Map)](#13-arsitektur-kode-file-map)
14. [Latihan Praktik](#14-latihan-praktik)

---

## 1. Overview: Multi-Tenant vs Single-Tenant

GajiPro dirancang sebagai aplikasi **dual-mode** — bisa dijalankan sebagai SaaS multi-tenant (banyak perusahaan) ATAU single-tenant on-premise (satu perusahaan).

### Konsep Multi-Tenancy

```
┌─────────────────────────────────────────────────────────┐
│                    SATU DATABASE                         │
│                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │ Company A   │  │ Company B   │  │ Company C   │     │
│  │ employees   │  │ employees   │  │ employees   │     │
│  │ attendances │  │ attendances │  │ attendances │     │
│  │ payrolls    │  │ payrolls    │  │ payrolls    │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│                                                         │
│  Setiap tabel punya kolom `company_id`                  │
│  Query WAJIB di-scope: WHERE company_id = ?             │
└─────────────────────────────────────────────────────────┘
```

### Strategi Isolasi Data

GajiPro menggunakan **Row-Level Isolation** — satu database, satu schema, data dipisahkan lewat kolom `company_id`:

| Strategi | Deskripsi | GajiPro |
|----------|-----------|---------|
| Database per Tenant | Tiap tenant punya database sendiri | - |
| Schema per Tenant | Tiap tenant punya schema sendiri | - |
| **Row-Level Isolation** | Semua tenant di tabel yang sama, filter by `company_id` | **Ya** |

**Keuntungan Row-Level:**
- Mudah di-maintain (1 migration untuk semua)
- Mudah di-scale (cukup tambah index)
- Mudah di-backup (1 database)

**Risiko:**
- Bug di query bisa bocorkan data tenant lain
- Setiap developer WAJIB ingat filter `company_id`

---

## 2. Arsitektur Middleware Security Stack

Setiap request HTTP melewati **4 layer middleware** secara berurutan sebelum sampai ke controller:

```
Request Masuk
     │
     ▼
┌─────────────────────────┐
│  1. DetectAttack         │  ← Deteksi serangan (SQL injection, XSS, dll)
│     Severity: CRITICAL   │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│  2. CheckBlockedIp       │  ← Blokir IP yang sudah di-blacklist
│     Severity: HIGH       │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│  3. SetTenant            │  ← Set context tenant (company) ke app container
│     Purpose: ISOLATION   │
└──────────┬──────────────┘
           │
           ▼
┌─────────────────────────┐
│  4. LogRateLimitHit      │  ← Log jika terkena rate limit
│     Purpose: MONITORING  │
└──────────┬──────────────┘
           │
           ▼
     Controller
```

### Registrasi di `bootstrap/app.php`

```php
// File: bootstrap/app.php

->withMiddleware(function (Middleware $middleware): void {
    // Alias untuk route-level middleware
    $middleware->alias([
        'tenant' => SetTenant::class,
        'superadmin' => EnsureSuperadmin::class,
        'employee' => EnsureUserIsEmployee::class,
        'admin' => RedirectEmployeeToPortal::class,
        'abort_if_single_tenant' => AbortIfSingleTenantMode::class,
        'role' => RoleMiddleware::class,
        'permission' => PermissionMiddleware::class,
        'role_or_permission' => RoleOrPermissionMiddleware::class,
    ]);

    // Global middleware untuk SEMUA web request
    $middleware->appendToGroup('web', [
        DetectAttack::class,
        CheckBlockedIp::class,
        SetTenant::class,
        LogRateLimitHit::class,
    ]);

    // Global middleware untuk SEMUA API request
    $middleware->appendToGroup('api', [
        DetectAttack::class,
        CheckBlockedIp::class,
        LogRateLimitHit::class,
    ]);
})
```

**Pelajaran penting:** Di Laravel 12, middleware tidak lagi didaftarkan di `app/Http/Kernel.php`. Semuanya di `bootstrap/app.php` secara deklaratif.

---

## 3. Deep Dive: SetTenant Middleware

File: `app/Http/Middleware/SetTenant.php`

Ini adalah **jantung multi-tenant isolation** di GajiPro.

```php
class SetTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);  // Guest → skip
        }

        // Super admin tidak punya tenant context
        if ($user->company_id === null) {
            return $next($request);
        }

        // Dual mode: single-tenant vs multi-tenant
        $company = single_tenant_mode()
            ? Company::find(locked_company_id()) ?? $user->company
            : $user->company;

        if (! $company) {
            return $next($request);
        }

        // Skip subscription check di single-tenant mode
        if (! single_tenant_mode() && ! $company->isSubscriptionActive()) {
            return redirect('/subscription-expired');
        }

        // SET TENANT — ini yang dipakai di SELURUH aplikasi
        app()->instance('tenant', $company);

        // Set Spatie Permission team context
        setPermissionsTeamId($company->id);

        return $next($request);
    }
}
```

### Analisis Baris per Baris

| Baris | Fungsi |
|-------|--------|
| `$request->user()` | Ambil user yang sedang login via session/token |
| `$user->company_id === null` | Superadmin tidak terikat ke company manapun |
| `single_tenant_mode()` | Helper function, baca dari `config/tenant.php` |
| `locked_company_id()` | Helper function, return company ID yang di-lock |
| `$company->isSubscriptionActive()` | Cek apakah subscription masih aktif (SaaS mode) |
| `app()->instance('tenant', $company)` | **KRITIS** — bind Company ke service container |
| `setPermissionsTeamId($company->id)` | Set team context Spatie Permission |

### Cara Pakai di Controller

```php
// Setelah middleware SetTenant berjalan:
$tenant = app('tenant');  // ← Company object

// Semua query WAJIB pakai company_id
$employees = Employee::where('company_id', $tenant->id)->get();
```

---

## 4. Deep Dive: DetectAttack Middleware

File: `app/Http/Middleware/DetectAttack.php`

Middleware ini mendeteksi **6 jenis serangan** secara real-time:

### Jenis Serangan yang Dideteksi

| Jenis | Severity | Jumlah Pattern | Contoh |
|-------|----------|----------------|--------|
| SQL Injection | Critical | 19 pattern | `' OR 1=1`, `UNION SELECT`, `DROP TABLE` |
| XSS | Critical | 11 pattern | `<script>`, `javascript:`, `onerror=` |
| Path Traversal | Critical | 12 pattern | `../`, `etc/passwd`, `proc/self` |
| Command Injection | Critical | 7 pattern | `; rm -rf /`, `| cat /etc/passwd` |
| LDAP Injection | Warning | 2 pattern | `)(`, null bytes |
| XML/XXE Injection | Critical | 4 pattern | `<!ENTITY`, `SYSTEM "` |

### Contoh Pattern SQL Injection

```php
protected array $sqlInjectionPatterns = [
    '/(\%27)|(\')|(\-\-)|(\%23)|(#)/i',           // Basic
    '/((\%3D)|(=))[^\n]*((\%27)|(\')|(\-\-)|(\%3B)|(;))/i',  // Encoded
    '/union(\s+)select/i',                          // UNION attack
    '/select(\s+)[\w\*\,\s]+(\s+)from/i',          // SELECT FROM
    '/drop(\s+)table/i',                            // DROP TABLE
    '/\bor\b\s+\d+\s*=\s*\d+/i',                   // OR 1=1
    '/sleep\s*\(/i',                                 // Time-based
    '/benchmark\s*\(/i',                             // Benchmark
];
```

### Alur Kerja DetectAttack

```
Request masuk
     │
     ▼
┌─────────────────┐
│ getAllInputs()   │ ← Kumpulkan semua input: query, POST, headers, cookies
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ detectAttacks()  │ ← Match setiap input terhadap semua pattern
└────────┬────────┘
         │
    Ada serangan?
     │         │
    Ya        Tidak
     │         │
     ▼         ▼
┌──────────┐  Continue
│logAttacks│ ← Simpan ke tabel `security_logs`
└────┬─────┘
     │
     ▼
┌──────────────┐
│shouldBlockIp?│ ← 5+ critical attacks dalam 1 jam → auto-block
└──────────────┘
     │
     ▼
Continue (detection only, TIDAK blocking request)
```

### Auto-Block Logic

```php
protected function shouldBlockIp(string $ip, array $attacks): bool
{
    $hasCritical = collect($attacks)->contains('severity', 'critical');

    if (! $hasCritical) {
        return false;
    }

    // Hitung serangan critical dari IP ini dalam 1 jam terakhir
    $recentAttacks = SecurityLog::where('ip_address', $ip)
        ->where('severity', 'critical')
        ->where('created_at', '>=', now()->subHour())
        ->count();

    // Block jika >= 5 critical attacks
    return $recentAttacks >= 5;
}

protected function blockAttacker(string $ip): void
{
    // Block selama 24 jam (otomatis, bukan permanent)
    BlockedIp::block(
        $ip,
        'Auto-blocked: Multiple critical attack attempts detected',
        null,    // System block (bukan manual)
        1440     // 24 jam dalam menit
    );
}
```

> **Catatan Penting:** Middleware ini hanya **mendeteksi dan log**, bukan langsung memblok request. Ini design yang sengaja agar tidak mengganggu legitimate user yang kebetulan input mirip pattern serangan.

---

## 5. Deep Dive: CheckBlockedIp Middleware

File: `app/Http/Middleware/CheckBlockedIp.php`

```php
class CheckBlockedIp
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('blocked_ips')) {
            return $next($request);  // Skip jika tabel belum ada
        }

        $ip = $request->ip();

        try {
            if (BlockedIp::isBlocked($ip)) {
                // Log akses dari IP yang diblok
                SecurityLog::log(
                    'blocked_ip_access',
                    'warning',
                    "Blocked IP {$ip} attempted to access: {$request->path()}",
                    null,
                    $request
                );

                // Return 403 (JSON untuk API, abort untuk web)
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Access denied. Your IP has been blocked.',
                    ], Response::HTTP_FORBIDDEN);
                }

                abort(Response::HTTP_FORBIDDEN, 'Access denied.');
            }
        } catch (\Exception $e) {
            return $next($request);  // Fail-open: jika error, lanjutkan
        }

        return $next($request);
    }
}
```

### Poin Menarik

1. **Fail-open design**: Jika terjadi error (misal database down), request tetap dilanjutkan. Lebih baik sedikit vulnerability daripada seluruh aplikasi down.
2. **Dual response**: JSON untuk API client, HTML abort untuk browser.
3. **Schema check**: Cek `Schema::hasTable()` agar tidak error saat migration belum jalan.

---

## 6. Dual Mode: SaaS vs On-Premise

### Konfigurasi di `config/tenant.php`

```php
return [
    // Single-tenant mode (on-premise)
    'single_mode' => env('SINGLE_TENANT_MODE', false),

    // Company ID yang di-lock di single-tenant mode
    'company_id' => (int) env('SINGLE_TENANT_COMPANY_ID', 1),

    // Brand customization
    'brand' => [
        'name' => env('BRAND_NAME', 'GajiPro'),
        'logo_path' => env('BRAND_LOGO_PATH', 'images/brand/logo.svg'),
        'primary_color' => env('BRAND_PRIMARY_COLOR', '#2563eb'),
        // ...
    ],
];
```

### Helper Functions (`app/helpers.php`)

```php
function single_tenant_mode(): bool
{
    return (bool) config('tenant.single_mode', false);
}

function locked_company_id(): int
{
    return (int) config('tenant.company_id', 1);
}

function brand_name(): string
{
    return (string) (config('tenant.brand.name') ?: config('app.name', 'GajiPro'));
}
```

### AbortIfSingleTenantMode Middleware

```php
// Middleware untuk disable fitur SaaS di single-tenant mode
class AbortIfSingleTenantMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (single_tenant_mode()) {
            abort(404);  // Fitur SaaS tidak tersedia
        }
        return $next($request);
    }
}
```

### Perbandingan Mode

| Fitur | Multi-Tenant (SaaS) | Single-Tenant (On-Premise) |
|-------|---------------------|---------------------------|
| Public Registration | Ya | Tidak (404) |
| Billing/Subscription | Ya | Bypass |
| Superadmin Panel | Ya | Hidden (404) |
| Brand Customization | Default GajiPro | Custom per client |
| Subscription Check | Aktif | Dilewati |
| Tenant Context | Dari `$user->company` | Dari `locked_company_id()` |

### Contoh `.env` untuk Single-Tenant

```env
SINGLE_TENANT_MODE=true
SINGLE_TENANT_COMPANY_ID=1
BRAND_NAME="PT Gemilang Jaya"
BRAND_PRIMARY_COLOR="#1e40af"
```

---

## 7. Tenant Isolation Pattern di Controller

### Pattern Dasar (Wajib di Semua Controller)

```php
public function index(Request $request): View
{
    $tenant = app('tenant');  // atau auth()->user()->company

    // WAJIB: scope by company_id
    $items = Model::with(['relation'])
        ->where('company_id', $tenant->id)
        ->paginate(15);

    return view('module.index', compact('items'));
}
```

### Pattern Ownership Check (Show/Edit/Delete)

```php
public function show(Model $model): View
{
    $tenant = app('tenant');

    // Cek apakah data milik tenant ini
    if ($model->company_id !== $tenant->id) {
        abort(404);  // Bukan 403! Agar attacker tidak tahu data ada
    }

    return view('module.show', compact('model'));
}
```

> **Kenapa 404 bukan 403?** Jika return 403 (Forbidden), attacker tahu bahwa resource exist tapi dia tidak boleh akses. Dengan 404 (Not Found), attacker tidak tahu apakah resource ada atau tidak. Ini teknik **information hiding**.

### Pattern Filter dengan Tenant Scope

Contoh dari `AttendanceReportController`:

```php
public function index(Request $request)
{
    $company = auth()->user()->company;
    $companyId = $company->id;

    $query = Attendance::with(['employee.department'])
        ->where('company_id', $companyId)  // ← TENANT ISOLATION
        ->whereBetween('date', [$startDate, $endDate]);

    // Filter tambahan tetap dalam scope tenant
    if ($request->filled('department_id')) {
        $query->whereHas('employee', function ($q) use ($request) {
            $q->where('department_id', $request->department_id);
        });
    }

    $attendances = $query->orderByDesc('date')->get();
    // ...
}
```

### Company Timezone Awareness

GajiPro mendukung **timezone per company**. Ini penting untuk attendance:

```php
// Company Model — timezone methods
public function now(): Carbon
{
    return Carbon::now($this->timezone);  // e.g., Asia/Jakarta
}

public function today(): Carbon
{
    return Carbon::today($this->timezone);
}

// Penggunaan di controller:
$company = auth()->user()->company;
$companyNow = $company->now();         // Waktu sekarang di timezone company
$companyToday = $company->today();     // Tanggal hari ini di timezone company

// Attendance query pakai timezone company
$presentToday = Attendance::where('company_id', $company->id)
    ->whereDate('date', $companyToday)  // ← bukan today() global!
    ->whereNotNull('clock_in')
    ->count();
```

---

## 8. Audit Trail dengan Tenant Context

### LogsActivityTrait

File: `app/Traits/LogsActivityTrait.php`

```php
trait LogsActivityTrait
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly($this->getLogAttributes())
            ->logOnlyDirty()          // Hanya log field yang berubah
            ->dontSubmitEmptyLogs()   // Skip jika tidak ada perubahan
            ->useLogName($this->getLogName());
    }

    // Auto-set company_id di activity log
    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($this->getAttribute('company_id')) {
            $activity->company_id = $this->company_id;
        } elseif (app()->bound('tenant') && app('tenant')) {
            $activity->company_id = app('tenant')->id;
        }
    }
}
```

**Key point:** Activity log juga di-isolasi per tenant via `company_id`. Artinya Admin Company A tidak bisa melihat log aktivitas Company B.

---

## 9. Attendance Report — Arsitektur & Kode

### File Map

| File | Fungsi |
|------|--------|
| `app/Http/Controllers/Reports/AttendanceReportController.php` | Controller 4 endpoint |
| `resources/views/reports/attendance/index.blade.php` | Rekap kehadiran bulanan |
| `resources/views/reports/attendance/daily.blade.php` | Laporan harian |
| `resources/views/reports/attendance/lateness.blade.php` | Laporan keterlambatan |
| `resources/views/reports/attendance/pdf.blade.php` | Template PDF export |

### Attendance Model — Core Fields

```php
// File: app/Models/Attendance.php

protected $fillable = [
    'company_id',           // Tenant isolation
    'employee_id',          // Siapa
    'office_location_id',   // Di mana (clock in)
    'work_schedule_id',     // Jadwal kerja apa
    'date',                 // Tanggal kehadiran
    'scheduled_start',      // Jadwal masuk
    'scheduled_end',        // Jadwal pulang
    'clock_in',             // Waktu clock in aktual
    'clock_out',            // Waktu clock out aktual
    'clock_in_latitude',    // GPS latitude
    'clock_in_longitude',   // GPS longitude
    'clock_in_photo',       // Foto selfie
    'face_verified',        // Apakah face recognition valid
    'face_confidence',      // Confidence score
    'late_minutes',         // Berapa menit terlambat
    'early_leave_minutes',  // Berapa menit pulang cepat
    'overtime_minutes',     // Berapa menit lembur
    'working_minutes',      // Total jam kerja (menit)
    'status',               // present, late, absent, leave, holiday
    'clock_in_status',      // on_time, late, very_late
    'clock_out_status',     // on_time, early, overtime
];
```

### Clock In Logic

```php
public function clockIn(array $data = []): self
{
    $now = $this->company ? $this->company->now() : Carbon::now();

    $this->clock_in = $now;
    $this->clock_in_ip = $data['ip'] ?? null;
    $this->clock_in_latitude = $data['latitude'] ?? null;
    $this->clock_in_longitude = $data['longitude'] ?? null;
    $this->clock_in_photo = $data['photo'] ?? null;

    // Hitung keterlambatan
    $scheduledStart = $this->getScheduledStartDatetime();
    if ($scheduledStart) {
        $tolerance = $this->workSchedule?->late_tolerance ?? 15;

        if ($now->gt($scheduledStart->copy()->addMinutes($tolerance))) {
            $this->late_minutes = $scheduledStart->diffInMinutes($now);
            $this->clock_in_status = $this->late_minutes > 30 ? 'very_late' : 'late';
            $this->status = 'late';
        } else {
            $this->clock_in_status = 'on_time';
            $this->status = 'present';
        }
    }

    $this->save();
    return $this;
}
```

### Clock Out Logic dengan Overnight Shift

```php
public function clockOut(array $data = []): self
{
    $now = $this->company ? $this->company->now() : Carbon::now();

    $this->clock_out = $now;

    // Hitung working minutes
    if ($this->clock_in) {
        $workingMinutes = $this->clock_in->diffInMinutes($now);

        // Kurangi durasi istirahat
        if ($this->workSchedule && $this->workSchedule->break_duration) {
            $workingMinutes -= $this->workSchedule->break_duration;
        }

        $this->working_minutes = max(0, $workingMinutes);
    }

    // Hitung early leave / overtime (support overnight shift!)
    $scheduledEnd = $this->getScheduledEndDatetime();
    if ($scheduledEnd) {
        $tolerance = $this->workSchedule?->early_leave_tolerance ?? 15;

        if ($now->lt($scheduledEnd->copy()->subMinutes($tolerance))) {
            $this->early_leave_minutes = $now->diffInMinutes($scheduledEnd);
            $this->clock_out_status = 'early';
        } elseif ($now->gt($scheduledEnd)) {
            $this->overtime_minutes = $scheduledEnd->diffInMinutes($now);
            $this->clock_out_status = 'overtime';
        } else {
            $this->clock_out_status = 'on_time';
        }
    }

    $this->save();
    return $this;
}

// Overnight shift support
public function getScheduledEndDatetime(): ?Carbon
{
    // ...
    $scheduledEnd = $shiftDate->copy()->setTimeFromTimeString($timeStr);

    // Jika shift overnight (misal 22:00 - 06:00), end time di hari berikutnya
    if ($this->workSchedule && $this->workSchedule->is_overnight) {
        $scheduledEnd->addDay();
    }

    return $scheduledEnd;
}
```

### Report: Rekap Bulanan

```php
// AttendanceReportController@index
public function index(Request $request)
{
    $company = auth()->user()->company;
    $companyId = $company->id;
    $companyNow = $company->now();

    // Default: bulan ini
    $startDate = $request->get('start_date', $companyNow->copy()->startOfMonth()->format('Y-m-d'));
    $endDate = $request->get('end_date', $companyNow->copy()->endOfMonth()->format('Y-m-d'));

    $query = Attendance::with(['employee.department'])
        ->where('company_id', $companyId)
        ->whereBetween('date', [$startDate, $endDate]);

    // Filter opsional
    if ($request->filled('department_id')) {
        $query->whereHas('employee', fn ($q) =>
            $q->where('department_id', $request->department_id)
        );
    }

    $attendances = $query->orderByDesc('date')->get();
    $summary = $this->getSummary($attendances);

    return view('reports.attendance.index', compact(
        'attendances', 'summary', 'departments', 'employees', 'startDate', 'endDate'
    ));
}

private function getSummary($attendances): array
{
    return [
        'total' => $attendances->count(),
        'present' => $attendances->where('clock_in_status', 'on_time')->count(),
        'late' => $attendances->whereIn('clock_in_status', ['late', 'very_late'])->count(),
        'absent' => $attendances->where('status', 'absent')->count(),
        'total_late_minutes' => $attendances->sum('late_minutes'),
        'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
    ];
}
```

### Report: Harian

```php
// AttendanceReportController@daily
public function daily(Request $request)
{
    // ...
    $summary = [
        'total_employees' => $activeEmployees,
        'present' => $attendances->where('clock_in_status', 'on_time')->count(),
        'late' => $attendances->whereIn('clock_in_status', ['late', 'very_late'])->count(),
        'absent' => $activeEmployees - $attendances->count(),
        'attendance_rate' => $activeEmployees > 0
            ? round(($attendances->count() / $activeEmployees) * 100, 1)
            : 0,
    ];
}
```

### Report: Keterlambatan (Lateness Analysis)

```php
// AttendanceReportController@lateness
$lateAttendances = Attendance::with(['employee.department'])
    ->where('company_id', $companyId)
    ->whereBetween('date', [$startDate, $endDate])
    ->whereIn('clock_in_status', ['late', 'very_late'])
    ->where('late_minutes', '>', 0)
    ->orderByDesc('late_minutes')
    ->get();

// Group by employee → statistik per orang
$employeeLateStats = $lateAttendances->groupBy('employee_id')->map(function ($attendances) {
    return [
        'employee' => $attendances->first()->employee,
        'total_late' => $attendances->count(),
        'total_minutes' => $attendances->sum('late_minutes'),
        'avg_minutes' => round($attendances->avg('late_minutes')),
    ];
})->sortByDesc('total_late')->values();
```

### Export CSV/PDF

```php
// Export Excel (CSV)
private function exportExcel($attendances, $startDate, $endDate)
{
    $callback = function () use ($attendances) {
        $file = fopen('php://output', 'w');

        fputcsv($file, [
            'No', 'Tanggal', 'Nama Karyawan', 'Departemen',
            'Clock In', 'Clock Out', 'Status',
            'Terlambat (menit)', 'Lembur (menit)',
        ]);

        foreach ($attendances as $index => $attendance) {
            fputcsv($file, [
                $index + 1,
                $attendance->date->format('d/m/Y'),
                $attendance->employee->full_name ?? '-',
                $attendance->employee->department?->name ?? '-',
                $attendance->clock_in?->format('H:i') ?? '-',
                $attendance->clock_out?->format('H:i') ?? '-',
                ucfirst($attendance->clock_in_status ?? $attendance->status),
                $attendance->late_minutes,
                $attendance->overtime_minutes,
            ]);
        }
        fclose($file);
    };

    return response()->stream($callback, 200, $headers);
}

// Export PDF (menggunakan DomPDF)
$pdf = Pdf::loadView('reports.attendance.pdf', compact('attendances', 'company', 'startDate', 'endDate'));
return $pdf->download('laporan-kehadiran-'.$startDate.'-'.$endDate.'.pdf');
```

---

## 10. Dashboard Analytics — Arsitektur & Kode

### File Map

| File | Fungsi |
|------|--------|
| `app/Http/Controllers/DashboardController.php` | Orchestrator — kumpulkan semua data |
| `app/Services/DashboardAnalyticsService.php` | Chart data & analytics calculations |
| `resources/views/dashboard.blade.php` | View dengan Chart.js |

### DashboardController — Arsitektur

```php
class DashboardController extends Controller
{
    public function __construct(
        private DashboardAnalyticsService $analyticsService  // Dependency Injection
    ) {}

    public function index()
    {
        $user = auth()->user();
        $company = $user->company;

        // Superadmin tanpa company → empty dashboard
        if (! $company) {
            return view('dashboard', [...empty data...]);
        }

        // Kumpulkan semua data
        $stats = $this->getStats($company);
        $attendanceToday = $this->getAttendanceToday($company);
        $recentClockIns = $this->getRecentClockIns($company);
        $recentEmployees = $this->getRecentEmployees($company->id);
        $pendingApprovals = $this->getPendingApprovals($company->id);
        $birthdaysThisMonth = $this->getBirthdaysThisMonth($company->id, $company);
        $expiringContracts = $this->getExpiringContracts($company->id);
        $analytics = $this->analyticsService->getAllAnalytics($company->id, $company);

        return view('dashboard', compact(...));
    }
}
```

### Stat Cards Data

```php
private function getStats(Company $company): array
{
    $companyNow = $company->now();
    $companyToday = $company->today();

    return [
        // Total karyawan aktif
        'total_employees' => Employee::where('company_id', $company->id)
            ->where('is_active', true)->count(),

        // Hadir hari ini (pakai timezone company!)
        'present_today' => Attendance::where('company_id', $company->id)
            ->whereDate('date', $companyToday)
            ->whereNotNull('clock_in')->count(),

        // Cuti pending
        'pending_leaves' => LeaveRequest::where('company_id', $company->id)
            ->where('status', 'pending')->count(),

        // Total payroll bulan ini (optimized dengan JOIN)
        'total_payroll_this_month' => PayrollItem::join('payrolls', ...)
            ->where('payrolls.company_id', $company->id)
            ->where('payrolls.period_month', $companyNow->month)
            ->sum('payroll_items.net_salary'),

        // Persentase kehadiran
        'attendance_percentage' => round(($presentToday / $totalEmployees) * 100, 1),
    ];
}
```

### Attendance Today — Optimized Single Query

```php
private function getAttendanceToday(Company $company): array
{
    $companyToday = $company->today();

    // Satu query dengan aggregate, bukan load semua record!
    $attendanceStats = Attendance::where('company_id', $company->id)
        ->whereDate('date', $companyToday)
        ->selectRaw("
            SUM(CASE WHEN clock_in_status = 'on_time' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN clock_in_status IN ('late', 'very_late') THEN 1 ELSE 0 END) as late
        ")
        ->first();

    $present = (int) ($attendanceStats->present ?? 0);
    $late = (int) ($attendanceStats->late ?? 0);

    // Cuti hari ini
    $onLeave = LeaveRequest::where('company_id', $company->id)
        ->where('status', 'approved')
        ->whereDate('start_date', '<=', $companyToday)
        ->whereDate('end_date', '>=', $companyToday)
        ->count();

    // Absent = Total - Present - Late - Leave
    $absent = max(0, $totalActive - $present - $late - $onLeave);

    return compact('present', 'late', 'on_leave', 'absent');
}
```

> **Teknik optimisasi:** Gunakan `selectRaw()` dengan `CASE WHEN` untuk menghitung beberapa aggregate dalam 1 query, daripada `->get()` lalu filter di PHP.

### DashboardAnalyticsService — Chart Data

```php
class DashboardAnalyticsService
{
    // Warna chart
    private array $colors = [
        '#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6',
        '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
    ];

    public function getAllAnalytics(int $companyId, ?Company $company = null): array
    {
        return [
            'attendance_chart' => $this->getAttendanceChartData($companyId, 14, $company),
            'employee_by_department' => $this->getEmployeeByDepartmentData($companyId),
            'payroll_trend' => $this->getPayrollTrendData($companyId, 6, $company),
            'employee_status' => $this->getEmployeeStatusDistribution($companyId),
            'leave_statistics' => $this->getLeaveStatistics($companyId, $company),
            'monthly_summary' => $this->getMonthlySummary($companyId, $company),
        ];
    }
}
```

### Attendance Chart — Optimized

```php
public function getAttendanceChartData(int $companyId, int $days = 30, ?Company $company = null): array
{
    // SATU query untuk semua data attendance 14 hari
    $attendanceData = Attendance::where('company_id', $companyId)
        ->whereBetween('date', [$startDate, $endDate])
        ->whereNotNull('clock_in')
        ->select(
            DB::raw('DATE(date) as attendance_date'),
            'clock_in_status',
            DB::raw('COUNT(*) as count')
        )
        ->groupBy(DB::raw('DATE(date)'), 'clock_in_status')
        ->get()
        ->groupBy('attendance_date');  // Collection groupBy untuk akses cepat

    // Loop per hari, ambil data dari collection (bukan query lagi)
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = $companyNow->copy()->subDays($i);
        $dateKey = $date->format('Y-m-d');

        $dayData = $attendanceData->get($dateKey, collect());

        $presentCount = $dayData->where('clock_in_status', 'on_time')->sum('count');
        $lateCount = $dayData->whereIn('clock_in_status', ['late', 'very_late'])->sum('count');
        $absentCount = max(0, $totalEmployees - $presentCount - $lateCount);

        $present[] = (int) $presentCount;
        $late[] = (int) $lateCount;
        $absent[] = (int) $absentCount;
    }

    return [
        'labels' => $labels,
        'datasets' => compact('present', 'late', 'absent'),
    ];
}
```

> **Optimisasi:** Tanpa optimisasi, 14 hari = 14 query. Dengan `groupBy` di database + Collection, cukup **1 query**.

### Payroll Trend — Optimized

```php
public function getPayrollTrendData(int $companyId, int $months = 6, ?Company $company = null): array
{
    // SATU query dengan JOIN untuk semua bulan
    $payrollTotals = PayrollItem::join('payrolls', 'payroll_items.payroll_id', '=', 'payrolls.id')
        ->where('payrolls.company_id', $companyId)
        ->where(function ($query) use ($periods) {
            foreach ($periods as $period) {
                $query->orWhere(function ($q) use ($period) {
                    $q->where('payrolls.period_year', $period['year'])
                        ->where('payrolls.period_month', $period['month']);
                });
            }
        })
        ->select(
            'payrolls.period_year',
            'payrolls.period_month',
            DB::raw('SUM(payroll_items.net_salary) as total')
        )
        ->groupBy('payrolls.period_year', 'payrolls.period_month')
        ->get()
        ->keyBy(fn ($item) => $item->period_year.'-'.str_pad($item->period_month, 2, '0', STR_PAD_LEFT));
    // ...
}
```

---

## 11. Chart.js Integration di Dashboard

### Layout Dashboard View

```
┌─────────────────────────────────────────────────────┐
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────┐ │
│  │ Total    │ │ Hadir    │ │ Pengajuan│ │ Total  │ │
│  │ Karyawan │ │ Hari Ini │ │ Cuti     │ │ Gaji   │ │
│  └──────────┘ └──────────┘ └──────────┘ └────────┘ │
│                                                     │
│  ┌────────────────────┐ ┌────────────────────┐     │
│  │ Trend Kehadiran    │ │ Trend Payroll      │     │
│  │ (Line Chart)       │ │ (Bar Chart)        │     │
│  └────────────────────┘ └────────────────────┘     │
│                                                     │
│  ┌────────────┐ ┌──────────┐ ┌───────────────┐    │
│  │ Karyawan   │ │ Status   │ │ Statistik     │    │
│  │ per Dept   │ │ Karyawan │ │ Cuti          │    │
│  │ (Doughnut) │ │(Doughnut)│ │ (Doughnut)    │    │
│  └────────────┘ └──────────┘ └───────────────┘    │
│                                                     │
│  ┌──────────────────────┐ ┌──────────────────────┐ │
│  │  Kehadiran Hari Ini  │ │  Ringkasan Bulanan   │ │
│  │  + Recent Clock-ins  │ │  + Pending Approvals │ │
│  │  + Karyawan Terbaru  │ │  + Ulang Tahun       │ │
│  │                      │ │  + Kontrak Berakhir  │ │
│  └──────────────────────┘ └──────────────────────┘ │
└─────────────────────────────────────────────────────┘
```

### Chart.js — Attendance Line Chart

```javascript
// File: resources/views/dashboard.blade.php

new Chart(attendanceCtx, {
    type: 'line',
    data: {
        labels: @json($analytics['attendance_chart']['labels']),
        datasets: [
            {
                label: 'Hadir',
                data: @json($analytics['attendance_chart']['datasets']['present']),
                borderColor: '#10b981',           // green
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4                       // Curved line
            },
            {
                label: 'Terlambat',
                data: @json($analytics['attendance_chart']['datasets']['late']),
                borderColor: '#f59e0b',           // amber
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
```

### Chart.js — Payroll Bar Chart

```javascript
new Chart(payrollCtx, {
    type: 'bar',
    data: {
        labels: @json($analytics['payroll_trend']['labels']),
        datasets: [{
            label: 'Total Payroll',
            data: @json($analytics['payroll_trend']['data']),
            backgroundColor: '#3b82f6',
            borderRadius: 8
        }]
    },
    options: {
        scales: {
            y: {
                ticks: {
                    callback: function(value) {
                        return 'Rp ' + (value / 1000000).toFixed(0) + 'jt';  // Format jutaan
                    }
                }
            }
        }
    }
});
```

### Chart.js — Doughnut Charts

```javascript
// Department / Status / Leave — semua pakai pattern yang sama
new Chart(departmentCtx, {
    type: 'doughnut',
    data: {
        labels: @json($analytics['employee_by_department']['labels']),
        datasets: [{
            data: @json($analytics['employee_by_department']['data']),
            backgroundColor: @json($analytics['employee_by_department']['colors']),
            borderWidth: 0
        }]
    },
    options: {
        cutout: '60%',  // Donut hole size
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12, padding: 8 } }
        }
    }
});
```

### Blade Pattern: Passing Data ke Chart.js

```blade
{{-- Data dari PHP ke JavaScript via @json --}}
<script>
    const labels = @json($analytics['attendance_chart']['labels']);
    const presentData = @json($analytics['attendance_chart']['datasets']['present']);
</script>
```

> **Best practice:** Gunakan `@json()` Blade directive untuk pass data PHP ke JavaScript. Ini secara otomatis melakukan `json_encode()` dengan proper escaping.

---

## 12. Testing Multi-Tenant Isolation (Pest)

### Dashboard Test — Tenant Isolation

```php
// File: tests/Feature/Dashboard/DashboardControllerTest.php

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->actingAs($this->user);
});

it('only shows data from the same company (multi-tenant)', function () {
    // Data milik company ini
    Employee::factory()->count(5)->create([
        'company_id' => $this->company->id,
    ]);

    // Data milik company LAIN (harus TIDAK muncul!)
    $otherCompany = Company::factory()->create();
    Employee::factory()->count(10)->create([
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->get(route('dashboard'));

    $response->assertStatus(200);
    // Harus 5, BUKAN 15!
    expect($response->viewData('stats')['total_employees'])->toBe(5);
});
```

### Dashboard Test — Attendance Count

```php
it('shows today attendance count', function () {
    $employees = Employee::factory()->count(10)->create([
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);

    // Clock in 7 dari 10 karyawan
    foreach ($employees->take(7) as $employee) {
        Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'date' => $this->company->today(),
            'clock_in' => $this->company->now()->setTime(8, 0),
        ]);
    }

    $response = $this->get(route('dashboard'));

    expect($response->viewData('stats')['present_today'])->toBe(7);
});
```

### Attendance Report Test

```php
// File: tests/Feature/Reports/AttendanceReportControllerTest.php

it('can filter by date range', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Attendance dalam range
    Attendance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $employee->id,
        'date' => now()->subDays(5),
    ]);

    // Attendance di LUAR range
    Attendance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $employee->id,
        'date' => now()->subDays(20),
    ]);

    $response = $this->get(route('reports.attendance', [
        'start_date' => now()->subDays(10)->format('Y-m-d'),
        'end_date' => now()->format('Y-m-d'),
    ]));

    $response->assertStatus(200);
});

it('can export attendance to excel', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
    ]);

    Attendance::factory()->count(5)->create([
        'company_id' => $this->company->id,
        'employee_id' => $employee->id,
    ]);

    $response = $this->get(route('reports.attendance.export', ['format' => 'excel']));

    $response->assertStatus(200);
    $response->assertDownload();
});
```

### Test Pattern: Tenant Isolation (WAJIB di Setiap Feature Test)

```php
// Template test tenant isolation yang HARUS ada
it('prevents access to other tenant data', function () {
    $otherCompany = Company::factory()->create();
    $otherEmployee = Employee::factory()->create([
        'company_id' => $otherCompany->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('employees.show', $otherEmployee));

    $response->assertNotFound();  // 404, bukan 403!
});
```

---

## 13. Arsitektur Kode (File Map)

### Middleware Stack (Security & Tenant)

```
app/Http/Middleware/
├── DetectAttack.php             ← 6 jenis serangan, 55+ regex patterns
├── CheckBlockedIp.php           ← Block IP dari blacklist
├── SetTenant.php                ← Set tenant context (company)
├── AbortIfSingleTenantMode.php  ← Disable fitur SaaS di on-premise
├── EnsureSuperadmin.php         ← Guard superadmin routes
├── EnsureUserIsEmployee.php     ← Guard portal routes
├── RedirectEmployeeToPortal.php ← Redirect employee ke portal
└── LogRateLimitHit.php          ← Log rate limit events
```

### Dashboard

```
app/Http/Controllers/DashboardController.php     ← 8 data sources
app/Services/DashboardAnalyticsService.php        ← 6 chart analytics
resources/views/dashboard.blade.php               ← Stat cards + Charts
```

### Attendance Report

```
app/Http/Controllers/Reports/AttendanceReportController.php
├── index()     → Rekap bulanan
├── daily()     → Laporan harian
├── lateness()  → Analisis keterlambatan
└── export()    → CSV / PDF

resources/views/reports/attendance/
├── index.blade.php      ← Summary stats + filter + table
├── daily.blade.php      ← Per-tanggal + summary
├── lateness.blade.php   ← Group by employee
└── pdf.blade.php        ← Template DomPDF
```

### Model & Tenant

```
app/Models/Company.php         ← Timezone, subscription, brand
app/Models/Attendance.php      ← Clock in/out, overnight shift
app/Traits/LogsActivityTrait.php ← Auto company_id di audit log
app/helpers.php                ← single_tenant_mode(), brand_name()
config/tenant.php              ← Single/multi-tenant config
bootstrap/app.php              ← Middleware registration
```

### Tests

```
tests/Feature/Dashboard/DashboardControllerTest.php
tests/Feature/DashboardAnalytics/DashboardAnalyticsServiceTest.php
tests/Feature/Reports/AttendanceReportControllerTest.php
tests/Feature/Attendance/AttendanceControllerTest.php
tests/Feature/Models/AttendanceTest.php
```

---

## 14. Latihan Praktik

### Latihan 1: Trace Request Flow (30 menit)

Buka browser, login sebagai admin, dan akses halaman Dashboard (`/dashboard`). Trace alur request dari middleware sampai view:

1. Buka `bootstrap/app.php` — identify middleware yang dijalankan
2. Buka `SetTenant.php` — apa yang terjadi jika user belum login?
3. Buka `DashboardController.php` — berapa query ke database?
4. Buka `dashboard.blade.php` — identify semua `@json()` calls

### Latihan 2: Tambah Widget "Attendance Rate Mingguan" (45 menit)

Di `DashboardAnalyticsService.php`, buat method baru:

```php
public function getWeeklyAttendanceRate(int $companyId, ?Company $company = null): array
{
    // Hitung attendance rate per hari (7 hari terakhir)
    // Return: ['labels' => ['Sen', 'Sel', ...], 'data' => [95.5, 87.2, ...]]
}
```

Lalu tampilkan di dashboard sebagai progress bar atau chart baru.

### Latihan 3: Test Tenant Isolation pada Attendance Report (30 menit)

Tulis Pest test baru di `tests/Feature/Reports/AttendanceReportControllerTest.php`:

```php
it('does not show attendance from other companies', function () {
    // 1. Buat attendance untuk company ini
    // 2. Buat attendance untuk company LAIN
    // 3. GET /reports/attendance
    // 4. Assert bahwa hanya attendance company ini yang tampil
});
```

### Latihan 4: Analisis Security Middleware (20 menit)

1. Buka `DetectAttack.php`
2. Jawab pertanyaan:
   - Apa yang terjadi jika request mengandung `' OR 1=1 --` di query parameter?
   - Berapa kali serangan critical yang dibutuhkan untuk auto-block IP?
   - Kenapa middleware ini **tidak** langsung memblok request?
   - Apa risiko dari desain "detect-only, no blocking"?

### Latihan 5: Simulasi Dual Mode (30 menit)

1. Set `SINGLE_TENANT_MODE=true` di `.env`
2. Coba akses `/register` (public registration) — apa yang terjadi?
3. Coba akses `/superadmin` — apa yang terjadi?
4. Buka Dashboard — verify tenant context berasal dari `locked_company_id()` bukan `$user->company`
5. Set kembali `SINGLE_TENANT_MODE=false`

### Latihan 6: Export Attendance Report PDF (20 menit)

1. Buka halaman Laporan Kehadiran
2. Set filter tanggal (bulan ini)
3. Klik "Export PDF"
4. Buka file PDF yang di-download
5. Trace kode dari `AttendanceReportController@export` sampai ke `reports.attendance.pdf` view

---

## Referensi File Penting

| Konsep | File | Line |
|--------|------|------|
| Middleware registration | `bootstrap/app.php` | 26-55 |
| Tenant context | `app/Http/Middleware/SetTenant.php` | 25-43 |
| Attack detection | `app/Http/Middleware/DetectAttack.php` | 14-86 |
| IP blocking | `app/Http/Middleware/CheckBlockedIp.php` | 14-51 |
| Single-tenant config | `config/tenant.php` | 1-58 |
| Helper functions | `app/helpers.php` | 1-55 |
| Dashboard controller | `app/Http/Controllers/DashboardController.php` | 1-236 |
| Analytics service | `app/Services/DashboardAnalyticsService.php` | 1-294 |
| Attendance model | `app/Models/Attendance.php` | 1-365 |
| Attendance report | `app/Http/Controllers/Reports/AttendanceReportController.php` | 1-217 |
| Dashboard view | `resources/views/dashboard.blade.php` | 1-620 |
| Company model (timezone) | `app/Models/Company.php` | 153-191 |
| Audit trait | `app/Traits/LogsActivityTrait.php` | 1-53 |
