# Sesi 6: Tenant Context Handling — Multi-Company Scenario Testing

> **Durasi**: 2-3 jam
> **Tanggal**: 16 April 2026 (Minggu 2)
> **Prasyarat**: GajiPro running, paham login flow & API (Sesi 4 & 5)
> **Tujuan**: Menguji isolasi data multi-tenant secara mendalam, memahami skenario edge case, dan menulis test tenant isolation

---

## Daftar Isi

1. [Kenapa Multi-Tenant Testing Itu Kritis](#1-kenapa-multi-tenant-testing-itu-kritis)
2. [Setup: Membuat 2 Perusahaan Demo](#2-setup-membuat-2-perusahaan-demo)
3. [Scenario 1: Data Isolation — Karyawan](#3-scenario-1-data-isolation--karyawan)
4. [Scenario 2: Data Isolation — Via API](#4-scenario-2-data-isolation--via-api)
5. [Scenario 3: Cross-Tenant URL Manipulation](#5-scenario-3-cross-tenant-url-manipulation)
6. [Scenario 4: Form Request Validation Scope](#6-scenario-4-form-request-validation-scope)
7. [Scenario 5: Role & Permission per Tenant](#7-scenario-5-role--permission-per-tenant)
8. [Scenario 6: Approval Workflow Isolation](#8-scenario-6-approval-workflow-isolation)
9. [Scenario 7: Payroll & Financial Data Isolation](#9-scenario-7-payroll--financial-data-isolation)
10. [Cara Menulis Test Tenant Isolation (Pest)](#10-cara-menulis-test-tenant-isolation-pest)
11. [Common Vulnerability Patterns](#11-common-vulnerability-patterns)
12. [Superadmin Cross-Tenant View](#12-superadmin-cross-tenant-view)
13. [Database-Level Verification](#13-database-level-verification)
14. [Checklist Tenant Security](#14-checklist-tenant-security)
15. [Latihan Praktik](#15-latihan-praktik)

---

## 1. Kenapa Multi-Tenant Testing Itu Kritis

### Real-World Horror Stories

```
🚨 Case 1: SaaS Payroll Bocor
   → Company A bisa lihat slip gaji Company B
   → 10.000 karyawan data gajinya exposed
   → Denda GDPR: €20 juta

🚨 Case 2: CRM Data Leak
   → Bug di filter query, lupa WHERE company_id
   → Customer list Company A muncul di Company B
   → Kehilangan kepercayaan, customer churn 40%

🚨 Case 3: HR System Breach
   → Admin Company A bisa edit karyawan Company B
   → Data pribadi (KTP, NPWP, rekening) bocor
   → Tuntutan hukum dari karyawan
```

### Di GajiPro, Data yang HARUS Ter-isolasi

| Kategori | Data Sensitif |
|----------|--------------|
| **Karyawan** | Nama, KTP, NPWP, alamat, rekening bank |
| **Gaji** | Slip gaji, komponen gaji, potongan |
| **Pajak** | PPh 21, bukti potong, SPT |
| **BPJS** | Nomor BPJS, iuran, klaim |
| **Kehadiran** | Jam masuk/keluar, GPS location, foto wajah |
| **Cuti** | Saldo cuti, alasan cuti, riwayat |
| **Keuangan** | Reimbursement, THR, bonus |
| **Dokumen** | KTP scan, kontrak, surat peringatan |

**Semua data ini WAJIB ter-scope ke `company_id`!**

---

## 2. Setup: Membuat 2 Perusahaan Demo

### Cara 1: Register via Web

1. Buka `http://localhost:8000/register`
2. Daftarkan **Perusahaan Alpha**:
   - Nama: `PT Alpha Technology`
   - Email: `admin@alpha.com`
   - Password: `password`
3. Logout
4. Daftarkan **Perusahaan Beta**:
   - Nama: `PT Beta Digital`
   - Email: `admin@beta.com`
   - Password: `password`

### Cara 2: Seed via Artisan

```bash
php artisan tinker
```

```php
// Buat 2 company dengan data lengkap
use App\Models\Company;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;

// --- Company Alpha ---
$alpha = Company::create([
    'name' => 'PT Alpha Technology',
    'slug' => 'pt-alpha-technology',
    'email' => 'info@alpha.com',
    'is_active' => true,
    'subscription_plan' => 'professional',
    'subscription_ends_at' => now()->addYear(),
    'max_employees' => 50,
    'timezone' => 'Asia/Jakarta',
]);

setPermissionsTeamId($alpha->id);

// Create roles for Alpha
// ... (roles dibuat otomatis saat registrasi)

$adminAlpha = User::create([
    'name' => 'Admin Alpha',
    'email' => 'admin@alpha.com',
    'password' => bcrypt('password'),
    'company_id' => $alpha->id,
    'is_active' => true,
]);

// --- Company Beta ---
$beta = Company::create([
    'name' => 'PT Beta Digital',
    'slug' => 'pt-beta-digital',
    'email' => 'info@beta.com',
    'is_active' => true,
    'subscription_plan' => 'starter',
    'subscription_ends_at' => now()->addYear(),
    'max_employees' => 25,
    'timezone' => 'Asia/Jakarta',
]);
```

### Cara 3: Gunakan Existing Demo Data

Jika sudah seed dengan `DatabaseSeeder`, gunakan:
- **Company 1**: PT Demo GajiPro (`admin@demo.gajipro.com`)
- **Company 2**: Register baru via `/register`

### Verifikasi Setup

```sql
-- Cek companies
SELECT id, name, slug, is_active FROM companies;

-- Cek users per company
SELECT u.id, u.name, u.email, u.company_id, c.name as company
FROM users u
JOIN companies c ON u.company_id = c.id
ORDER BY u.company_id;
```

---

## 3. Scenario 1: Data Isolation — Karyawan

### Test: Admin Alpha Tidak Bisa Lihat Karyawan Beta

#### Step 1: Tambah Karyawan di Masing-masing Company

Login sebagai **Admin Alpha** → Tambah karyawan:
- Nama: `Budi Alpha` (akan mendapat company_id = Alpha)

Login sebagai **Admin Beta** → Tambah karyawan:
- Nama: `Siti Beta` (akan mendapat company_id = Beta)

#### Step 2: Verifikasi Isolasi

Login sebagai **Admin Alpha**:
- Buka Daftar Karyawan → Harus ada `Budi Alpha`, TIDAK ada `Siti Beta`

Login sebagai **Admin Beta**:
- Buka Daftar Karyawan → Harus ada `Siti Beta`, TIDAK ada `Budi Alpha`

#### Step 3: Verifikasi di Database

```sql
SELECT e.id, e.full_name, e.company_id, c.name as company
FROM employees e
JOIN companies c ON e.company_id = c.id
ORDER BY e.company_id, e.full_name;

-- Expected:
-- id | full_name    | company_id | company
-- 1  | Budi Alpha   | 1          | PT Alpha Technology
-- 2  | Siti Beta    | 2          | PT Beta Digital
```

### Bagaimana Controller Menjaga Isolasi?

```php
// app/Http/Controllers/EmployeeController.php

public function index(Request $request): View
{
    $tenant = app('tenant');  // ← Company dari user yang login

    $employees = Employee::with(['department', 'position'])
        ->where('company_id', $tenant->id)  // ← FILTER!
        ->paginate(15);

    // Admin Alpha: company_id = 1 → hanya karyawan Alpha
    // Admin Beta:  company_id = 2 → hanya karyawan Beta

    return view('employees.index', compact('employees'));
}
```

---

## 4. Scenario 2: Data Isolation — Via API

### Test: Token Alpha Tidak Bisa Akses Data Beta

```bash
# Login sebagai employee Alpha
ALPHA_TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@alpha.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# Login sebagai employee Beta
BETA_TOKEN=$(curl -s http://localhost:8000/api/v1/auth/login \
  -X POST -H "Content-Type: application/json" \
  -d '{"email":"admin@beta.com","password":"password"}' \
  | python3 -c "import sys,json; print(json.load(sys.stdin)['data']['token'])")

# Alpha minta dashboard → hanya data Alpha
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $ALPHA_TOKEN" | python3 -m json.tool

# Beta minta dashboard → hanya data Beta
curl -s http://localhost:8000/api/v1/dashboard \
  -H "Authorization: Bearer $BETA_TOKEN" | python3 -m json.tool

# Compare: Total karyawan harus BERBEDA!
```

### Kenapa Token Tidak Bisa Akses Data Company Lain?

```php
// API Controller pattern:
public function index(Request $request): JsonResponse
{
    $user = $request->user();          // User dari token
    $company = $user->company;          // Company milik user
    $employee = $user->employee;        // Employee record user

    // Query SELALU pakai company milik user:
    $attendances = Attendance::where('company_id', $company->id)
        ->where('employee_id', $employee->id)
        ->get();

    // Token Alpha → company_id = 1 → hanya data Alpha
    // Token Beta  → company_id = 2 → hanya data Beta
}
```

---

## 5. Scenario 3: Cross-Tenant URL Manipulation

### Test: Akses Resource Milik Tenant Lain via URL

Ini adalah **serangan paling umum** pada sistem multi-tenant!

#### Skenario

Admin Alpha login. Dia tahu bahwa Employee ID 5 adalah milik Beta.
Dia coba akses langsung:

```
http://localhost:8000/employees/5
```

#### Yang Harus Terjadi

```
404 Not Found
```

Bukan `403 Forbidden`, tapi `404 Not Found`! Kenapa?

#### Kenapa 404, Bukan 403?

```php
// Controller show():
public function show(Employee $employee): View
{
    $tenant = app('tenant');

    // PENTING: Return 404, bukan 403!
    if ($employee->company_id !== $tenant->id) {
        abort(404);
        // 404 = "Resource tidak ada" (dari perspektif Alpha, memang tidak ada)
        // 403 = "Kamu tidak boleh akses" (ini BOCORKAN info bahwa resource EXISTS)
    }

    return view('employees.show', compact('employee'));
}
```

**Security principle:** Jangan bocorkan informasi tentang keberadaan resource. Jika pakai 403, attacker tahu ID itu valid — tinggal cari cara bypass auth.

#### Test di cURL (API)

```bash
# Cari ID employee milik Beta
BETA_EMPLOYEE_ID=5  # ganti sesuai

# Coba akses dari token Alpha
curl -s http://localhost:8000/api/v1/leaves?employee_id=$BETA_EMPLOYEE_ID \
  -H "Authorization: Bearer $ALPHA_TOKEN"

# Expected: Hanya return data milik Alpha (ignore employee_id parameter)
# atau 404 jika endpoint butuh specific employee
```

---

## 6. Scenario 4: Form Request Validation Scope

### Masalah: Unique Validation Tanpa Scope

```php
// ❌ SALAH — Tanpa scope company_id
'email' => ['required', 'email', 'unique:employees,email']
// Artinya: email harus unique di SELURUH database
// Alpha tidak bisa punya email yang sama dengan Beta!

// ✅ BENAR — Dengan scope company_id
'email' => ['required', 'email',
    Rule::unique('employees', 'email')
        ->where('company_id', app('tenant')->id)
]
// Artinya: email harus unique DALAM company yang sama
// Alpha & Beta BOLEH punya email yang sama
```

### Masalah: Exists Validation Tanpa Scope

```php
// ❌ SALAH — Bisa pilih department milik company lain!
'department_id' => ['required', 'exists:departments,id']

// ✅ BENAR — Hanya department milik tenant ini
'department_id' => ['required',
    Rule::exists('departments', 'id')
        ->where('company_id', app('tenant')->id)
]
```

### Test Skenario

1. Company Alpha punya Department "Engineering" (id=1)
2. Company Beta punya Department "Marketing" (id=5)
3. Admin Alpha coba buat karyawan dengan `department_id = 5`

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
            'email' => ['required', 'email',
                Rule::unique('employees', 'email')
                    ->where('company_id', $tenant->id),
            ],

            // Hanya department milik company ini
            'department_id' => ['required',
                Rule::exists('departments', 'id')
                    ->where('company_id', $tenant->id),
            ],

            // Hanya position milik company ini
            'position_id' => ['required',
                Rule::exists('positions', 'id')
                    ->where('company_id', $tenant->id),
            ],

            // Hanya work schedule milik company ini
            'work_schedule_id' => ['nullable',
                Rule::exists('work_schedules', 'id')
                    ->where('company_id', $tenant->id),
            ],
        ];
    }
}
```

---

## 7. Scenario 5: Role & Permission per Tenant

### Spatie Permission Team Mode

GajiPro menggunakan Spatie Permission dengan **team mode**:

```php
// config/permission.php
'teams' => true,
// team_foreign_key = company_id
```

### Apa Artinya?

```
Company Alpha (id=1):
├── Role: admin (team_id=1)
├── Role: hr-manager (team_id=1)
├── Role: payroll-manager (team_id=1)
└── Role: employee (team_id=1)

Company Beta (id=2):
├── Role: admin (team_id=2)
├── Role: hr-manager (team_id=2)
├── Role: payroll-manager (team_id=2)
└── Role: employee (team_id=2)
```

**Admin di Alpha ≠ Admin di Beta!** Meskipun nama role sama, mereka terpisah.

### Test: Permission Check

```php
// SetTenant middleware:
setPermissionsTeamId($company->id);

// Setelah ini, $user->hasRole('admin') hanya cek role
// di team/company user tersebut

// User Alpha yang punya role 'admin' di team 1:
$userAlpha->hasRole('admin');
// → true (karena setPermissionsTeamId(1))

// Tanpa setPermissionsTeamId:
// → Bisa jadi salah karena cek di semua team!
```

### Kenapa Ini Penting?

Bayangkan Alpha menambahkan custom permission "can-export-data" hanya untuk admin mereka. Tanpa team isolation, admin Beta juga bisa dapat permission tersebut!

```sql
-- Verifikasi di database
SELECT r.name, r.team_id, c.name as company
FROM roles r
LEFT JOIN companies c ON r.team_id = c.id
ORDER BY r.team_id, r.name;
```

---

## 8. Scenario 6: Approval Workflow Isolation

### Multi-Tenant Approval Flow

```
Company Alpha:
  Karyawan Alpha → Ajukan cuti → Approval oleh HR Alpha → Approved ✅

Company Beta:
  Karyawan Beta → Ajukan cuti → Approval oleh HR Beta → Approved ✅

TIDAK BOLEH:
  Karyawan Alpha → Ajukan cuti → HR Beta approve ❌
  HR Alpha → Lihat pending cuti Beta ❌
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
// API: Approve leave
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

---

## 9. Scenario 7: Payroll & Financial Data Isolation

### Ini Data Paling Sensitif!

```
Slip gaji Alpha: Rp 25.000.000/bulan
  → HANYA boleh dilihat oleh Alpha

Slip gaji Beta: Rp 15.000.000/bulan
  → HANYA boleh dilihat oleh Beta
```

### Test: API Payslip Isolation

```bash
# Token Alpha
curl -s http://localhost:8000/api/v1/payslips \
  -H "Authorization: Bearer $ALPHA_TOKEN"
# → Hanya slip gaji karyawan Alpha

# Token Beta
curl -s http://localhost:8000/api/v1/payslips \
  -H "Authorization: Bearer $BETA_TOKEN"
# → Hanya slip gaji karyawan Beta

# Coba akses payslip spesifik milik Beta pakai token Alpha
curl -s http://localhost:8000/api/v1/payslips/999 \
  -H "Authorization: Bearer $ALPHA_TOKEN"
# → 404 Not Found (bukan 403!)
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
```

---

## 10. Cara Menulis Test Tenant Isolation (Pest)

### Pattern Dasar: Test Isolation

```php
<?php

use App\Models\User;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Setup 2 companies
    $this->companyAlpha = Company::factory()->create(['name' => 'Alpha']);
    $this->companyBeta = Company::factory()->create(['name' => 'Beta']);

    // Setup users
    $this->adminAlpha = User::factory()->create([
        'company_id' => $this->companyAlpha->id,
    ]);
    $this->adminAlpha->assignRole('admin');

    $this->adminBeta = User::factory()->create([
        'company_id' => $this->companyBeta->id,
    ]);
    $this->adminBeta->assignRole('admin');
});

describe('Tenant Isolation', function () {

    it('cannot list employees from another company', function () {
        // Arrange: Create employees for each company
        $empAlpha = Employee::factory()->create([
            'company_id' => $this->companyAlpha->id,
            'full_name' => 'Budi Alpha',
        ]);
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
            'full_name' => 'Siti Beta',
        ]);

        // Act: Alpha admin views employee list
        $response = $this->actingAs($this->adminAlpha)
            ->get(route('employees.index'));

        // Assert: Only Alpha employee visible
        $response->assertOk()
            ->assertSee('Budi Alpha')
            ->assertDontSee('Siti Beta');
    });

    it('cannot view employee from another company', function () {
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        // Alpha admin tries to view Beta's employee
        $response = $this->actingAs($this->adminAlpha)
            ->get(route('employees.show', $empBeta));

        $response->assertNotFound(); // 404, NOT 403!
    });

    it('cannot update employee from another company', function () {
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->put(route('employees.update', $empBeta), [
                'full_name' => 'Hacked Name',
            ]);

        $response->assertNotFound();

        // Verify data not changed
        $this->assertDatabaseMissing('employees', [
            'id' => $empBeta->id,
            'full_name' => 'Hacked Name',
        ]);
    });

    it('cannot delete employee from another company', function () {
        $empBeta = Employee::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->delete(route('employees.destroy', $empBeta));

        $response->assertNotFound();

        // Verify not deleted
        $this->assertDatabaseHas('employees', [
            'id' => $empBeta->id,
        ]);
    });
});

describe('Validation Scoping', function () {

    it('cannot use department from another company', function () {
        $deptBeta = Department::factory()->create([
            'company_id' => $this->companyBeta->id,
        ]);

        $response = $this->actingAs($this->adminAlpha)
            ->post(route('employees.store'), [
                'full_name' => 'New Employee',
                'email' => 'new@example.com',
                'department_id' => $deptBeta->id,  // ← Beta's department!
            ]);

        // Should fail validation
        $response->assertSessionHasErrors('department_id');
    });
});
```

### Run Test

```bash
# Run specific test file
php artisan test --compact tests/Feature/TenantIsolationTest.php

# Run dengan filter
php artisan test --compact --filter="cannot list employees from another"
```

### Template: Test untuk Setiap Resource

Untuk setiap resource (Employee, Department, LeaveRequest, dll.), tulis test:

```php
describe('{Resource} Tenant Isolation', function () {
    it('cannot list {resources} from another company');
    it('cannot view {resource} from another company');
    it('cannot create {resource} with another company data');
    it('cannot update {resource} from another company');
    it('cannot delete {resource} from another company');
});
```

---

## 11. Common Vulnerability Patterns

### 1. Lupa WHERE company_id

```php
// ❌ VULNERABLE
$employees = Employee::all();

// ✅ SAFE
$employees = Employee::where('company_id', $tenant->id)->get();
```

**Pencegahan:** Global scope atau review checklist.

### 2. Route Model Binding Tanpa Scope

```php
// ❌ VULNERABLE — Laravel auto-resolve Employee by ID, tanpa company check
public function show(Employee $employee): View
{
    return view('employees.show', compact('employee'));
    // Employee bisa dari company manapun!
}

// ✅ SAFE — Tambah ownership check
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
// ❌ VULNERABLE — Load semua relasi tanpa filter
$department = Department::with('employees')->find($id);
// employees bisa termasuk dari company lain jika ada bug

// ✅ SAFE — Scope relasi juga
$department = Department::with(['employees' => function ($q) use ($tenant) {
    $q->where('company_id', $tenant->id);
}])->where('company_id', $tenant->id)->findOrFail($id);
```

### 4. Count/Aggregate Tanpa Scope

```php
// ❌ VULNERABLE — Hitung semua karyawan!
$totalEmployees = Employee::count();

// ✅ SAFE
$totalEmployees = Employee::where('company_id', $tenant->id)->count();
```

### 5. Search/Filter Tanpa Scope

```php
// ❌ VULNERABLE
$employees = Employee::where('full_name', 'like', "%{$search}%")->get();
// Bisa return karyawan dari company lain!

// ✅ SAFE
$employees = Employee::where('company_id', $tenant->id)
    ->where('full_name', 'like', "%{$search}%")
    ->get();
```

### 6. Mass Assignment company_id

```php
// ❌ VULNERABLE — User bisa inject company_id lain via form
$employee = Employee::create($request->all());

// ✅ SAFE — Set company_id dari tenant, bukan dari request
$employee = Employee::create([
    'company_id' => $tenant->id,  // ← Selalu dari server
    ...$request->validated(),
]);
```

---

## 12. Superadmin Cross-Tenant View

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

```php
// CompanyController hanya punya index() dan show()
// TIDAK ada create(), edit(), update(), destroy()

// Ini by design:
// - Superadmin MONITOR, bukan MANAGE
// - Company data hanya bisa diubah oleh admin company tersebut
// - Mencegah "God Mode" abuse
```

### Test: Superadmin View

```bash
# Login superadmin
curl -s http://localhost:8000/superadmin/login
# ... (web-based login)

# Atau via database check:
SELECT
    c.name,
    COUNT(e.id) as total_employees,
    COUNT(DISTINCT u.id) as total_users
FROM companies c
LEFT JOIN employees e ON e.company_id = c.id
LEFT JOIN users u ON u.company_id = c.id
GROUP BY c.id, c.name;
```

---

## 13. Database-Level Verification

### Query: Cek Semua Tabel Punya company_id

```sql
-- Tabel yang HARUS punya company_id
SELECT TABLE_NAME, COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'gajipro'
  AND COLUMN_NAME = 'company_id'
ORDER BY TABLE_NAME;
```

### Query: Detect Data Bocor

```sql
-- Cek apakah ada employee dengan company_id yang tidak match user-nya
SELECT e.id, e.full_name, e.company_id as emp_company,
       u.id as user_id, u.company_id as user_company
FROM employees e
JOIN users u ON e.user_id = u.id
WHERE e.company_id != u.company_id;
-- Expected: 0 rows!

-- Cek apakah ada attendance tanpa company_id
SELECT COUNT(*) FROM attendances WHERE company_id IS NULL;
-- Expected: 0

-- Cek data distribution per company
SELECT c.name, 'employees' as type, COUNT(e.id) as count
FROM companies c
LEFT JOIN employees e ON e.company_id = c.id
GROUP BY c.id, c.name
UNION ALL
SELECT c.name, 'departments', COUNT(d.id)
FROM companies c
LEFT JOIN departments d ON d.company_id = c.id
GROUP BY c.id, c.name
UNION ALL
SELECT c.name, 'attendances', COUNT(a.id)
FROM companies c
LEFT JOIN attendances a ON a.company_id = c.id
GROUP BY c.id, c.name
ORDER BY name, type;
```

### Query: Foreign Key Integrity

```sql
-- Cek apakah ada orphan records (employee tanpa company)
SELECT e.id, e.full_name, e.company_id
FROM employees e
LEFT JOIN companies c ON e.company_id = c.id
WHERE c.id IS NULL;
-- Expected: 0 rows!
```

---

## 14. Checklist Tenant Security

### Per-Feature Checklist

Setiap kali develop fitur baru, gunakan checklist ini:

#### Controller Layer
- [ ] `index()` → Query pakai `where('company_id', $tenant->id)`
- [ ] `show()` → Cek `$model->company_id !== $tenant->id` → `abort(404)`
- [ ] `store()` → Set `company_id` dari `$tenant->id`, BUKAN dari request
- [ ] `update()` → Cek ownership sebelum update
- [ ] `destroy()` → Cek ownership sebelum delete

#### Form Request Layer
- [ ] `unique` rules include `->where('company_id', ...)`
- [ ] `exists` rules include `->where('company_id', ...)`
- [ ] Relational IDs (department_id, position_id) validated with company scope

#### API Controller Layer
- [ ] Gunakan `$request->user()->company` untuk resolve tenant
- [ ] Return 404 (bukan 403) untuk resource milik tenant lain
- [ ] Pagination tetap di-scope ke tenant

#### Testing Layer
- [ ] Test: User A tidak bisa list data User B
- [ ] Test: User A tidak bisa view/edit/delete data User B
- [ ] Test: Validation gagal jika reference ID milik tenant lain
- [ ] Test: Count/aggregate hanya menghitung data tenant sendiri

#### Database Layer
- [ ] Tabel baru HARUS punya `company_id` (kecuali system tables)
- [ ] Foreign key ke `companies` table
- [ ] Index pada `company_id` untuk performance
- [ ] Composite unique constraints include `company_id`

---

## 15. Latihan Praktik

### Latihan 1: Setup 2 Companies & Verify Isolation (20 menit)

1. Register 2 perusahaan baru via `/register`:
   - `PT Latihan Alpha` (admin: `alpha@test.com`)
   - `PT Latihan Beta` (admin: `beta@test.com`)
2. Login sebagai Alpha → Tambah 3 karyawan dengan nama unik
3. Login sebagai Beta → Tambah 2 karyawan dengan nama unik
4. **Verifikasi**: Login Alpha → Pastikan HANYA 3 karyawan Alpha muncul
5. **Verifikasi**: Login Beta → Pastikan HANYA 2 karyawan Beta muncul

**Pertanyaan:**
- Berapa total karyawan di database? (Query SQL)
- Apakah Alpha bisa lihat karyawan Beta? Kenapa?

### Latihan 2: Cross-Tenant URL Test (15 menit)

1. Login sebagai Alpha
2. Tambah 1 departemen, catat URL-nya (contoh: `/departments/15`)
3. Logout, login sebagai Beta
4. Coba akses URL departemen Alpha langsung: `/departments/15`
5. **Expected**: 404 Not Found

6. Coba juga via API:
   ```bash
   # Login Alpha, dapat token
   # Login Beta, dapat token
   # Coba akses data Alpha pakai token Beta
   ```

### Latihan 3: Validation Scope Test (15 menit)

1. Login sebagai Alpha → Buat department "Engineering"
2. Login sebagai Beta → Coba buat karyawan dengan department_id milik Alpha
3. **Expected**: Validation error

4. Login sebagai Beta → Buat department "Engineering" juga
5. **Expected**: Berhasil! (Nama boleh sama di company berbeda)

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

Jalankan query-query di Section 13 (Database-Level Verification):

1. Cek semua tabel yang punya `company_id`
2. Cek distribusi data per company
3. Cek apakah ada data bocor (employee tanpa company, dll.)

### Latihan 6: Buat Checklist untuk Module Baru (15 menit)

Bayangkan kamu akan membuat module baru: **Training Management** (manajemen pelatihan karyawan).

Gunakan checklist di Section 14 dan tulis:

1. Tabel apa yang perlu dibuat? (column apa saja?)
2. Controller method apa saja?
3. Validation rules apa yang perlu tenant-scoped?
4. Test isolation apa yang harus ditulis?

---

## Rangkuman Sesi 6

### Apa yang Sudah Dipelajari

| Topik | Key Takeaway |
|-------|-------------|
| Data Isolation | SETIAP query HARUS `where('company_id', $tenant->id)` |
| Cross-Tenant Attack | URL manipulation → harus return 404, bukan 403 |
| Validation Scope | `unique` dan `exists` rules HARUS include `company_id` |
| Permission per Tenant | Spatie team mode → role "admin" berbeda per company |
| Approval Isolation | HR Alpha TIDAK bisa approve cuti Beta |
| Financial Isolation | Slip gaji, pajak, BPJS — sangat sensitif |
| Pest Testing | Selalu test: "User A tidak bisa akses data User B" |
| Common Bugs | Lupa WHERE, mass assignment, global count tanpa scope |
| Superadmin | Cross-tenant VIEW only, tidak bisa modify |
| Database Audit | Query untuk detect data bocor |

### 3 Golden Rules Multi-Tenant

```
🔒 Rule 1: SETIAP query HARUS ada WHERE company_id
   → Tidak ada pengecualian. Lupa = data breach.

🔒 Rule 2: company_id SELALU dari server, BUKAN dari request
   → Jangan trust input user untuk menentukan tenant.

🔒 Rule 3: Return 404 untuk resource milik tenant lain
   → Jangan bocorkan informasi keberadaan data.
```

### Mindset Developer Multi-Tenant

```
Setiap kali menulis query, tanya:
"Apakah ada company_id filter di sini?"

Setiap kali menulis form request, tanya:
"Apakah exists/unique rule sudah di-scope ke tenant?"

Setiap kali menulis test, tanya:
"Sudahkah saya test cross-tenant access?"

Jika jawabannya TIDAK → STOP dan perbaiki dulu!
```

---

## Recap Minggu 2 (Sesi 4-6)

| Sesi | Fokus | Hasil |
|------|-------|-------|
| **Sesi 4** | Running Web + Dashboard | Bisa jalankan app, paham login flow & middleware |
| **Sesi 5** | Flutter + API Connection | Paham API auth, bisa test endpoint, Swagger |
| **Sesi 6** | Multi-Tenant Testing | Paham isolation, bisa menulis tenant isolation test |

### Preview Minggu 3

Di minggu berikutnya kita akan mulai **hands-on development**:
- Menambah fitur baru menggunakan TDD
- Implementasi modul dari nol (migration → model → controller → view → test)
- Code review dan best practices

---

> **Catatan Instruktur:** Sesi ini adalah yang paling krusial dari segi security. Pastikan setiap peserta BENAR-BENAR paham kenapa tenant isolation penting dan bisa menulis test untuk memverifikasinya. Jika perlu, tambah waktu untuk latihan 4 (menulis Pest test).
