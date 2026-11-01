<?php

namespace App\Http\Controllers\Api\V1\Public;

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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PublicTenantRegistrationController extends Controller
{
    /**
     * Handle tenant registration from external frontend.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
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
        if ($planSlug !== 'trial' && $planSlug !== 'starter') {
            $selectedPlan = SubscriptionPlan::where('slug', $planSlug)->where('is_active', true)->first();
            if ($selectedPlan) {
                $price = $billingCycle === 'yearly' ? $selectedPlan->price_yearly : $selectedPlan->price_monthly;
                $isPaidPlan = $price > 0;
            }
        }

        try {
            $result = DB::transaction(function () use ($validated, $selectedPlan, $isPaidPlan, $billingCycle, $gateway) {
                $subscriptionPlan = $isPaidPlan ? $selectedPlan->slug : ($validated['plan'] ?? 'trial');
                $subscriptionEndsAt = $isPaidPlan ? null : now()->addDays(14);
                $maxEmployees = $isPaidPlan ? ($selectedPlan->max_employees ?: 999) : 10;

                // 1. Create company
                $company = Company::create([
                    'name' => $validated['company_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'subscription_plan' => $subscriptionPlan,
                    'subscription_ends_at' => $subscriptionEndsAt,
                    'max_employees' => $maxEmployees,
                    'is_active' => true,
                    'is_demo_mode' => ! $isPaidPlan,
                    'demo_started_at' => $isPaidPlan ? null : now(),
                ]);

                // 2. Set team context for role creation
                setPermissionsTeamId($company->id);

                // 3. Create company-specific roles with permissions
                $this->createCompanyRoles($company);

                // 4. Create user as company admin
                $user = User::create([
                    'company_id' => $company->id,
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => $validated['password'],
                    'is_active' => true,
                ]);

                // 5. Assign roles
                $user->assignRole(['admin', 'hr-manager', 'employee']);

                // 6. Create Headquarters Office
                $officeLocation = $this->createOfficeLocation($company);

                // 7. Create Employee Record
                $this->createEmployeeForUser($user, $company, $officeLocation);

                // 8. Seed company setup & transaction data
                if (! app()->runningUnitTests()) {
                    try {
                        $demoService = new DemoDataService;
                        $demoService->seedCompanySetup($company);
                        $demoService->seedEmployeeTransactionData($company);
                    } catch (\Throwable $seederError) {
                        Log::warning('Demo data seeding skipped or partial', ['error' => $seederError->getMessage()]);
                    }
                }

                // 9. Create subscription and payment if paid plan
                $payment = null;
                $redirectUrl = null;

                if ($isPaidPlan && $selectedPlan) {
                    $price = $billingCycle === 'yearly' ? $selectedPlan->price_yearly : $selectedPlan->price_monthly;
                    $endsAt = $billingCycle === 'yearly' ? now()->addYear() : now()->addMonth();

                    $subscription = Subscription::create([
                        'company_id' => $company->id,
                        'subscription_plan_id' => $selectedPlan->id,
                        'status' => Subscription::STATUS_PENDING,
                        'billing_cycle' => $billingCycle,
                        'started_at' => now(),
                        'ends_at' => $endsAt,
                    ]);

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

                    if ($gateway !== 'manual') {
                        try {
                            $paymentService = app(PaymentGatewayService::class);
                            $paymentService->setGateway($gateway);

                            $gatewayResult = $paymentService->createTransaction([
                                'order_id' => $payment->payment_number,
                                'amount' => (int) $payment->amount,
                                'customer_name' => $user->name,
                                'customer_email' => $user->email,
                                'description' => "Langganan {$selectedPlan->name} ({$billingCycle})",
                            ]);

                            $payment->update([
                                'gateway_order_id' => $payment->payment_number,
                                'gateway_transaction_id' => $gatewayResult['invoice_id'] ?? $gatewayResult['token'] ?? null,
                            ]);

                            $redirectUrl = $gatewayResult['invoice_url'] ?? $gatewayResult['redirect_url'] ?? null;
                        } catch (\Throwable $e) {
                            Log::error('Registration payment gateway initiation failed', [
                                'error' => $e->getMessage(),
                                'payment_id' => $payment->id,
                            ]);
                        }
                    }
                }

                // Create Sanctum Token
                $token = $user->createToken('hris-admin-token')->plainTextToken;

                return [
                    'user' => $user,
                    'company' => $company,
                    'token' => $token,
                    'isPaidPlan' => $isPaidPlan,
                    'payment' => $payment,
                    'redirectUrl' => $redirectUrl,
                ];
            });

            event(new Registered($result['user']));

            return response()->json([
                'success' => true,
                'message' => 'Pendaftaran perusahaan berhasil! Selamat datang di GajiPro.',
                'data' => [
                    'token' => $result['token'],
                    'user' => [
                        'id' => $result['user']->id,
                        'name' => $result['user']->name,
                        'email' => $result['user']->email,
                        'roles' => ['admin', 'hr-manager', 'employee'],
                    ],
                    'company' => [
                        'id' => $result['company']->id,
                        'name' => $result['company']->name,
                        'subscription_plan' => $result['company']->subscription_plan,
                        'is_demo_mode' => (bool) $result['company']->is_demo_mode,
                    ],
                    'is_paid_plan' => $result['isPaidPlan'],
                    'payment' => $result['payment'] ? [
                        'payment_number' => $result['payment']->payment_number,
                        'amount' => $result['payment']->amount,
                        'gateway' => $result['payment']->gateway,
                        'status' => $result['payment']->status,
                    ] : null,
                    'redirect_url' => $result['redirectUrl'],
                ],
            ], 201);

        } catch (\Throwable $e) {
            Log::error('Tenant registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat pendaftaran: '.$e->getMessage(),
            ], 500);
        }
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
        $nameParts = explode(' ', $user->name, 2);
        $firstName = $nameParts[0];
        $lastName = $nameParts[1] ?? '';

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

        $employee->officeLocations()->attach($officeLocation->id, ['is_primary' => true]);

        return $employee;
    }

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

        $allPermissions = collect($rolesPermissions)->flatten()->unique()->values();

        foreach ($allPermissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        foreach ($rolesPermissions as $roleName => $permissions) {
            $role = Role::where('name', $roleName)
                ->where('company_id', $company->id)
                ->first();

            if (! $role) {
                $roleId = DB::table('roles')->insertGetId([
                    'name' => $roleName,
                    'guard_name' => 'web',
                    'company_id' => $company->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $role = Role::find($roleId);
            }

            $role->syncPermissions($permissions);
        }

        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
