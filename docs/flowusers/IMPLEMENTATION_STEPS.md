# Implementation Steps - Trial Onboarding

Detail step-by-step untuk implementasi trial onboarding flow.

---

## Step 1: Update Registration Controller

**File:** `app/Http/Controllers/Auth/RegisteredUserController.php`

### Current Code Issues:
1. User hanya di-assign role `admin`
2. Tidak ada Employee record untuk user
3. User tidak bisa login ke mobile app

### Required Changes:

```php
public function store(Request $request): RedirectResponse
{
    // ... validation ...

    $user = DB::transaction(function () use ($validated) {
        // Create company (existing)
        $company = Company::create([...]);

        // Create user (existing)
        $user = User::create([...]);

        // ✅ CHANGE 1: Assign multiple roles
        $user->assignRole(['admin', 'hr-manager', 'employee']);

        // ✅ CHANGE 2: Create Employee record for user
        $this->createEmployeeForUser($user, $company);

        // Seed demo data (existing)
        if (!app()->runningUnitTests()) {
            $demoService = new DemoDataService();
            $demoService->seedDemoData($company, $user); // Pass user
        }

        return $user;
    });

    // ... rest of code ...
}

/**
 * Create Employee record for the registering user
 */
protected function createEmployeeForUser(User $user, Company $company): Employee
{
    // Parse name
    $nameParts = explode(' ', $user->name, 2);
    $firstName = $nameParts[0];
    $lastName = $nameParts[1] ?? '';

    // Get or create default department
    $department = Department::firstOrCreate(
        ['company_id' => $company->id, 'code' => 'MGT'],
        ['name' => 'Management', 'description' => 'Management team']
    );

    // Get or create default position
    $position = Position::firstOrCreate(
        ['company_id' => $company->id, 'code' => 'DIR'],
        [
            'name' => 'Director',
            'department_id' => $department->id,
            'level' => 4,
            'base_salary' => 25000000,
        ]
    );

    // Get default work schedule
    $workSchedule = WorkSchedule::where('company_id', $company->id)
        ->where('is_default', true)
        ->first();

    return Employee::create([
        'company_id' => $company->id,
        'user_id' => $user->id,
        'department_id' => $department->id,
        'position_id' => $position->id,
        'work_schedule_id' => $workSchedule?->id,
        'employee_number' => 'EMP' . date('Y') . '0001',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $user->email,
        'phone' => $user->phone ?? '08123456789',
        'date_of_birth' => now()->subYears(30),
        'gender' => 'male', // Default, user can update later
        'marital_status' => 'single',
        'religion' => 'Islam',
        'identity_number' => fake()->numerify('################'),
        'address' => 'Jakarta, Indonesia',
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'postal_code' => '12345',
        'hire_date' => now(),
        'employment_status' => 'permanent',
        'base_salary' => $position->base_salary,
        'bank_name' => 'BCA',
        'bank_account_number' => fake()->numerify('##########'),
        'bank_account_name' => $user->name,
        'tax_status' => 'TK/0',
        'is_active' => true,
    ]);
}
```

---

## Step 2: Update DemoDataService

**File:** `app/Services/DemoDataService.php`

### Changes Required:

