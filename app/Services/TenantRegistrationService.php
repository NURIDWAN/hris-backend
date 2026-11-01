<?php

namespace App\Services;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class TenantRegistrationService
{
    public function __construct(
        protected PaymentGatewayService $paymentGatewayService,
        protected DemoDataService $demoDataService
    ) {}

    /**
     * Register a new tenant with admin user, initial master data, and optional subscription.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function registerTenant(array $data): array
    {
        $planSlug = $data['plan'] ?? 'trial';
        $billingCycle = $data['billing_cycle'] ?? 'yearly';
        $gateway = $data['gateway'] ?? 'xendit';

        // Check if paid plan selected
        $selectedPlan = null;
        $isPaidPlan = false;
        if ($planSlug !== 'trial') {
            $selectedPlan = SubscriptionPlan::where('slug', $planSlug)->where('is_active', true)->first();
            $isPaidPlan = $selectedPlan && ($billingCycle === 'yearly' ? $selectedPlan->price_yearly : $selectedPlan->price_monthly) > 0;
        }

        $result = DB::transaction(function () use ($data, $selectedPlan, $isPaidPlan, $billingCycle, $gateway) {
            $subscriptionPlan = $isPaidPlan ? $selectedPlan->slug : 'trial';
            $subscriptionEndsAt = $isPaidPlan ? null : now()->addDays(14);
            $maxEmployees = $isPaidPlan ? ($selectedPlan->max_employees ?: 999) : 10;

            // 1. Create company
            $company = Company::create([
                'name' => $data['company_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
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
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'],
            ]);

            // Assign multiple roles: admin, hr-manager, employee
            $user->assignRole(['admin', 'hr-manager', 'employee']);

            // 5. Create office location (headquarters)
            $officeLocation = $this->createOfficeLocation($company);

            // 6. Create employee record for the registered user
            $this->createEmployeeForUser($user, $company, $officeLocation);

            // 7. Seed company setup data (departments, positions, schedules, etc.)
            if (! app()->runningUnitTests()) {
                $this->demoDataService->seedCompanySetup($company);
                $this->demoDataService->seedEmployeeTransactionData($company);
            }

            // 8. Create subscription and payment for paid plans
            $payment = null;
            $subscription = null;
            if ($isPaidPlan) {
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
            }

            return [
                'user' => $user,
                'company' => $company,
                'subscription' => $subscription,
                'payment' => $payment,
                'isPaidPlan' => $isPaidPlan,
                'gateway' => $gateway,
                'selectedPlan' => $selectedPlan,
                'billingCycle' => $billingCycle,
            ];
        });

        // 9. Handle paid plan payment gateway transaction
        $gatewayResult = null;
        if ($result['isPaidPlan'] && $result['payment'] && in_array($result['gateway'], ['xendit', 'midtrans'])) {
            try {
                $this->paymentGatewayService->setGateway($result['gateway']);
                $gatewayResult = $this->paymentGatewayService->createTransaction([
                    'order_id' => $result['payment']->payment_number,
                    'amount' => (int) $result['payment']->amount,
                    'customer_name' => $result['user']->name,
                    'customer_email' => $result['user']->email,
                    'description' => "Langganan {$result['selectedPlan']->name} ({$result['billingCycle']})",
                ]);

                $result['payment']->update([
                    'gateway_order_id' => $result['payment']->payment_number,
                    'gateway_transaction_id' => $gatewayResult['invoice_id'] ?? $gatewayResult['token'] ?? null,
                ]);
            } catch (\Exception $e) {
                Log::error('Registration payment gateway transaction failed', [
                    'error' => $e->getMessage(),
                    'payment_id' => $result['payment']->id,
                ]);

                $result['payment']->update(['status' => Payment::STATUS_FAILED]);
            }
        }

        $result['gatewayResult'] = $gatewayResult;

        return $result;
    }

    /**
     * Create headquarters office location for the company.
     */
    public function createOfficeLocation(Company $company): OfficeLocation
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
    public function createEmployeeForUser(User $user, Company $company, OfficeLocation $officeLocation): Employee
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

    public function getOrCreateDefaultDepartment(Company $company): Department
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

    public function getOrCreateDefaultPosition(Company $company, Department $department): Position
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

    public function getOrCreateDefaultWorkSchedule(Company $company): WorkSchedule
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

    public function createCompanyRoles(Company $company): void
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
