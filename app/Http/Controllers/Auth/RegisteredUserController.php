<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\OfficeLocation;
use App\Models\Payment;
use App\Models\Position;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\WorkSchedule;
use App\Services\DemoDataService;
use App\Services\PaymentGatewayService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'plan' => ['nullable', 'string'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
            'gateway' => ['nullable', 'string'],
        ]);

        $planSlug = $validated['plan'] ?? 'trial';
        $billingCycle = $validated['billing_cycle'] ?? 'yearly';
        $gateway = $validated['gateway'] ?? 'xendit';

        // Check if paid plan selected
        $selectedPlan = null;
        $isPaidPlan = false;
        if ($planSlug !== 'trial') {
            $selectedPlan = SubscriptionPlan::where('slug', $planSlug)->where('is_active', true)->first();
            $isPaidPlan = $selectedPlan && ($billingCycle === 'yearly' ? $selectedPlan->price_yearly : $selectedPlan->price_monthly) > 0;
        }

        $result = DB::transaction(function () use ($validated, $selectedPlan, $isPaidPlan, $billingCycle, $gateway) {
            // Determine subscription settings based on plan
            $subscriptionPlan = $isPaidPlan ? $selectedPlan->slug : 'trial';
            $subscriptionEndsAt = $isPaidPlan ? null : now()->addDays(14); // Paid plans: set after payment
            $maxEmployees = $isPaidPlan ? ($selectedPlan->max_employees ?: 999) : 10;

            // Create company
            $company = Company::create([
                'name' => $validated['company_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'subscription_plan' => $subscriptionPlan,
                'subscription_ends_at' => $subscriptionEndsAt,
                'max_employees' => $maxEmployees,
                'is_active' => true,
                'is_demo_mode' => ! $isPaidPlan, // Not demo if paid
                'demo_started_at' => $isPaidPlan ? null : now(),
            ]);

            // Set team context for role creation
            setPermissionsTeamId($company->id);

            // Create company-specific roles with permissions
            $this->createCompanyRoles($company);

            // Create user as company admin
            $user = User::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => $validated['password'],
            ]);

            // Assign multiple roles: admin, hr-manager, employee
            $user->assignRole(['admin', 'hr-manager', 'employee']);

            // Create office location (headquarters)
            $officeLocation = $this->createOfficeLocation($company);

            // Create employee record for the registered user
            $this->createEmployeeForUser($user, $company, $officeLocation);

            // Seed company setup data (departments, positions, schedules, etc.)
            if (! app()->runningUnitTests()) {
                $demoService = new DemoDataService;
                $demoService->seedCompanySetup($company);
                $demoService->seedEmployeeTransactionData($company);
            }

            // Create subscription and payment for paid plans
            $payment = null;
            if ($isPaidPlan) {
                $price = $billingCycle === 'yearly' ? $selectedPlan->price_yearly : $selectedPlan->price_monthly;
                $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

                // Create subscription record
                $subscription = Subscription::create([
                    'company_id' => $company->id,
                    'subscription_plan_id' => $selectedPlan->id,
                    'status' => Subscription::STATUS_PENDING,
                    'billing_cycle' => $billingCycle,
                    'started_at' => now(),
                    'ends_at' => $endsAt,
                ]);

                // Create payment record
                $payment = Payment::create([
                    'company_id' => $company->id,
                    'subscription_id' => $subscription->id,
                    'gateway' => $gateway,
                    'amount' => $price,
                    'fee' => 0,
                    'net_amount' => $price,
                    'currency' => 'IDR',
                    'status' => Payment::STATUS_PENDING,
                    'description' => "Langganan {$selectedPlan->name} ({$billingCycle})",
                    'billing_cycle' => $billingCycle,
                    'expired_at' => now()->addDay(),
                ]);
            }

            return [
                'user' => $user,
                'company' => $company,
                'payment' => $payment,
                'isPaidPlan' => $isPaidPlan,
                'gateway' => $gateway,
                'selectedPlan' => $selectedPlan,
                'billingCycle' => $billingCycle,
            ];
        });

        event(new Registered($result['user']));
        Auth::login($result['user']);

        // Handle paid plan - redirect to payment gateway
        if ($result['isPaidPlan'] && $result['payment']) {
            $payment = $result['payment'];

            // Handle manual payment
            if ($result['gateway'] === 'manual') {
                return redirect()->route('settings.billing.invoice', $payment)
                    ->with('success', 'Akun berhasil dibuat! Silakan transfer ke rekening yang tertera dan konfirmasi pembayaran.');
            }

            // Handle online payment gateway
            try {
                $paymentService = app(PaymentGatewayService::class);
                $paymentService->setGateway($result['gateway']);

                $gatewayResult = $paymentService->createTransaction([
                    'order_id' => $payment->payment_number,
                    'amount' => (int) $payment->amount,
                    'customer_name' => $result['user']->name,
                    'customer_email' => $result['user']->email,
                    'description' => "Langganan {$result['selectedPlan']->name} ({$result['billingCycle']})",
                ]);

                // Update payment with gateway info
                $payment->update([
                    'gateway_order_id' => $payment->payment_number,
                    'gateway_transaction_id' => $gatewayResult['invoice_id'] ?? $gatewayResult['token'] ?? null,
                ]);

                // Redirect to payment page
                $redirectUrl = $gatewayResult['invoice_url'] ?? $gatewayResult['redirect_url'] ?? null;
                if ($redirectUrl) {
                    return redirect($redirectUrl);
                }

            } catch (\Exception $e) {
                Log::error('Registration payment failed', [
                    'error' => $e->getMessage(),
                    'payment_id' => $payment->id,
                ]);

                $payment->update(['status' => Payment::STATUS_FAILED]);

                return redirect()->route('dashboard')
                    ->with('warning', 'Akun berhasil dibuat! Namun pembayaran gagal diproses. Silakan coba lagi di menu Billing.');
            }
        }

        return redirect()->intended('/dashboard');
    }

    /**
     * Create headquarters office location for the company.
     */
    protected function createOfficeLocation(Company $company): OfficeLocation
    {
        return OfficeLocation::create([
            'company_id' => $company->id,
            'name' => 'Kantor Pusat',
            'code' => 'HQ',
            'address' => 'Alamat kantor pusat',
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
            'radius' => 100,
            'is_active' => true,
            'is_headquarters' => true,
        ]);
    }

    /**
     * Create employee record for the registered user.
     */
    protected function createEmployeeForUser(User $user, Company $company, OfficeLocation $officeLocation): Employee
    {
        // Parse name into first and last name
        $nameParts = explode(' ', $user->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

        // Get or create default department and position
        $department = $this->getOrCreateDefaultDepartment($company);
        $position = $this->getOrCreateDefaultPosition($company, $department);
        $workSchedule = $this->getOrCreateDefaultWorkSchedule($company);

        $employee = Employee::create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'work_schedule_id' => $workSchedule->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $user->email,
            'phone' => $user->phone,
            'hire_date' => now(),
            'employment_status' => 'permanent',
            'base_salary' => $position->base_salary ?? 10000000,
            'is_active' => true,
        ]);

        // Assign employee to office location
        $employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

        return $employee;
    }

    /**
     * Get or create default department for the company.
     */
    protected function getOrCreateDefaultDepartment(Company $company): Department
    {
        return Department::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'MGT',
            ],
            [
                'name' => 'Management',
                'description' => 'Manajemen perusahaan',
                'is_active' => true,
            ]
        );
    }

    /**
     * Get or create default position for the company.
     */
    protected function getOrCreateDefaultPosition(Company $company, Department $department): Position
    {
        return Position::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'ADMIN',
            ],
            [
                'department_id' => $department->id,
                'name' => 'Administrator',
                'level' => 3,
                'base_salary' => 10000000,
                'description' => 'Administrator perusahaan',
                'is_active' => true,
            ]
        );
    }

    /**
     * Get or create default work schedule for the company.
     */
    protected function getOrCreateDefaultWorkSchedule(Company $company): WorkSchedule
    {
        return WorkSchedule::firstOrCreate(
            [
                'company_id' => $company->id,
                'code' => 'WS-DEFAULT',
            ],
            [
                'name' => 'Jam Kantor Normal',
                'start_time' => '08:00',
                'end_time' => '17:00',
                'break_start' => '12:00',
                'break_end' => '13:00',
                'break_duration' => 60,
                'working_hours' => 8,
                'working_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                'late_tolerance' => 15,
                'early_leave_tolerance' => 15,
                'is_flexible' => false,
                'is_default' => true,
                'is_active' => true,
            ]
        );
    }

    /**
     * Create company-specific roles with permissions.
     */
    protected function createCompanyRoles(Company $company): void
    {
        $rolesPermissions = [
            'admin' => [
                'view employees', 'create employees', 'edit employees', 'delete employees', 'import employees', 'export employees',
                'view attendance', 'create attendance', 'edit attendance', 'approve attendance', 'export attendance',
                'view leaves', 'create leaves', 'edit leaves', 'approve leaves', 'manage leave types',
                'view payroll', 'create payroll', 'edit payroll', 'process payroll', 'approve payroll', 'export payroll',
                'view salary components', 'create salary components', 'edit salary components', 'delete salary components',
                'view reports', 'export reports',
                'view settings', 'edit settings',
                'view users', 'create users', 'edit users', 'delete users', 'manage roles',
            ],
            'hr-manager' => [
                'view employees', 'create employees', 'edit employees', 'import employees', 'export employees',
                'view attendance', 'create attendance', 'edit attendance', 'approve attendance', 'export attendance',
                'view leaves', 'create leaves', 'edit leaves', 'approve leaves', 'manage leave types',
                'view reports', 'export reports',
            ],
            'payroll-manager' => [
                'view employees',
                'view attendance', 'export attendance',
                'view leaves',
                'view payroll', 'create payroll', 'edit payroll', 'process payroll', 'approve payroll', 'export payroll',
                'view salary components', 'create salary components', 'edit salary components', 'delete salary components',
                'view reports', 'export reports',
            ],
            'employee' => [
                'view attendance',
                'create attendance',
                'view leaves',
                'create leaves',
            ],
        ];

        // Collect all unique permissions needed
        $allPermissions = collect($rolesPermissions)->flatten()->unique()->values();

        // Ensure all permissions exist
        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach ($rolesPermissions as $roleName => $permissions) {
            // Check if company-specific role exists
            $role = Role::where('name', $roleName)
                ->where('company_id', $company->id)
                ->first();

            if (! $role) {
                // Insert directly to bypass Spatie unique validation (which doesn't account for company_id)
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'company_id' => $company->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $role = Role::find($roleId);
            }

            // Sync permissions
            $role->syncPermissions($permissions);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