```php
class DemoDataService
{
    protected Company $company;
    protected ?User $mainUser = null;
    protected ?Employee $mainEmployee = null;

    /**
     * Seed demo data for new company
     *
     * @param Company $company
     * @param User|null $mainUser The user who registered (owner)
     */
    public function seedDemoData(Company $company, ?User $mainUser = null): void
    {
        $this->company = $company;
        $this->mainUser = $mainUser;

        // Get main employee (created during registration)
        if ($mainUser) {
            $this->mainEmployee = Employee::where('user_id', $mainUser->id)->first();
        }

        DB::transaction(function () {
            $this->createDepartments();
            $this->createPositions();
            $this->createWorkSchedules();
            $this->createLeaveTypes();
            $this->createSalaryComponents();
            $this->createEmployees(); // Other employees
            $this->createLeaveBalances(); // Include main employee
            $this->createAttendances();   // Include main employee
            $this->createLeaveRequests();
            $this->createTaxSettings();
            $this->createBpjsSettings();
            $this->createPayroll();       // Include main employee

            // ✅ NEW: Additional demo data
            $this->createOvertimeRequests();
            $this->createReimbursements();
            $this->createAnnouncements();
        });

        // ...
    }

    protected function createLeaveBalances(): void
    {
        $employees = Employee::where('company_id', $this->company->id)->get();
        $leaveTypes = LeaveType::where('company_id', $this->company->id)->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                // ✅ Give main employee full balance
                $usedDays = ($employee->id === $this->mainEmployee?->id)
                    ? 2 // Main user used 2 days
                    : rand(0, min(3, $leaveType->max_days_per_year));

                LeaveBalance::create([
                    'employee_id' => $employee->id,
                    'leave_type_id' => $leaveType->id,
                    'year' => date('Y'),
                    'allocated_days' => $leaveType->max_days_per_year,
                    'used_days' => $usedDays,
                    'remaining_days' => $leaveType->max_days_per_year - $usedDays,
                ]);
            }
        }
    }

    protected function createAttendances(): void
    {
        $employees = Employee::where('company_id', $this->company->id)->get();
        $workSchedule = WorkSchedule::where('company_id', $this->company->id)
            ->where('is_default', true)->first();

        if (!$workSchedule) return;

        // Create attendance for last 30 days
        for ($i = 30; $i >= 0; $i--) {
            $date = now()->subDays($i);

            if ($date->isWeekend()) continue;

            foreach ($employees as $employee) {
                // ✅ Main employee always has attendance
                $skipRate = ($employee->id === $this->mainEmployee?->id) ? 95 : 90;

                if (rand(1, 100) > $skipRate) continue;

                // ... create attendance record
            }
        }
    }

    protected function createPayroll(): void
    {
        // Create payroll for last 3 months for main employee
        $months = $this->mainEmployee ? 3 : 1;

        for ($m = $months; $m >= 1; $m--) {
            $monthDate = now()->subMonths($m);

            $payroll = Payroll::create([
                'company_id' => $this->company->id,
                'period_start' => $monthDate->copy()->startOfMonth(),
                'period_end' => $monthDate->copy()->endOfMonth(),
                'payment_date' => $monthDate->copy()->endOfMonth(),
                'status' => 'paid',
                // ...
            ]);

            // Create payroll items for all employees (or just main employee for older months)
            $employees = ($m === 1)
                ? Employee::where('company_id', $this->company->id)->get()
                : collect([$this->mainEmployee])->filter();

            foreach ($employees as $employee) {
                // ... create payroll item
            }
        }
    }

    /**
     * ✅ NEW: Create overtime requests sample
     */
    protected function createOvertimeRequests(): void
    {
        if (!$this->mainEmployee) return;

        // Create 2 approved overtime requests for main employee
        OvertimeRequest::create([
            'employee_id' => $this->mainEmployee->id,
            'date' => now()->subDays(7),
            'start_time' => '17:00',
            'end_time' => '20:00',
            'duration_hours' => 3,
            'reason' => 'Menyelesaikan project deadline',
            'status' => 'approved',
            'approved_at' => now()->subDays(6),
        ]);

        OvertimeRequest::create([
            'employee_id' => $this->mainEmployee->id,
            'date' => now()->subDays(14),
            'start_time' => '17:00',
            'end_time' => '19:00',
            'duration_hours' => 2,
            'reason' => 'Client meeting',
            'status' => 'approved',
            'approved_at' => now()->subDays(13),
        ]);
    }

    /**
     * ✅ NEW: Create reimbursement samples
     */
    protected function createReimbursements(): void
    {
        if (!$this->mainEmployee) return;

        // Create reimbursement category if not exists
        $category = ReimbursementCategory::firstOrCreate(
            ['company_id' => $this->company->id, 'code' => 'TRANSPORT'],
            ['name' => 'Transport', 'max_amount' => 500000]
        );

        // Approved reimbursement
        Reimbursement::create([
            'employee_id' => $this->mainEmployee->id,
            'category_id' => $category->id,
            'amount' => 150000,
            'description' => 'Transport meeting client',
            'receipt_date' => now()->subDays(10),
            'status' => 'approved',
            'approved_at' => now()->subDays(8),
        ]);

        // Pending reimbursement
        Reimbursement::create([
            'employee_id' => $this->mainEmployee->id,
            'category_id' => $category->id,
            'amount' => 200000,
            'description' => 'Parkir dan tol',
            'receipt_date' => now()->subDays(2),
            'status' => 'pending',
        ]);
    }

    /**
     * ✅ NEW: Create announcement samples
     */
    protected function createAnnouncements(): void
    {
        Announcement::create([
            'company_id' => $this->company->id,
            'title' => 'Selamat Datang di GajiPro!',
            'content' => 'Terima kasih telah mendaftar di GajiPro. Ini adalah data demo untuk membantu Anda mengenal fitur-fitur yang tersedia. Anda bisa mereset data kapan saja dari menu Settings.',
            'priority' => 'high',
            'is_pinned' => true,
            'status' => 'published',
            'published_at' => now(),
            'target_type' => 'all',
        ]);

        Announcement::create([
            'company_id' => $this->company->id,
            'title' => 'Pengingat: Absensi Mobile',
            'content' => 'Gunakan aplikasi mobile untuk clock in/out dengan foto selfie dan lokasi GPS.',
            'priority' => 'normal',
            'is_pinned' => false,
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'target_type' => 'all',
        ]);
    }
}
```

