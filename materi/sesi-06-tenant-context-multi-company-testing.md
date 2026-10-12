# Sesi 6 (Alt): Tenant Context Handling — Multi-Company Scenario Testing

> **Durasi**: 2-3 jam
> **Tanggal**: 16 April 2026 (Minggu 2)
> **Prasyarat**: GajiPro running, paham login flow & API (Sesi 4 & 5)
> **Tujuan**: Menguasai tenant context handling secara mendalam, menguji isolasi data multi-tenant pada semua layer, dan menulis test tenant isolation yang mengcover seluruh attack surface
> **File Pendamping**: `sesi-06-saas-billing-xendit.md` (materi SaaS Billing dengan Xendit)

---

## Daftar Isi

1. [Kenapa Multi-Tenant Testing Itu Kritis](#1-kenapa-multi-tenant-testing-itu-kritis)
2. [Arsitektur Tenant Context di GajiPro](#2-arsitektur-tenant-context-di-gajipro)
3. [Lifecycle Request: Dari Login sampai Query](#3-lifecycle-request-dari-login-sampai-query)
4. [Setup: Membuat 2 Perusahaan untuk Testing](#4-setup-membuat-2-perusahaan-untuk-testing)
5. [Scenario 1: Data Isolation — Karyawan](#5-scenario-1-data-isolation--karyawan)
6. [Scenario 2: Data Isolation — Via API Sanctum](#6-scenario-2-data-isolation--via-api-sanctum)
7. [Scenario 3: Cross-Tenant URL Manipulation (IDOR)](#7-scenario-3-cross-tenant-url-manipulation-idor)
8. [Scenario 4: Form Request Validation Scope](#8-scenario-4-form-request-validation-scope)
9. [Scenario 5: Role & Permission per Tenant (Spatie Team Mode)](#9-scenario-5-role--permission-per-tenant-spatie-team-mode)
10. [Scenario 6: Approval Workflow Isolation](#10-scenario-6-approval-workflow-isolation)
11. [Scenario 7: Payroll & Financial Data Isolation](#11-scenario-7-payroll--financial-data-isolation)
12. [Scenario 8: Tenant Context di Queued Jobs](#12-scenario-8-tenant-context-di-queued-jobs)
13. [Scenario 9: Activity Log Tenant Context](#13-scenario-9-activity-log-tenant-context)
14. [Menulis Test Tenant Isolation (Pest)](#14-menulis-test-tenant-isolation-pest)
15. [Common Vulnerability Patterns](#15-common-vulnerability-patterns)
16. [Superadmin Cross-Tenant View](#16-superadmin-cross-tenant-view)
17. [Database-Level Verification](#17-database-level-verification)
18. [Debugging & Incident Response](#18-debugging--incident-response)
19. [Checklist Tenant Security](#19-checklist-tenant-security)
20. [Latihan Praktik](#20-latihan-praktik)

---

## 1. Kenapa Multi-Tenant Testing Itu Kritis

### Real-World Horror Stories

```
Case 1: SaaS Payroll Bocor
   → Company A bisa lihat slip gaji Company B
   → 10.000 karyawan data gajinya terekspos
   → Denda GDPR: 20 juta EUR
   → Produk shutdown permanent

Case 2: CRM Data Leak
   → Bug di filter query, lupa WHERE company_id
   → Customer list Company A muncul di Company B
   → Kehilangan kepercayaan, customer churn 40%

Case 3: HR System Breach
   → Admin Company A bisa edit karyawan Company B
   → Data pribadi (KTP, NPWP, rekening) bocor
   → Class action lawsuit dari karyawan
```

### Di GajiPro, Data yang HARUS Ter-isolasi

| Kategori | Data Sensitif | Level Risiko |
|----------|--------------|--------------|
| **Karyawan** | Nama, KTP, NPWP, alamat, rekening bank | TINGGI |
| **Gaji** | Slip gaji, komponen gaji, potongan | SANGAT TINGGI |
| **Pajak** | PPh 21, bukti potong, SPT | SANGAT TINGGI |
| **BPJS** | Nomor BPJS, iuran, klaim | TINGGI |
| **Kehadiran** | Jam masuk/keluar, GPS location, foto wajah | TINGGI |
| **Cuti** | Saldo cuti, alasan cuti, riwayat | SEDANG |
| **Keuangan** | Reimbursement, THR, bonus | TINGGI |
| **Dokumen** | KTP scan, kontrak, surat peringatan | TINGGI |
| **Billing** | Invoice, payment, subscription | TINGGI |

**Semua data ini WAJIB ter-scope ke `company_id`. Tidak ada pengecualian.**

### Filosofi Keamanan di GajiPro

```
Defense in Depth (Pertahanan Berlapis):

Layer 1: Middleware (SetTenant)
  └─ Set app('tenant') dan setPermissionsTeamId() otomatis

Layer 2: Controller
  └─ Selalu query where('company_id', $tenant->id)

Layer 3: Form Request
  └─ Validasi exists/unique di-scope ke tenant

Layer 4: Model (optional)
  └─ Global scope / trait untuk auto-filter

Layer 5: Database
  └─ Foreign keys + index pada company_id
```

Jika satu layer gagal, layer lain masih melindungi. Tapi **tujuan kita: semua layer harus tight**.

---

## 2. Arsitektur Tenant Context di GajiPro

### File-File Kunci

| File | Peran |
|------|-------|
| `app/Http/Middleware/SetTenant.php` | Set `app('tenant')` dan team context |
| `app/Models/Company.php` | Model tenant utama |
| `app/Models/User.php` | User punya `company_id` |
| `config/permission.php` | Spatie team mode konfigurasi |
| `bootstrap/app.php` | Register middleware di web group |

### SetTenant Middleware (Actual Code)

```php
// app/Http/Middleware/SetTenant.php

public function handle(Request $request, Closure $next): Response
{
    $user = $request->user();

    if (! $user) {
        return $next($request);
    }

    // Super admin (no company) tidak punya tenant context
    if ($user->company_id === null) {
        return $next($request);
    }

    $company = $user->company;

    if (! $company) {
        return $next($request);
    }

    // Cek subscription masih aktif
    if (! $company->isSubscriptionActive()) {
        return redirect('/subscription-expired');
    }

    // Set tenant di app container (bisa di-resolve via app('tenant'))
    app()->instance('tenant', $company);

    // Set context untuk Spatie Permission (role per company)
    setPermissionsTeamId($company->id);

    return $next($request);
}
```

### Cara Akses Tenant di Controller

```php
// Cara 1: Via facade/helper
$tenant = app('tenant');

// Cara 2: Via user yang login
$tenant = auth()->user()->company;

// Cara 3: Via request
$tenant = $request->user()->company;
```

**Konvensi GajiPro: Selalu pakai `app('tenant')` di admin controllers**, dan `$request->user()->company` di API controllers.

### Spatie Team Mode

```php
// config/permission.php
return [
    // ...
    'teams' => true,
    'team_foreign_key' => 'company_id',
];
```

Dengan ini, role `admin` di Company A berbeda dengan role `admin` di Company B. Lihat Section 9 untuk detail.

---

## 3. Lifecycle Request: Dari Login sampai Query

### Flow Lengkap (Web Admin)

```
1. User login: POST /login
   └─ Email + password diverifikasi
   └─ Session dibuat dengan user_id
   └─ Redirect ke /dashboard

2. User akses /employees
   └─ Request masuk melalui middleware stack:
      ├─ DetectAttack  (security)
      ├─ CheckBlockedIp (security)
      ├─ web middleware (session)
      ├─ Authenticate (auth:web)
      └─ SetTenant ← KUNCI!
         ├─ Resolve user->company
         ├─ Cek subscription aktif
         ├─ app()->instance('tenant', $company)
         └─ setPermissionsTeamId($company->id)

3. Controller dipanggil: EmployeeController@index
   └─ $tenant = app('tenant');
   └─ Employee::where('company_id', $tenant->id)->paginate(15)
   └─ Return view dengan hanya data tenant ini

4. View di-render dengan data yang sudah ter-filter
```

### Flow Lengkap (API Mobile)

```
1. Mobile login: POST /api/v1/auth/login
   └─ Return access token (Sanctum)
   └─ Token attached ke user yang login

2. Mobile request: GET /api/v1/dashboard
   Header: Authorization: Bearer {token}
   └─ Middleware stack:
      ├─ DetectAttack
      ├─ CheckBlockedIp
      ├─ LogRateLimitHit
      └─ auth:sanctum ← resolve user dari token

3. API Controller: DashboardController@index
   └─ $user = $request->user();
   └─ $company = $user->company;
   └─ Query dengan company_id filter

4. JSON response dengan data tenant ini
```

**Perbedaan Penting**: API menggunakan `auth:sanctum` dan resolve tenant dari `$user->company` langsung (tidak via `SetTenant` middleware pada group API default). Ini by design — stateless, tidak perlu redirect.

### Verifikasi Runtime

```php
// Di tinker (atau boost tinker tool):
use App\Models\User;

$user = User::where('email', 'admin@demo.gajipro.com')->first();
auth()->login($user);

// Simulasi middleware set tenant
app()->instance('tenant', $user->company);
setPermissionsTeamId($user->company_id);

// Verify
dump([
    'tenant_id' => app('tenant')->id,
    'tenant_name' => app('tenant')->name,
    'team_id' => getPermissionsTeamId(),
    'has_admin_role' => $user->hasRole('admin'),
]);
```

---

## 4. Setup: Membuat 2 Perusahaan untuk Testing

### Cara 1: Register via Web (Paling Realistic)

```
1. Buka http://localhost:8000/register
2. Daftarkan Perusahaan Alpha:
   - Nama: PT Alpha Technology
   - Email Admin: admin@alpha.com
   - Password: password
3. Logout
4. Daftarkan Perusahaan Beta:
   - Nama: PT Beta Digital
   - Email Admin: admin@beta.com
   - Password: password
```

### Cara 2: Seed via Artisan Tinker

```bash
php artisan tinker
```

```php
use App\Models\Company;
use App\Models\User;
use App\Models\SubscriptionPlan;
use App\Models\Subscription;

$plan = SubscriptionPlan::where('slug', 'starter')->first();

// --- Company Alpha ---
$alpha = Company::create([
    'name' => 'PT Alpha Technology',
    'slug' => 'pt-alpha-technology',
    'email' => 'info@alpha.com',
    'is_active' => true,
    'subscription_plan_id' => $plan->id,
    'timezone' => 'Asia/Jakarta',
]);

Subscription::create([
    'company_id' => $alpha->id,
    'subscription_plan_id' => $plan->id,
    'status' => 'active',
    'billing_cycle' => 'yearly',
    'started_at' => now(),
    'ends_at' => now()->addYear(),
]);

setPermissionsTeamId($alpha->id);
$adminAlpha = User::factory()->create([
    'name' => 'Admin Alpha',
    'email' => 'admin@alpha.com',
    'password' => bcrypt('password'),
    'company_id' => $alpha->id,
]);
$adminAlpha->assignRole('admin');

// --- Company Beta ---
$beta = Company::create([
    'name' => 'PT Beta Digital',
    'slug' => 'pt-beta-digital',
    'email' => 'info@beta.com',
    'is_active' => true,
    'subscription_plan_id' => $plan->id,
    'timezone' => 'Asia/Jakarta',
]);

Subscription::create([
    'company_id' => $beta->id,
    'subscription_plan_id' => $plan->id,
    'status' => 'active',
    'billing_cycle' => 'yearly',
    'started_at' => now(),
    'ends_at' => now()->addYear(),
]);

setPermissionsTeamId($beta->id);
$adminBeta = User::factory()->create([
    'name' => 'Admin Beta',
    'email' => 'admin@beta.com',
    'password' => bcrypt('password'),
    'company_id' => $beta->id,
]);
$adminBeta->assignRole('admin');
```

### Cara 3: Gunakan Existing Demo Data

Sudah ada di `DatabaseSeeder.php`:
- **Company 1**: PT Demo GajiPro (`admin@demo.gajipro.com` / `password`)
- Register baru via `/register` untuk company kedua

### Verifikasi Setup

```sql
-- Cek companies
SELECT id, name, slug, is_active FROM companies;

-- Cek users per company
SELECT u.id, u.name, u.email, u.company_id, c.name AS company
FROM users u
LEFT JOIN companies c ON u.company_id = c.id
ORDER BY u.company_id;

-- Cek subscription aktif
SELECT c.name, s.status, s.billing_cycle, s.ends_at
FROM companies c
JOIN subscriptions s ON s.company_id = c.id
WHERE s.status = 'active';
```

---

## 5. Scenario 1: Data Isolation — Karyawan

### Test Manual

**Step 1: Tambah karyawan di masing-masing company**

Login sebagai **Admin Alpha** → Tambah karyawan:
- Nama: `Budi Alpha` (otomatis company_id = Alpha)

Login sebagai **Admin Beta** → Tambah karyawan:
- Nama: `Siti Beta` (otomatis company_id = Beta)

**Step 2: Verifikasi isolasi**

Login Alpha → List Karyawan:
- Harus terlihat: `Budi Alpha`
- Tidak boleh terlihat: `Siti Beta`

Login Beta → List Karyawan:
- Harus terlihat: `Siti Beta`
- Tidak boleh terlihat: `Budi Alpha`

**Step 3: Verifikasi di database**

```sql
SELECT e.id,
       CONCAT(e.first_name, ' ', e.last_name) AS full_name,
       e.company_id,
       c.name AS company
FROM employees e
JOIN companies c ON e.company_id = c.id
ORDER BY e.company_id;
```

### Bagaimana Controller Menjaga Isolasi?

```php
// app/Http/Controllers/EmployeeController.php

public function index(Request $request): View
{
    $tenant = app('tenant');  // ← Company dari user yang login

    $employees = Employee::with(['department', 'position'])
        ->where('company_id', $tenant->id)  // ← FILTER!
        ->when($request->search, fn ($q, $search) =>
            $q->where('full_name', 'like', "%{$search}%")
        )
        ->latest()
        ->paginate(15);

    return view('employees.index', compact('employees'));
}
```

**Inti**: tanpa `where('company_id', $tenant->id)`, query akan mengembalikan SEMUA karyawan dari SEMUA company.

---

## 6. Scenario 2: Data Isolation — Via API Sanctum

### Setup Token untuk Kedua Admin

```bash
# Login Alpha
ALPHA_TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@alpha.com","password":"password","device_name":"test"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# Login Beta
BETA_TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@beta.com","password":"password","device_name":"test"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

echo "Alpha token: $ALPHA_TOKEN"
echo "Beta token:  $BETA_TOKEN"
```

### Request ke API Dashboard

```bash
# Alpha minta dashboard → hanya data Alpha
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $ALPHA_TOKEN" | python3 -m json.tool

# Beta minta dashboard → hanya data Beta
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $BETA_TOKEN" | python3 -m json.tool
```

**Verify**: Total karyawan pada response HARUS BEDA (kecuali kebetulan sama jumlahnya).

### Kenapa Token Tidak Bisa Akses Data Company Lain?

```php
// Pattern di API Controller:
public function index(Request $request): JsonResponse
{
    $user = $request->user();          // User dari Bearer token
    $company = $user->company;          // Company milik user
    $employee = $user->employee;        // Employee record user

    // Query SELALU pakai company milik user:
    $attendances = Attendance::where('company_id', $company->id)
        ->where('employee_id', $employee->id)
        ->get();

    // Token Alpha → company_id = Alpha → hanya data Alpha
    // Token Beta  → company_id = Beta  → hanya data Beta
}
```

### Cara Mobile Flutter Tidak Bisa "Trick" Server

Flutter app TIDAK pernah mengirim `company_id` ke server. Server selalu resolve dari `$request->user()->company_id`. Jika mobile app dihack dan kirim payload `company_id: 999`, server IGNORE karena pakai resolved company.

---

## 7. Scenario 3: Cross-Tenant URL Manipulation (IDOR)

IDOR = Insecure Direct Object Reference. Ini serangan **paling umum** di sistem multi-tenant.

### Skenario Serangan

```
1. Admin Alpha login
2. Alpha menebak bahwa Employee ID 5 adalah milik Beta
3. Alpha coba akses: GET /employees/5
```

### Yang Harus Terjadi

```
HTTP 404 Not Found
```

**Bukan `403 Forbidden`!** Kenapa?

### Kenapa 404, Bukan 403?

```php
public function show(Employee $employee): View
{
    $tenant = app('tenant');

    // PENTING: Return 404, bukan 403!
    if ($employee->company_id !== $tenant->id) {
        abort(404);
    }

    return view('employees.show', compact('employee'));
}
```

**Security principle — Information Disclosure**:
- `404` = "Resource tidak ada" (dari perspektif Alpha, memang tidak ada)
- `403` = "Kamu tidak boleh akses" — ini **BOCORKAN** info bahwa resource EXISTS

Jika kita pakai `403`, attacker bisa melakukan **enumeration attack**:
```
GET /employees/1 → 403 (exists di company lain)
GET /employees/2 → 200 (exists di company-ku)
GET /employees/3 → 404 (tidak ada)
GET /employees/4 → 403 (exists di company lain)
```

Dengan `404` untuk semua yang bukan milik tenant, attacker tidak bisa bedakan "tidak ada" vs "ada tapi bukan milikku".

### Test di cURL (API)

```bash
# Misalnya ID Employee milik Beta = 10
BETA_EMPLOYEE_ID=10

# Coba akses pakai token Alpha
curl -si http://localhost:8000/api/v1/employees/$BETA_EMPLOYEE_ID \
  -H "Authorization: Bearer $ALPHA_TOKEN"

# Expected: HTTP/1.1 404 Not Found
```

### Variasi Attack yang Harus Dites

| Method | Path | Expected |
|--------|------|----------|
| GET | `/employees/{other-tenant-id}` | 404 |
| PUT | `/employees/{other-tenant-id}` | 404 |
| DELETE | `/employees/{other-tenant-id}` | 404 |
| GET | `/api/v1/payslips/{other-tenant-id}` | 404 |
| POST | `/attendance/{other-tenant-employee-id}/approve` | 404 |

---

## 8. Scenario 4: Form Request Validation Scope

### Masalah: Unique Validation Tanpa Scope

```php
// SALAH — Tanpa scope company_id
'email' => ['required', 'email', 'unique:employees,email']
```

Artinya: email harus unique di SELURUH database. Alpha tidak bisa punya email yang sama dengan Beta! Ini BUG.

```php
// BENAR — Dengan scope company_id
use Illuminate\Validation\Rule;

'email' => [
    'required',
    'email',
    Rule::unique('employees', 'email')
        ->where('company_id', app('tenant')->id),
],
```

Artinya: email harus unique DALAM company yang sama. Alpha & Beta BOLEH punya email yang sama.

### Masalah: Exists Validation Tanpa Scope

```php
// SALAH — Bisa pilih department milik company lain!
'department_id' => ['required', 'exists:departments,id']

// BENAR — Hanya department milik tenant ini
'department_id' => [
    'required',
    Rule::exists('departments', 'id')
        ->where('company_id', app('tenant')->id),
],
```

### Test Skenario Manual

1. Company Alpha punya Department "Engineering" (id=1)
2. Company Beta punya Department "Marketing" (id=5)
3. Admin Alpha coba buat karyawan dengan `department_id=5` (milik Beta)

**Yang harus terjadi:** Validation error "Department tidak ditemukan"

### Contoh Form Request Lengkap

```php
// app/Http/Requests/StoreEmployeeRequest.php

class StoreEmployeeRequest extends FormRequest
{
    public function rules(): array
    {
        $tenant = app('tenant');

        return [
            'full_name' => ['required', 'string', 'max:255'],

            // Unique dalam company yang sama
            'email' => [
                'required',
                'email',
                Rule::unique('employees', 'email')
                    ->where('company_id', $tenant->id),
            ],

            // Hanya department milik company ini
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')
                    ->where('company_id', $tenant->id),
            ],

            // Hanya position milik company ini
            'position_id' => [
                'required',
                Rule::exists('positions', 'id')
                    ->where('company_id', $tenant->id),
            ],

            // Hanya work schedule milik company ini
            'work_schedule_id' => [
                'nullable',
                Rule::exists('work_schedules', 'id')
                    ->where('company_id', $tenant->id),
            ],
        ];
    }
}
```

### Audit Form Request Existing

Cek apakah semua FormRequest di `app/Http/Requests/` sudah tenant-scoped:

```bash
# Cari FormRequest yang pakai exists/unique tanpa scope
grep -rn "exists:\|unique:" app/Http/Requests/ | grep -v "company_id"
```

Hasil query di atas adalah **kandidat bug** yang perlu direview.

---

## 9. Scenario 5: Role & Permission per Tenant (Spatie Team Mode)

### Apa Itu Team Mode?

GajiPro pakai Spatie Permission dengan **team mode aktif**:

```php
// config/permission.php
'teams' => true,
'team_foreign_key' => 'company_id',
```

### Artinya

```
Company Alpha (id=1):
├── Role: admin          (team_id=1)
├── Role: hr-manager     (team_id=1)
├── Role: payroll-manager (team_id=1)
└── Role: employee       (team_id=1)

Company Beta (id=2):
├── Role: admin          (team_id=2)
├── Role: hr-manager     (team_id=2)
├── Role: payroll-manager (team_id=2)
└── Role: employee       (team_id=2)
```

**Admin di Alpha ≠ Admin di Beta!** Meskipun nama role sama, mereka TERPISAH.

### Bagaimana SetTenant Middleware Handle Ini?

```php
// SetTenant middleware:
setPermissionsTeamId($company->id);

// Setelah ini, $user->hasRole('admin') hanya cek role
// di team/company user tersebut
```

Kalau tidak ada `setPermissionsTeamId()`, Spatie akan cek di semua team → potensi false positive.

### Test: User Bisa Punya Role Beda di Company Beda

Skenario edge case: User X adalah admin di Alpha, tapi employee biasa di Beta.

```php
// Pakai tinker
use App\Models\User;

$userX = User::find(99); // misalnya

// Context Alpha
setPermissionsTeamId(1);
dump($userX->hasRole('admin')); // true

// Context Beta
setPermissionsTeamId(2);
dump($userX->hasRole('admin')); // false (meskipun user sama)
```

Di GajiPro, user biasanya hanya punya 1 company (`company_id` single). Tapi pattern ini memungkinkan multi-company user di masa depan.

### Existing Test Reference

Lihat test lengkapnya: `tests/Feature/Permission/CompanyRoleIsolationTest.php`

Snippet penting:

```php
it('can have same role name in different companies', function () {
    setPermissionsTeamId($this->companyA->id);
    $roleA = Role::create(['name' => 'admin', 'guard_name' => 'web']);

    setPermissionsTeamId($this->companyB->id);
    $roleB = Role::create(['name' => 'admin', 'guard_name' => 'web']);

    expect($roleA->id)->not->toBe($roleB->id);
    expect($roleA->company_id)->toBe($this->companyA->id);
    expect($roleB->company_id)->toBe($this->companyB->id);
});
```

### Verifikasi di Database

```sql
SELECT r.id, r.name, r.team_id AS company_id, c.name AS company
FROM roles r
LEFT JOIN companies c ON r.team_id = c.id
ORDER BY r.team_id, r.name;

-- Pastikan setiap role punya team_id yang sesuai company-nya.
-- Role dengan team_id = NULL = role global (biasanya untuk super-admin).
```

---

## 10. Scenario 6: Approval Workflow Isolation

### Multi-Tenant Approval Flow

```
Company Alpha:
  Karyawan Alpha → Ajukan cuti → Approval oleh HR Alpha → Approved

Company Beta:
  Karyawan Beta → Ajukan cuti → Approval oleh HR Beta → Approved

TIDAK BOLEH:
  Karyawan Alpha → HR Beta approve
  HR Alpha → Lihat pending cuti Beta
```

### Test Skenario

1. Karyawan Alpha ajukan cuti
2. Login sebagai HR Beta → Cek pending approvals
3. Cuti Alpha TIDAK boleh muncul di list HR Beta

### Di Kode

```php
// LeaveRequestController - approval list
public function index(Request $request): View
{
    $tenant = app('tenant');

    $leaveRequests = LeaveRequest::with(['employee', 'leaveType'])
        ->where('company_id', $tenant->id)  // ← Isolasi!
        ->when($request->status === 'pending', fn ($q) =>
            $q->where('status', 'pending')
        )
        ->latest()
        ->paginate(15);

    return view('leave-requests.index', compact('leaveRequests'));
}
```

### Approval via API

```php
public function approveLeave(int $id): JsonResponse
{
    $user = $request->user();
    $company = $user->company;

    $leave = LeaveRequest::where('id', $id)
        ->where('company_id', $company->id)  // ← KRITIS!
        ->firstOrFail();  // 404 jika bukan milik company

    // Approve logic...
}
```

### Extra Layer: ApprovalWorkflow Configuration

GajiPro punya `approval_workflows` table untuk define multi-step approval. Pastikan:

```sql
SELECT aw.id, aw.name, aw.company_id, aw.module
FROM approval_workflows aw
ORDER BY aw.company_id;
```

Workflow harus tercatat per company_id. Jika ada workflow dengan `company_id = NULL` di tabel ini, itu masalah.

---

## 11. Scenario 7: Payroll & Financial Data Isolation

### Ini Data Paling Sensitif!

```
Slip gaji Alpha: Rp 25.000.000/bulan  → Hanya Alpha
Slip gaji Beta:  Rp 15.000.000/bulan  → Hanya Beta
```

### Test: API Payslip Isolation

```bash
# Token Alpha
curl -s http://localhost:8000/api/v1/payslips \
  -H "Authorization: Bearer $ALPHA_TOKEN" | python3 -m json.tool
# Hanya slip gaji karyawan Alpha

# Token Beta
curl -s http://localhost:8000/api/v1/payslips \
  -H "Authorization: Bearer $BETA_TOKEN" | python3 -m json.tool
# Hanya slip gaji karyawan Beta

# Coba akses payslip spesifik milik Beta pakai token Alpha
curl -si http://localhost:8000/api/v1/payslips/999 \
  -H "Authorization: Bearer $ALPHA_TOKEN"
# 404 Not Found (bukan 403!)
```

### Tax Data Isolation

```php
// Tax Form 1721-A1
$taxForms = TaxForm1721A1::where('company_id', $company->id)
    ->where('employee_id', $employee->id)
    ->get();

// SPT 1721
$spt = Spt1721::where('company_id', $company->id)->get();

// BPJS Settings
$bpjsTk = BpjsTkSetting::where('company_id', $company->id)->first();
$bpjsKes = BpjsKesSetting::where('company_id', $company->id)->first();

// PPh 21 Settings
$pph21 = Pph21Setting::where('company_id', $company->id)->first();
```

### Test: Payroll Processing Isolation

Saat Admin Alpha run `process payroll`, pastikan:

```sql
-- Cek payroll hanya process employee milik Alpha
SELECT p.id, p.company_id, pi.employee_id, e.company_id AS emp_company
FROM payrolls p
JOIN payroll_items pi ON pi.payroll_id = p.id
JOIN employees e ON e.id = pi.employee_id
WHERE p.company_id != e.company_id;

-- Expected: 0 rows! (payroll company_id = employee company_id)
```

### Test: Billing Data Isolation

```bash
# Tokens dari section 6
# Alpha akses billing history
curl -s http://localhost:8000/settings/billing/history \
  -H "Cookie: session-cookie-alpha"

# Beta akses billing history
curl -s http://localhost:8000/settings/billing/history \
  -H "Cookie: session-cookie-beta"

# Expected: Alpha tidak lihat invoice Beta, dan sebaliknya
```

Cek di database:

```sql
SELECT i.id, i.invoice_number, i.company_id, c.name, i.total
FROM invoices i
JOIN companies c ON i.company_id = c.id
ORDER BY i.company_id;
```

---

## 12. Scenario 8: Tenant Context di Queued Jobs

### Problem: Queue Job Tidak Punya Request Context

Ketika kita `dispatch(new ProcessPayrollJob(...))`, job dijalankan di worker terpisah. Tidak ada `app('tenant')` karena tidak ada HTTP request.

### Salah: Assume Tenant di Job

```php
// SALAH
class ProcessPayrollJob implements ShouldQueue
{
    public function handle(): void
    {
        $tenant = app('tenant'); // NULL! Karena worker tidak punya request context
        $employees = Employee::where('company_id', $tenant->id)->get();
    }
}
```

### Benar: Pass company_id Eksplisit

```php
class ProcessPayrollJob implements ShouldQueue
{
    public function __construct(
        public int $companyId,
        public int $payrollId,
    ) {}

    public function handle(): void
    {
        // Set team context manual (jika pakai Spatie permission)
        setPermissionsTeamId($this->companyId);

        // Query dengan company_id eksplisit
        $employees = Employee::where('company_id', $this->companyId)->get();
        $payroll = Payroll::where('company_id', $this->companyId)
            ->findOrFail($this->payrollId);

        // Process...
    }
}

// Dispatch dari controller:
dispatch(new ProcessPayrollJob(
    companyId: app('tenant')->id,
    payrollId: $payroll->id,
));
```

### Test Queue Job Isolation

```php
use App\Jobs\ProcessPayrollJob;
use Illuminate\Support\Facades\Queue;

it('only processes payroll for the specified company', function () {
    Queue::fake();

    $alphaCompany = Company::factory()->create();
    $betaCompany = Company::factory()->create();

    $alphaPayroll = Payroll::factory()->create(['company_id' => $alphaCompany->id]);

    dispatch(new ProcessPayrollJob($alphaCompany->id, $alphaPayroll->id));

    Queue::assertPushed(ProcessPayrollJob::class, function ($job) use ($alphaCompany) {
        return $job->companyId === $alphaCompany->id;
    });
});
```

---

## 13. Scenario 9: Activity Log Tenant Context

### GajiPro pakai Spatie ActivityLog + LogsActivityTrait

```php
// app/Traits/LogsActivityTrait.php
// Otomatis log create/update/delete event
// Menangkap company_id dari model atau current tenant
```

### Test: Log Tidak Bocor Antar Tenant

```sql
-- Cek activity log dengan company_id
SELECT al.id, al.log_name, al.description,
       al.subject_type, al.subject_id,
       al.properties->>'$.company_id' AS log_company_id
FROM activity_log al
LIMIT 20;
```

### Audit Trail Viewer harus Tenant-Scoped

```php
// app/Http/Controllers/ActivityLogController.php (pattern)
public function index(): View
{
    $tenant = app('tenant');

    $activities = Activity::query()
        ->where('properties->company_id', $tenant->id)
        ->orderByDesc('created_at')
        ->paginate(50);

    return view('settings.activity-logs.index', compact('activities'));
}
```

Superadmin punya viewer TERPISAH yang bisa lihat semua. Itu by design (lihat Section 16).

---

## 14. Menulis Test Tenant Isolation (Pest)

### Pattern Dasar: Test Isolation

```php
<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Setup 2 companies
    $this->companyAlpha = Company::factory()->create(['name' => 'Alpha']);
    $this->companyBeta = Company::factory()->create(['name' => 'Beta']);

    // Setup users
    $this->adminAlpha = User::factory()->create([
        'company_id' => $this->companyAlpha->id,
    ]);

    setPermissionsTeamId($this->companyAlpha->id);
    $this->adminAlpha->assignRole('admin');

    $this->adminBeta = User::factory()->create([
        'company_id' => $this->companyBeta->id,
    ]);

    setPermissionsTeamId($this->companyBeta->id);
    $this->adminBeta->assignRole('admin');
});

describe('Employee Tenant Isolation', function () {

    it('cannot list employees from another company', function () {
        Employee::factory()->create([
            'company_id' => $this->companyAlpha->id,
            'first_name' => 'Budi',
            'last_name' => 'Alpha',
        ]);
        Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
            'first_name' => 'Siti',
            'last_name' => 'Beta',
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->get(route('employees.index'));

        $response->assertOk()
            ->assertSee('Budi Alpha')
            ->assertDontSee('Siti Beta');
    });

    it('cannot view employee from another company', function () {
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->get(route('employees.show', $empBeta));

        $response->assertNotFound(); // 404, NOT 403!
    });

    it('cannot update employee from another company', function () {
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
            'first_name' => 'Original',
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->put(route('employees.update', $empBeta), [
                'first_name' => 'Hacked',
            ]);

        $response->assertNotFound();

        $this->assertDatabaseMissing('employees', [
            'id' => $empBeta->id,
            'first_name' => 'Hacked',
        ]);
    });

    it('cannot delete employee from another company', function () {
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->delete(route('employees.destroy', $empBeta));

        $response->assertNotFound();

        $this->assertDatabaseHas('employees', [
            'id' => $empBeta->id,
        ]);
    });

    it('cannot create employee with department from another company', function () {
        $deptBeta = Department::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->post(route('employees.store'), [
                'first_name' => 'Hacker',
                'last_name' => 'Test',
                'email' => 'hacker@test.com',
                'department_id' => $deptBeta->id,
            ]);

        $response->assertSessionHasErrors('department_id');
    });
});
```

### Pattern: API Tenant Isolation

```php
describe('API Tenant Isolation', function () {

    it('api token from tenant A cannot access tenant B data', function () {
        $tokenAlpha = $this->adminAlpha->createToken('test')->plainTextToken;

        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->withHeader('Authorization', "Bearer $tokenAlpha")
            ->getJson("/api/v1/employees/{$empBeta->id}");

        $response->assertNotFound();
    });

    it('api dashboard returns only current tenant stats', function () {
        Employee::factory()->count(5)->create(['company_id' => $this->companyAlpha->id]);
        Employee::factory()->count(3)->create(['company_id' => $this->companyBeta->id]);

        $tokenAlpha = $this->adminAlpha->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $tokenAlpha")
            ->getJson('/api/v1/dashboard');

        $response->assertOk();
        // Expect: total_employees = 5 (Alpha only), NOT 8
    });
});
```

### Run Test

```bash
# Create test file
php artisan make:test --pest TenantIsolation/EmployeeIsolationTest

# Run
php artisan test --compact tests/Feature/TenantIsolation/EmployeeIsolationTest.php

# Run with filter
php artisan test --compact --filter="cannot list employees from another"

# Run all tenant isolation tests
php artisan test --compact tests/Feature/TenantIsolation
```

### Template: Test untuk Setiap Resource

Untuk setiap resource (Employee, Department, LeaveRequest, Payroll, dll.), tulis:

```php
describe('{Resource} Tenant Isolation', function () {
    it('cannot list {resources} from another company');
    it('cannot view {resource} from another company');
    it('cannot create {resource} referencing another tenant data');
    it('cannot update {resource} from another company');
    it('cannot delete {resource} from another company');
    it('api cannot fetch {resource} from another tenant');
});
```

---

## 15. Common Vulnerability Patterns

### 1. Lupa WHERE company_id

```php
// VULNERABLE
$employees = Employee::all();

// SAFE
$employees = Employee::where('company_id', $tenant->id)->get();
```

**Pencegahan**: Code review checklist + grep audit.

### 2. Route Model Binding Tanpa Scope

```php
// VULNERABLE — Laravel auto-resolve Employee by ID, tanpa company check
public function show(Employee $employee): View
{
    return view('employees.show', compact('employee'));
    // Employee bisa dari company manapun!
}

// SAFE — Tambah ownership check
public function show(Employee $employee): View
{
    if ($employee->company_id !== app('tenant')->id) {
        abort(404);
    }
    return view('employees.show', compact('employee'));
}
```

### 3. Eager Loading Tanpa Scope

```php
// VULNERABLE — Load semua relasi tanpa filter
$department = Department::with('employees')->find($id);

// SAFE — Scope relasi juga
$tenant = app('tenant');
$department = Department::with(['employees' => function ($q) use ($tenant) {
    $q->where('company_id', $tenant->id);
}])->where('company_id', $tenant->id)->findOrFail($id);
```

### 4. Count/Aggregate Tanpa Scope

```php
// VULNERABLE — Hitung semua karyawan!
$totalEmployees = Employee::count();

// SAFE
$totalEmployees = Employee::where('company_id', $tenant->id)->count();
```

### 5. Search/Filter Tanpa Scope

```php
// VULNERABLE
$employees = Employee::where('full_name', 'like', "%{$search}%")->get();

// SAFE
$employees = Employee::where('company_id', $tenant->id)
    ->where('full_name', 'like', "%{$search}%")
    ->get();
```

### 6. Mass Assignment company_id

```php
// VULNERABLE — User bisa inject company_id lain via form
$employee = Employee::create($request->all());

// SAFE — Set company_id dari tenant, bukan dari request
$employee = Employee::create([
    'company_id' => $tenant->id,  // ← Selalu dari server
    ...$request->validated(),
]);
```

### 7. Global Helper Tanpa Scope

```php
// VULNERABLE
function getEmployeeCount() {
    return Employee::count();
}

// SAFE
function getEmployeeCount(int $companyId) {
    return Employee::where('company_id', $companyId)->count();
}
```

### 8. DB::raw SQL Tanpa Filter

```php
// VULNERABLE
DB::select('SELECT * FROM employees WHERE is_active = 1');

// SAFE
DB::select(
    'SELECT * FROM employees WHERE is_active = 1 AND company_id = ?',
    [app('tenant')->id]
);
```

### 9. File Path Tanpa Tenant Prefix

```php
// VULNERABLE — Semua company share folder
Storage::put("documents/{$filename}", $file);

// SAFE — Per-tenant folder
Storage::put("companies/{$tenant->id}/documents/{$filename}", $file);
```

---

## 16. Superadmin Cross-Tenant View

### Superadmin Bisa Lihat Semua — Ini by Design

```php
// Superadmin Dashboard — NO company_id filter
public function index(): View
{
    $stats = [
        'total_companies' => Company::count(),
        'active_subscriptions' => Subscription::where('status', 'active')->count(),
        'total_revenue' => Payment::where('status', 'success')->sum('amount'),
        'total_employees' => Employee::count(),  // ALL employees
    ];

    return view('superadmin.dashboard', compact('stats'));
}
```

### Tapi Superadmin TIDAK Bisa Modify!

```
Superadmin CompanyController:
- index() OK
- show()  OK
- create(), store(), edit(), update(), destroy() TIDAK ADA

Ini by design:
- Superadmin MONITOR, bukan MANAGE
- Company data hanya bisa diubah oleh admin company tersebut
- Mencegah "God Mode" abuse
```

### Separation of Concerns

```
/superadmin/login  → Login superadmin (user dengan is_superadmin=true)
/superadmin/*      → Route terproteksi EnsureSuperadmin middleware
/login             → Login admin company (user dengan company_id)
/*                 → Route terproteksi auth + SetTenant middleware
```

### Verifikasi

```bash
# Database check
SELECT id, name, email, company_id, is_superadmin
FROM users
WHERE is_superadmin = 1;

# Superadmin harus punya company_id = NULL
```

---

## 17. Database-Level Verification

### Query: Cek Semua Tabel Punya company_id

```sql
SELECT TABLE_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'gajipro'
  AND COLUMN_NAME = 'company_id'
ORDER BY TABLE_NAME;
```

### Query: Tabel yang MUNGKIN Kurang company_id

```sql
-- List tabel yang TIDAK punya company_id (kandidat untuk review)
SELECT DISTINCT t.TABLE_NAME
FROM INFORMATION_SCHEMA.TABLES t
LEFT JOIN INFORMATION_SCHEMA.COLUMNS c
    ON c.TABLE_NAME = t.TABLE_NAME
    AND c.COLUMN_NAME = 'company_id'
    AND c.TABLE_SCHEMA = t.TABLE_SCHEMA
WHERE t.TABLE_SCHEMA = 'gajipro'
  AND t.TABLE_TYPE = 'BASE TABLE'
  AND c.TABLE_NAME IS NULL
ORDER BY t.TABLE_NAME;

-- Expected (tabel system yang tidak butuh company_id):
-- - companies (itu sendiri)
-- - migrations, cache, jobs, failed_jobs
-- - personal_access_tokens (relasi via user)
-- - sessions, password_reset_tokens
-- - activity_log (pakai properties JSON)
-- - subscription_plans (global, shared)
-- - payment_gateway_settings (system-wide)
-- - Spatie permission tables (pakai team_id = company_id)
```

### Query: Detect Data Bocor (Integrity Check)

```sql
-- Cek employee dengan company_id yang tidak match user-nya
SELECT e.id, CONCAT(e.first_name, ' ', e.last_name) AS name,
       e.company_id AS emp_company,
       u.id AS user_id, u.company_id AS user_company
FROM employees e
JOIN users u ON e.user_id = u.id
WHERE e.company_id != u.company_id;
-- Expected: 0 rows!

-- Cek attendance tanpa company_id
SELECT COUNT(*) FROM attendances WHERE company_id IS NULL;
-- Expected: 0

-- Cek payroll_item dengan company mismatch
SELECT p.id, p.company_id, pi.employee_id, e.company_id AS emp_company
FROM payrolls p
JOIN payroll_items pi ON pi.payroll_id = p.id
JOIN employees e ON e.id = pi.employee_id
WHERE p.company_id != e.company_id;
-- Expected: 0 rows!

-- Cek leave_request dengan company mismatch
SELECT lr.id, lr.company_id AS req_company, e.company_id AS emp_company
FROM leave_requests lr
JOIN employees e ON lr.employee_id = e.id
WHERE lr.company_id != e.company_id;
-- Expected: 0 rows!
```

### Query: Data Distribution per Company

```sql
SELECT c.name AS company,
       (SELECT COUNT(*) FROM employees WHERE company_id = c.id) AS employees,
       (SELECT COUNT(*) FROM departments WHERE company_id = c.id) AS departments,
       (SELECT COUNT(*) FROM attendances WHERE company_id = c.id) AS attendances,
       (SELECT COUNT(*) FROM leave_requests WHERE company_id = c.id) AS leaves,
       (SELECT COUNT(*) FROM payrolls WHERE company_id = c.id) AS payrolls
FROM companies c
ORDER BY c.id;
```

### Query: Foreign Key Integrity

```sql
-- Orphan employees (employee tanpa company)
SELECT e.id, CONCAT(e.first_name, ' ', e.last_name) AS name, e.company_id
FROM employees e
LEFT JOIN companies c ON e.company_id = c.id
WHERE c.id IS NULL;
-- Expected: 0 rows!
```

### Cara Otomatis dengan Boost

Gunakan tool `database-schema` dari Laravel Boost MCP untuk audit cepat:

```
"Dengan laravel-boost database-schema tool, cek semua tabel
 dan list mana yang punya kolom company_id, mana yang tidak."
```

---

## 18. Debugging & Incident Response

### Debugging Tenant Context di Runtime

#### Check 1: Apakah Tenant Sudah Ter-set?

Tambah log sementara di controller:

```php
public function index(Request $request): View
{
    \Log::debug('Tenant context check', [
        'user_id' => auth()->id(),
        'user_company_id' => auth()->user()?->company_id,
        'app_tenant_id' => app()->bound('tenant') ? app('tenant')->id : null,
        'permission_team' => getPermissionsTeamId(),
    ]);

    // ... rest of controller
}
```

Lihat hasilnya di `storage/logs/laravel.log`:

```bash
tail -f storage/logs/laravel.log | grep "Tenant context check"
```

#### Check 2: Apakah Query Ter-filter?

Aktifkan query log temporarily:

```php
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

$employees = Employee::where('company_id', $tenant->id)->get();

dd(DB::getQueryLog());
// Cek: apakah SQL yang dijalankan ada "where company_id = ?"
```

### Incident Response: "Data Leak Suspected"

Jika ada laporan "Company A bisa lihat data Company B":

**Step 1: Konfirmasi dengan user**
- Minta screenshot/URL
- Waktu kejadian
- User mana yang lihat data siapa

**Step 2: Cek activity log**

```sql
SELECT al.causer_id, al.subject_type, al.subject_id,
       al.properties, al.created_at
FROM activity_log al
WHERE al.causer_id = {suspected_user_id}
  AND al.created_at BETWEEN '{time_start}' AND '{time_end}'
ORDER BY al.created_at DESC;
```

**Step 3: Cek security log**

```sql
SELECT *
FROM security_logs
WHERE user_id = {suspected_user_id}
  AND created_at BETWEEN '{time_start}' AND '{time_end}';
```

**Step 4: Audit query pattern**

Cari controller yang handle URL yang dilaporkan. Audit apakah query-nya pakai `where('company_id', ...)`.

**Step 5: Patch & Test**

Tambah filter `company_id`, tulis test tenant isolation, deploy.

**Step 6: Notifikasi**

- Notifikasi internal team
- Notifikasi affected customer (jika confirmed)
- Post-mortem report

### Log Pattern untuk Tenant Breach

Tambahkan global log di `SetTenant` middleware untuk audit:

```php
// SetTenant.php
\Log::info('Tenant resolved', [
    'user_id' => $user->id,
    'company_id' => $company->id,
    'route' => $request->route()?->getName(),
    'method' => $request->method(),
    'path' => $request->path(),
]);
```

Ini membantu trace "user X akses route Y dengan tenant Z" saat investigasi.

---

## 19. Checklist Tenant Security

### Per-Feature Checklist

Setiap kali develop fitur baru, gunakan checklist ini:

#### Controller Layer
- [ ] `index()` → Query pakai `where('company_id', $tenant->id)`
- [ ] `show()` → Cek `$model->company_id !== $tenant->id` → `abort(404)`
- [ ] `store()` → Set `company_id` dari `$tenant->id`, BUKAN dari request
- [ ] `update()` → Cek ownership sebelum update
- [ ] `destroy()` → Cek ownership sebelum delete
- [ ] Custom actions (approve, reject, process) → Cek ownership

#### Form Request Layer
- [ ] `unique` rules include `->where('company_id', ...)`
- [ ] `exists` rules include `->where('company_id', ...)`
- [ ] Relational IDs (department_id, position_id) validated with company scope

#### API Controller Layer
- [ ] Gunakan `$request->user()->company` untuk resolve tenant
- [ ] Return 404 (bukan 403) untuk resource milik tenant lain
- [ ] Pagination tetap di-scope ke tenant
- [ ] API Resource tidak expose `company_id` dari resource lain

#### Queued Jobs
- [ ] Pass `company_id` sebagai parameter ke constructor
- [ ] Panggil `setPermissionsTeamId($companyId)` di awal `handle()`
- [ ] Semua query di job pakai `where('company_id', $this->companyId)`

#### Testing Layer
- [ ] Test: User A tidak bisa list data User B
- [ ] Test: User A tidak bisa view/edit/delete data User B
- [ ] Test: Validation gagal jika reference ID milik tenant lain
- [ ] Test: Count/aggregate hanya menghitung data tenant sendiri
- [ ] Test: API dengan token A tidak bisa akses resource B
- [ ] Test: Queue job tenant isolation

#### Database Layer
- [ ] Tabel baru HARUS punya `company_id` (kecuali system tables)
- [ ] Foreign key ke `companies` table dengan `cascadeOnDelete`
- [ ] Index pada `company_id` untuk performance
- [ ] Composite unique constraints include `company_id`

#### File Storage Layer
- [ ] Path include `companies/{company_id}/` prefix
- [ ] File access check `company_id` sebelum serve
- [ ] Signed URL include tenant validation

---

## 20. Latihan Praktik

### Latihan 1: Setup 2 Companies & Verify Isolation (20 menit)

1. Register 2 perusahaan baru via `/register`:
   - `PT Latihan Alpha` (admin: `alpha@test.com`)
   - `PT Latihan Beta` (admin: `beta@test.com`)
2. Login sebagai Alpha → Tambah 3 karyawan dengan nama unik
3. Login sebagai Beta → Tambah 2 karyawan dengan nama unik
4. **Verifikasi**: Login Alpha → Pastikan HANYA 3 karyawan Alpha muncul
5. **Verifikasi**: Login Beta → Pastikan HANYA 2 karyawan Beta muncul

**Pertanyaan**:
- Berapa total karyawan di database? (Query SQL)
- Apakah Alpha bisa lihat karyawan Beta? Kenapa?

### Latihan 2: Cross-Tenant URL Test (IDOR) (15 menit)

1. Login sebagai Alpha, tambah 1 department, catat URL-nya (contoh: `/departments/15`)
2. Logout, login sebagai Beta
3. Coba akses URL department Alpha langsung: `/departments/15`
4. **Expected**: 404 Not Found

5. Coba juga via API:
   ```bash
   # Login Alpha, dapat token
   # Login Beta, dapat token
   # Coba akses data Alpha pakai token Beta
   curl -si http://localhost:8000/api/v1/employees/{id-alpha} \
     -H "Authorization: Bearer $BETA_TOKEN"
   ```
6. **Expected**: 404

### Latihan 3: Validation Scope Test (15 menit)

1. Login sebagai Alpha → Buat department "Engineering" (catat ID)
2. Login sebagai Beta → Coba buat karyawan dengan department_id milik Alpha
3. **Expected**: Validation error

4. Login sebagai Beta → Buat department "Engineering" juga
5. **Expected**: Berhasil! (Nama department boleh sama di company berbeda)

### Latihan 4: Tulis Pest Test (30 menit)

Buat file test baru:

```bash
php artisan make:test --pest TenantIsolation/EmployeeIsolationTest
```

Tulis test untuk:

1. `it('shows only employees from current tenant')`
2. `it('returns 404 when accessing employee from another tenant')`
3. `it('cannot create employee with department from another tenant')`
4. `it('sets company_id automatically on employee creation')`

Jalankan:

```bash
php artisan test --compact tests/Feature/TenantIsolation/EmployeeIsolationTest.php
```

### Latihan 5: Database Audit (15 menit)

Jalankan query-query di Section 17:

1. Cek semua tabel yang punya `company_id`
2. Cek distribusi data per company
3. Cek data bocor (employee tanpa company, dll.)
4. Cek orphan records

Tulis laporan: apakah database dalam keadaan bersih?

### Latihan 6: API Tenant Isolation Test (20 menit)

Jalankan lengkap:

```bash
# 1. Dapatkan token untuk 2 admin
ALPHA_TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@alpha.com","password":"password","device_name":"test"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

BETA_TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@beta.com","password":"password","device_name":"test"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# 2. Compare dashboard data
echo "=== ALPHA DASHBOARD ==="
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $ALPHA_TOKEN" | python3 -m json.tool

echo "=== BETA DASHBOARD ==="
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $BETA_TOKEN" | python3 -m json.tool

# 3. Try cross-tenant access (harus 404)
# Dapatkan ID employee Beta dari database
# Coba akses pakai token Alpha
```

Dokumentasikan hasil: apakah API mengisolasi dengan benar?

### Latihan 7: Buat Checklist untuk Module Baru (15 menit)

Bayangkan akan dibuat module baru: **Training Management** (pelatihan karyawan).

Gunakan checklist di Section 19. Buat dokumen yang menjawab:

1. Tabel apa yang perlu dibuat? (column apa saja?)
2. Controller method apa saja? Tenant isolation check di mana?
3. Validation rules apa yang perlu tenant-scoped?
4. Test isolation apa yang harus ditulis (minimal 5 test cases)?
5. Apakah butuh queue job? Jika ya, bagaimana tenant context-nya?

---

## Rangkuman Sesi 6

### Apa yang Sudah Dipelajari

| Topik | Key Takeaway |
|-------|-------------|
| Arsitektur | `SetTenant` middleware → `app('tenant')` + `setPermissionsTeamId()` |
| Data Isolation | SETIAP query HARUS `where('company_id', $tenant->id)` |
| Cross-Tenant Attack | URL manipulation → harus return 404, bukan 403 |
| Validation Scope | `unique` dan `exists` rules HARUS include `company_id` |
| Permission per Tenant | Spatie team mode → role "admin" berbeda per company |
| Approval Isolation | HR Alpha TIDAK bisa approve cuti Beta |
| Financial Isolation | Slip gaji, pajak, BPJS — sangat sensitif |
| Queued Jobs | Pass `company_id` eksplisit, panggil `setPermissionsTeamId()` |
| Activity Log | Log harus ter-scope per tenant |
| Pest Testing | Selalu test: "User A tidak bisa akses data User B" |
| Common Bugs | Lupa WHERE, mass assignment, global count tanpa scope |
| Superadmin | Cross-tenant VIEW only, tidak bisa modify |
| Database Audit | Query untuk detect data bocor |
| Debugging | Log tenant context, audit query, incident response |

### 5 Golden Rules Multi-Tenant

```
Rule 1: SETIAP query HARUS ada WHERE company_id
   Tidak ada pengecualian. Lupa = data breach.

Rule 2: company_id SELALU dari server, BUKAN dari request
   Jangan trust input user untuk menentukan tenant.

Rule 3: Return 404 untuk resource milik tenant lain
   Jangan bocorkan informasi keberadaan data (IDOR prevention).

Rule 4: Queue jobs harus di-pass company_id eksplisit
   Worker tidak punya HTTP context — harus self-contained.

Rule 5: Tulis test tenant isolation untuk setiap resource
   "User A tidak bisa akses data User B" harus jadi test wajib.
```

### Mindset Developer Multi-Tenant

```
Setiap kali menulis query, tanya:
"Apakah ada company_id filter di sini?"

Setiap kali menulis form request, tanya:
"Apakah exists/unique rule sudah di-scope ke tenant?"

Setiap kali menulis test, tanya:
"Sudahkah saya test cross-tenant access?"

Setiap kali deploy fitur baru, tanya:
"Sudahkah saya audit SEMUA query dengan checklist?"

Jika jawabannya TIDAK → STOP dan perbaiki dulu!
```

### Recap Minggu 2 (Sesi 4-6)

| Sesi | Fokus | Hasil |
|------|-------|-------|
| **Sesi 4** | Running Web + Dashboard | Bisa jalankan app, paham login flow & middleware |
| **Sesi 5** | Flutter + API Connection | Paham API auth, bisa test endpoint, Swagger |
| **Sesi 6 (a)** | SaaS Billing + Xendit | Bisa setup payment gateway, webhook, subscription flow |
| **Sesi 6 (b)** | Multi-Tenant Testing | Paham isolation, bisa menulis tenant isolation test |

### Preview Minggu 3

Di minggu berikutnya mulai **hands-on development**:
- Menambah fitur baru menggunakan TDD
- Implementasi modul dari nol (migration → model → controller → view → test)
- Code review dan best practices
- Integrasi tenant isolation test ke setiap fitur baru

---

## Appendix: Quick Reference

### Kode Pattern Copy-Paste

#### Controller (Index)
```php
public function index(Request $request): View
{
    $tenant = app('tenant');

    $items = Model::where('company_id', $tenant->id)
        ->when($request->search, fn ($q, $s) =>
            $q->where('name', 'like', "%{$s}%")
        )
        ->latest()
        ->paginate(15);

    return view('module.index', compact('items'));
}
```

#### Controller (Show)
```php
public function show(Model $model): View
{
    if ($model->company_id !== app('tenant')->id) {
        abort(404);
    }

    return view('module.show', compact('model'));
}
```

#### Controller (Store)
```php
public function store(StoreRequest $request): RedirectResponse
{
    $model = Model::create([
        'company_id' => app('tenant')->id,
        ...$request->validated(),
    ]);

    return redirect()
        ->route('module.show', $model)
        ->with('success', 'Data berhasil disimpan.');
}
```

#### Form Request
```php
public function rules(): array
{
    $tenant = app('tenant');

    return [
        'name' => ['required', 'string', 'max:255'],
        'email' => [
            'required', 'email',
            Rule::unique('models', 'email')
                ->where('company_id', $tenant->id),
        ],
        'department_id' => [
            'required',
            Rule::exists('departments', 'id')
                ->where('company_id', $tenant->id),
        ],
    ];
}
```

#### API Controller
```php
public function index(Request $request): JsonResponse
{
    $company = $request->user()->company;

    $items = Model::where('company_id', $company->id)
        ->latest()
        ->paginate(20);

    return response()->json([
        'data' => ModelResource::collection($items),
        'meta' => [
            'current_page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
            'total' => $items->total(),
        ],
    ]);
}
```

#### Test Boilerplate
```php
beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $this->companyA = Company::factory()->create();
    $this->companyB = Company::factory()->create();

    $this->adminA = User::factory()->create(['company_id' => $this->companyA->id]);
    setPermissionsTeamId($this->companyA->id);
    $this->adminA->assignRole('admin');

    $this->adminB = User::factory()->create(['company_id' => $this->companyB->id]);
    setPermissionsTeamId($this->companyB->id);
    $this->adminB->assignRole('admin');
});
```

---

> **Catatan Instruktur**: Sesi ini adalah yang **paling krusial dari segi security**. Pastikan setiap peserta BENAR-BENAR paham kenapa tenant isolation penting dan bisa menulis test untuk memverifikasinya. Jika perlu, tambah waktu untuk Latihan 4 (menulis Pest test) dan Latihan 6 (API isolation test).
>
> **Koneksi ke Sesi 6 Utama (SaaS Billing)**: Tenant isolation dan billing saling berkaitan. Subscription data, invoice, dan payment juga harus ter-isolasi. Saat ngulik Xendit webhook di sesi utama, jangan lupa verify bahwa `company_id` resolved dari payment record, bukan dari webhook payload.