---

## Step 3: Update Demo Banner Component

**File:** `resources/views/components/demo-mode-banner.blade.php`

```blade
@props(['company'])

@if($company && $company->is_demo_mode)
    <div class="bg-gradient-to-r from-primary-500 to-primary-600 text-white px-4 py-3 rounded-xl shadow-lg mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold">Mode Demo Aktif</h4>
                    <p class="text-sm text-primary-100">
                        Anda menggunakan data contoh untuk mencoba fitur GajiPro.
                        @if($company->subscription_ends_at)
                            Trial berakhir dalam
                            <strong>{{ now()->diffInDays($company->subscription_ends_at) }} hari</strong>.
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <a href="{{ route('demo.settings') }}"
                   class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition">
                    Mulai dari Nol
                </a>
                <a href="{{ route('subscription.plans') }}"
                   class="px-4 py-2 bg-white text-primary-600 hover:bg-primary-50 rounded-lg text-sm font-medium transition">
                    Upgrade Sekarang
                </a>
            </div>
        </div>

        {{-- Mobile App Hint --}}
        <div class="mt-3 pt-3 border-t border-white/20">
            <p class="text-sm text-primary-100">
                <strong>Tip:</strong> Login ke mobile app dengan email yang sama untuk test fitur absensi,
                lihat slip gaji, dan ajukan cuti.
            </p>
        </div>
    </div>
@endif
```

---

## Step 4: Verify Mobile API Auth

**File:** `app/Http/Controllers/Api/V1/AuthController.php`

Pastikan response login menyertakan employee data:

```php
public function login(Request $request): JsonResponse
{
    // ... validation & authentication ...

    $user = Auth::user();
    $employee = $user->employee; // Harus ada relation

    if (!$employee) {
        return response()->json([
            'success' => false,
            'message' => 'Akun Anda tidak memiliki data karyawan.',
        ], 403);
    }

    $token = $user->createToken('mobile-app')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login berhasil',
        'data' => [
            'user' => new UserResource($user),
            'employee' => new EmployeeResource($employee),
            'company' => new CompanyResource($user->company),
            'token' => $token,
        ],
    ]);
}
```

---

## Step 5: Add Tests

**File:** `tests/Feature/Auth/TrialRegistrationTest.php`

```php
<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'hr-manager']);
    Role::create(['name' => 'employee']);
});

describe('Trial Registration', function () {

    it('creates user with multiple roles on registration', function () {
        $response = $this->post('/register', [
            'company_name' => 'PT Test Company',
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'phone' => '08123456789',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');

        $user = User::where('email', 'john@test.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->hasRole('admin'))->toBeTrue()
            ->and($user->hasRole('hr-manager'))->toBeTrue()
            ->and($user->hasRole('employee'))->toBeTrue();
    });

    it('creates employee record for registering user', function () {
        $this->post('/register', [
            'company_name' => 'PT Test Company',
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'john@test.com')->first();
        $employee = Employee::where('user_id', $user->id)->first();

        expect($employee)->not->toBeNull()
            ->and($employee->email)->toBe('john@test.com')
            ->and($employee->first_name)->toBe('John');
    });

    it('enables demo mode for new company', function () {
        $this->post('/register', [
            'company_name' => 'PT Test Company',
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $company = Company::where('email', 'john@test.com')->first();

        expect($company->is_demo_mode)->toBeTrue()
            ->and($company->subscription_plan)->toBe('trial')
            ->and($company->subscription_ends_at)->not->toBeNull();
    });

    it('allows user to login via mobile API after registration', function () {
        // Register via web
        $this->post('/register', [
            'company_name' => 'PT Test Company',
            'name' => 'John Doe',
            'email' => 'john@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // Login via API
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@test.com',
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user',
                    'employee',
                    'token',
                ],
            ]);
    });

});
```

---

## Step 6: Route Updates

**File:** `routes/web.php`

```php
// Demo settings routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/demo/settings', [DemoSettingController::class, 'index'])
        ->name('demo.settings');
    Route::post('/demo/switch-to-production', [DemoSettingController::class, 'switchToProduction'])
        ->name('demo.switch-to-production');
    Route::post('/demo/reset', [DemoSettingController::class, 'resetDemoData'])
        ->name('demo.reset');
});
```

---

## Step 7: Create Mobile App Config & Download Section

**File:** `config/gajipro.php` (create new)

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mobile App Download Links
    |--------------------------------------------------------------------------
    |
    | Links untuk download aplikasi mobile GajiPro.
    | Update via .env untuk kemudahan deployment.
    |
    */
    'mobile_app' => [
        'android' => [
            'play_store' => env('MOBILE_ANDROID_PLAYSTORE', ''),
            'direct_apk' => env('MOBILE_ANDROID_APK', ''),
        ],
        'ios' => [
            'app_store' => env('MOBILE_IOS_APPSTORE', ''),
            'testflight' => env('MOBILE_IOS_TESTFLIGHT', ''),
        ],
        'version' => env('MOBILE_APP_VERSION', '1.0.0'),
        'min_version' => env('MOBILE_APP_MIN_VERSION', '1.0.0'),
    ],
];
```

**File:** `.env.example` (add)

```env
# Mobile App Links
MOBILE_ANDROID_PLAYSTORE=
MOBILE_ANDROID_APK=
MOBILE_IOS_APPSTORE=
MOBILE_IOS_TESTFLIGHT=
MOBILE_APP_VERSION=1.0.0
MOBILE_APP_MIN_VERSION=1.0.0
```

**File:** `resources/views/components/mobile-app-download.blade.php` (create new)

```blade
@props(['class' => ''])

@php
    $androidPlayStore = config('gajipro.mobile_app.android.play_store');
    $androidApk = config('gajipro.mobile_app.android.direct_apk');
    $iosAppStore = config('gajipro.mobile_app.ios.app_store');
    $iosTestFlight = config('gajipro.mobile_app.ios.testflight');

    $hasAndroid = $androidPlayStore || $androidApk;
    $hasIos = $iosAppStore || $iosTestFlight;
@endphp

@if($hasAndroid || $hasIos)
<div {{ $attributes->merge(['class' => 'bg-white rounded-xl border border-secondary-200 p-6 ' . $class]) }}>
    <div class="flex items-center gap-3 mb-4">
        <div class="p-2 bg-primary-100 rounded-lg">
            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
        </div>
        <div>
            <h3 class="font-semibold text-secondary-900">Download Mobile App</h3>
            <p class="text-sm text-secondary-500">Absensi, slip gaji, dan pengajuan cuti dari HP</p>
        </div>
    </div>

    <div class="space-y-3">
        {{-- Android --}}
        @if($hasAndroid)
        <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-green-500" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M17.523 2.727l1.414 1.414-2.07 2.07A6.96 6.96 0 0119 11v1h3v2h-3v8h-2v-8H7v8H5v-8H2v-2h3v-1a6.96 6.96 0 012.133-5.789l-2.07-2.07 1.414-1.414 2.07 2.07A6.944 6.944 0 0112 3c1.665 0 3.207.583 4.413 1.556l2.07-2.829h1.04zM12 5a5 5 0 00-5 5v2h10v-2a5 5 0 00-5-5z"/>
                </svg>
                <span class="font-medium text-secondary-700">Android</span>
            </div>
            <div class="flex gap-2">
                @if($androidPlayStore)
                <a href="{{ $androidPlayStore }}" target="_blank"
                   class="px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition">
                    Google Play
                </a>
                @endif
                @if($androidApk)
                <a href="{{ $androidApk }}" target="_blank"
                   class="px-3 py-1.5 bg-secondary-200 hover:bg-secondary-300 text-secondary-700 text-sm font-medium rounded-lg transition">
                    Download APK
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- iOS --}}
        @if($hasIos)
        <div class="flex items-center justify-between p-3 bg-secondary-50 rounded-lg">
            <div class="flex items-center gap-3">
                <svg class="w-8 h-8 text-secondary-800" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/>
                </svg>
                <span class="font-medium text-secondary-700">iOS</span>
            </div>
            <div class="flex gap-2">
                @if($iosAppStore)
                <a href="{{ $iosAppStore }}" target="_blank"
                   class="px-3 py-1.5 bg-secondary-800 hover:bg-secondary-900 text-white text-sm font-medium rounded-lg transition">
                    App Store
                </a>
                @endif
                @if($iosTestFlight)
                <a href="{{ $iosTestFlight }}" target="_blank"
                   class="px-3 py-1.5 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition">
                    TestFlight
                </a>
                @endif
            </div>
        </div>
        @endif
    </div>

    <p class="mt-4 text-xs text-secondary-400">
        Login dengan email yang sama untuk sinkronisasi data.
    </p>
</div>
@endif
```

**Usage di Dashboard:**

```blade
{{-- resources/views/dashboard.blade.php --}}

@if($company->is_demo_mode)
    <x-demo-mode-banner :company="$company" />

    {{-- Mobile App Download Card --}}
    <x-mobile-app-download class="mb-6" />
@endif
```

---

## Execution Order

1. **Create migration (if needed)** - Verify `employees.user_id` exists
2. **Update RegisteredUserController** - Add multi-role & employee creation
3. **Update DemoDataService** - Include main user in demo data
4. **Update Demo Banner** - Enhance UI
5. **Verify API Auth** - Ensure mobile login works
6. **Add Tests** - Write test cases
7. **Create Mobile App Config** - `config/gajipro.php` + `.env` entries
8. **Create Mobile Download Component** - `components/mobile-app-download.blade.php`
9. **Run Tests** - Verify all pass

```bash
# Run tests for registration
./vendor/bin/pest tests/Feature/Auth/TrialRegistrationTest.php

# Run all auth tests
./vendor/bin/pest tests/Feature/Auth/

# Run pint
./vendor/bin/pint
```

---

## Verification Checklist

After implementation, verify:

- [ ] `php artisan migrate` runs without error
- [ ] Registration creates user with 3 roles
- [ ] Registration creates employee for user
- [ ] Demo data seeded after registration
- [ ] User can see attendance history (30 days)
- [ ] User can see payslip (1-3 months)
- [ ] User can see leave balance
- [ ] Mobile API login returns employee data
- [ ] Demo banner shows on dashboard
- [ ] Mobile app download links show (if configured)
- [ ] Reset data works correctly

---

## Quick Setup: Mobile App Links

Setelah app di-publish, update `.env`:

```env
# Google Play (setelah release)
MOBILE_ANDROID_PLAYSTORE=https://play.google.com/store/apps/details?id=net.gajipro.app

# Google Drive (untuk testing/beta)
MOBILE_ANDROID_APK=https://drive.google.com/file/d/YOUR_FILE_ID/view?usp=sharing

# App Store (jika ada)
MOBILE_IOS_APPSTORE=https://apps.apple.com/app/gajipro/id123456789

# TestFlight (untuk beta testing iOS)
MOBILE_IOS_TESTFLIGHT=https://testflight.apple.com/join/ABCD1234
```

Komponen akan otomatis menampilkan link yang tersedia.

---

*Created: 2026-02-18*
