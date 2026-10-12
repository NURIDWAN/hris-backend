<?php

namespace Database\Seeders;

use App\Models\BpjsKesSetting;
use App\Models\BpjsTkSetting;
use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\EmployeeSalaryComponent;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Pph21Setting;
use App\Models\SalaryComponent;
use App\Models\User;
use App\Services\PayrollCalculationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class CompanyDataNormalizationSeeder extends Seeder
{
    /**
     * Normalize data for all existing companies.
     * This seeder uses updateOrCreate to safely run multiple times.
     */
    public function run(): void
    {
        // Include all companies
        $companies = Company::all();

        foreach ($companies as $company) {
            $this->command->info("Normalizing data for: {$company->name} (ID: {$company->id})");

            DB::transaction(function () use ($company) {
                $this->ensurePph21Settings($company);
                $this->ensureBpjsKesSettings($company);
                $this->ensureBpjsTkSettings($company);
                $this->ensureSalaryComponents($company);
                $this->ensureLeaveTypes($company);
                $this->restoreSoftDeletedEmployees($company);
                $this->ensureEmployeeTaxData($company);
                $this->ensureEmployeeSalaries($company);
                $this->ensureLeaveBalances($company);

                // Ensure company has its own roles
                $this->ensureCompanyRoles($company);

                // Assign roles to users
                $this->ensureUserRoles($company);

                // Recalculate payroll items for all companies
                $this->recalculatePayrollItems($company);
            });

            $this->command->info("  ✓ Completed normalization for {$company->name}");
        }

        $this->command->info('All companies normalized successfully!');
    }

    protected function ensurePph21Settings(Company $company): void
    {
        Pph21Setting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'is_gross_up' => false,
                'use_ter' => true,
                'npwp_discount_rate' => 20.00,
                'effective_year' => (int) date('Y'),
            ]
        );
    }

    protected function ensureBpjsKesSettings(Company $company): void
    {
        BpjsKesSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'company_rate' => 4.00,
                'employee_rate' => 1.00,
                'min_salary_basis' => 2900000,
                'max_salary_basis' => 12000000,
                'effective_year' => (int) date('Y'),
                'is_active' => true,
            ]
        );
    }

    protected function ensureBpjsTkSettings(Company $company): void
    {
        BpjsTkSetting::updateOrCreate(
            ['company_id' => $company->id],
            [
                'jht_company_rate' => 3.70,
                'jht_employee_rate' => 2.00,
                'jht_enabled' => true,
                'jkk_rate' => 0.24,
                'jkk_risk_level' => 'very_low',
                'jkk_enabled' => true,
                'jkm_rate' => 0.30,
                'jkm_enabled' => true,
                'jp_company_rate' => 2.00,
                'jp_employee_rate' => 1.00,
                'jp_max_salary' => 10042300,
                'jp_enabled' => true,
                'effective_year' => (int) date('Y'),
                'is_active' => true,
            ]
        );
    }

    protected function ensureSalaryComponents(Company $company): void
    {
        $components = [
            [
                'name' => 'Tunjangan Makan',
                'code' => 'TJ-MAKAN',
                'type' => 'earning',
                'category' => 'benefit',
                'is_taxable' => true,
                'default_amount' => 500000,
            ],
            [
                'name' => 'Tunjangan Transport',
                'code' => 'TJ-TRANS',
                'type' => 'earning',
                'category' => 'benefit',
                'is_taxable' => true,
                'default_amount' => 500000,
            ],
            [
                'name' => 'Tunjangan Kesehatan',
                'code' => 'TJ-KES',
                'type' => 'earning',
                'category' => 'benefit',
                'is_taxable' => false,
                'default_amount' => 300000,
            ],
            [
                'name' => 'Bonus Kinerja',
                'code' => 'BONUS',
                'type' => 'earning',
                'category' => 'variable',
                'is_taxable' => true,
                'default_amount' => 0,
            ],
            [
                'name' => 'Potongan Pinjaman',
                'code' => 'POT-PINJAM',
                'type' => 'deduction',
                'category' => 'loan',
                'is_taxable' => false,
                'default_amount' => 0,
            ],
        ];

        foreach ($components as $index => $comp) {
            // Check if exists (including soft-deleted)
            $existing = SalaryComponent::withTrashed()
                ->where('company_id', $company->id)
                ->where('code', $comp['code'])
                ->first();

            if ($existing) {
                // Restore if soft-deleted and update
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'name' => $comp['name'],
                    'type' => $comp['type'],
                    'category' => $comp['category'],
                    'calculation_type' => 'fixed',
                    'default_amount' => $comp['default_amount'],
                    'is_taxable' => $comp['is_taxable'],
                    'is_active' => true,
                    'is_mandatory' => false,
                    'sort_order' => $index + 1,
                ]);
            } else {
                SalaryComponent::create([
                    'company_id' => $company->id,
                    'code' => $comp['code'],
                    'name' => $comp['name'],
                    'type' => $comp['type'],
                    'category' => $comp['category'],
                    'calculation_type' => 'fixed',
                    'default_amount' => $comp['default_amount'],
                    'is_taxable' => $comp['is_taxable'],
                    'is_active' => true,
                    'is_mandatory' => false,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    protected function ensureLeaveTypes(Company $company): void
    {
        $types = [
            ['name' => 'Cuti Tahunan', 'code' => 'CT', 'default_days' => 12, 'is_paid' => true, 'is_annual' => true],
            ['name' => 'Cuti Sakit', 'code' => 'CS', 'default_days' => 14, 'is_paid' => true, 'is_annual' => false],
            ['name' => 'Cuti Melahirkan', 'code' => 'CM', 'default_days' => 90, 'is_paid' => true, 'is_annual' => false],
            ['name' => 'Izin Tidak Dibayar', 'code' => 'ITD', 'default_days' => 30, 'is_paid' => false, 'is_annual' => false],
            ['name' => 'Cuti Menikah', 'code' => 'CMK', 'default_days' => 3, 'is_paid' => true, 'is_annual' => false],
        ];

        foreach ($types as $type) {
            // Check if exists (including soft-deleted)
            $existing = LeaveType::withTrashed()
                ->where('company_id', $company->id)
                ->where('code', $type['code'])
                ->first();

            if ($existing) {
                // Restore if soft-deleted and update
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $existing->update([
                    'name' => $type['name'],
                    'default_days' => $type['default_days'],
                    'is_paid' => $type['is_paid'],
                    'is_annual' => $type['is_annual'],
                    'requires_approval' => true,
                    'is_active' => true,
                ]);
            } else {
                LeaveType::create([
                    'company_id' => $company->id,
                    'code' => $type['code'],
                    'name' => $type['name'],
                    'default_days' => $type['default_days'],
                    'is_paid' => $type['is_paid'],
                    'is_annual' => $type['is_annual'],
                    'requires_approval' => true,
                    'is_active' => true,
                ]);
            }
        }
    }

    protected function ensureEmployeeSalaries(Company $company): void
    {
        $employees = Employee::where('company_id', $company->id)
            ->whereDoesntHave('salaries', function ($q) {
                $q->where('is_active', true);
            })
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $salaryComponents = SalaryComponent::where('company_id', $company->id)
            ->where('type', 'earning')
            ->where('is_active', true)
            ->get();

        foreach ($employees as $employee) {
            $baseSalary = $employee->base_salary ?? $employee->position?->base_salary ?? 5000000;

            // Create employee salary
            $salary = EmployeeSalary::create([
                'company_id' => $company->id,
                'employee_id' => $employee->id,
                'basic_salary' => $baseSalary,
                'effective_date' => $employee->hire_date ?? now(),
                'payment_method' => 'transfer',
                'bank_name' => $employee->bank_name ?? 'BCA',
                'bank_account_number' => $employee->bank_account_number ?? $this->generateBankAccount(),
                'bank_account_name' => $employee->bank_account_name ?? $employee->full_name,
                'is_active' => true,
            ]);

            // Assign salary components
            foreach ($salaryComponents as $component) {
                if ($component->default_amount > 0) {
                    EmployeeSalaryComponent::create([
                        'employee_salary_id' => $salary->id,
                        'salary_component_id' => $component->id,
                        'amount' => $component->default_amount,
                    ]);
                }
            }

            // Update employee tax status if missing
            if (empty($employee->tax_status)) {
                $employee->update([
                    'tax_status' => $this->randomTaxStatus(),
                ]);
            }

            // Update NPWP if missing
            if (empty($employee->npwp)) {
                $employee->update([
                    'npwp' => $this->generateNpwp(),
                ]);
            }
        }
    }

    /**
     * Ensure all employees have tax_status and NPWP
     */
    protected function ensureEmployeeTaxData(Company $company): void
    {
        $employees = Employee::where('company_id', $company->id)->get();

        foreach ($employees as $employee) {
            $updates = [];

            if (empty($employee->tax_status)) {
                $updates['tax_status'] = $this->randomTaxStatus();
            }

            if (empty($employee->npwp)) {
                $updates['npwp'] = $this->generateNpwp();
            }

            if (! empty($updates)) {
                $employee->update($updates);
            }
        }
    }

    /**
     * Generate random tax status
     */
    protected function randomTaxStatus(): string
    {
        $statuses = ['TK/0', 'K/0', 'K/1', 'K/2', 'K/3'];

        return $statuses[array_rand($statuses)];
    }

    /**
     * Generate random NPWP number
     */
    protected function generateNpwp(): string
    {
        $npwp = '';
        $pattern = '##.###.###.#-###.###';

        for ($i = 0; $i < strlen($pattern); $i++) {
            if ($pattern[$i] === '#') {
                $npwp .= random_int(0, 9);
            } else {
                $npwp .= $pattern[$i];
            }
        }

        return $npwp;
    }

    /**
     * Generate random bank account number
     */
    protected function generateBankAccount(): string
    {
        $account = '';
        for ($i = 0; $i < 10; $i++) {
            $account .= random_int(0, 9);
        }

        return $account;
    }

    protected function ensureLeaveBalances(Company $company): void
    {
        $employees = Employee::where('company_id', $company->id)->get();
        $leaveTypes = LeaveType::where('company_id', $company->id)->get();
        $currentYear = (int) date('Y');

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $leaveType) {
                // Check if exists (including soft-deleted)
                $existing = LeaveBalance::withTrashed()
                    ->where('company_id', $company->id)
                    ->where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $currentYear)
                    ->first();

                if ($existing) {
                    if ($existing->trashed()) {
                        $existing->restore();
                    }
                } else {
                    LeaveBalance::create([
                        'company_id' => $company->id,
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $currentYear,
                        'entitled_days' => $leaveType->default_days ?? 0,
                        'used_days' => 0,
                        'pending_days' => 0,
                        'carried_forward_days' => 0,
                        'adjustment_days' => 0,
                    ]);
                }
            }
        }
    }

    protected function ensureUserRoles(Company $company): void
    {
        // Set team context for Spatie Permission (required for teams mode)
        setPermissionsTeamId($company->id);

        // Get all users for this company
        $users = User::where('company_id', $company->id)->get();

        $reassignedCount = 0;
        foreach ($users as $user) {
            // Check if user already has a company-specific role (role with company_id matching)
            $hasCompanySpecificRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', User::class)
                ->where('roles.company_id', $company->id)
                ->exists();

            if ($hasCompanySpecificRole) {
                continue;
            }

            // Get current role name from any assignment (global or with company_id in model_has_roles)
            $currentRoleAssignment = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', User::class)
                ->select('roles.name as role_name')
                ->first();

            $roleName = $currentRoleAssignment?->role_name;

            if (! $roleName) {
                // No role - determine appropriate role
                $hasEmployee = Employee::where('user_id', $user->id)->exists();

                if ($hasEmployee) {
                    $roleName = 'employee';
                } else {
                    $firstUser = User::where('company_id', $company->id)
                        ->orderBy('id')
                        ->first();
                    $roleName = ($user->id === $firstUser?->id) ? 'admin' : 'employee';
                }
            }

            // Get company-specific role
            $companyRole = Role::where('name', $roleName)
                ->where('company_id', $company->id)
                ->first();

            if ($companyRole) {
                // Delete old role assignments for this user
                DB::table('model_has_roles')
                    ->where('model_id', $user->id)
                    ->where('model_type', User::class)
                    ->delete();

                // Assign company-specific role
                DB::table('model_has_roles')->insert([
                    'role_id' => $companyRole->id,
                    'model_type' => User::class,
                    'model_id' => $user->id,
                    'company_id' => $company->id,
                ]);
                $reassignedCount++;
            }
        }

        if ($reassignedCount > 0) {
            $this->command->info("    → Reassigned {$reassignedCount} users to company roles");
        }
    }

    protected function recalculatePayrollItems(Company $company): void
    {
        $calculationService = app(PayrollCalculationService::class);

        // Get all payroll items for this company that are not yet paid
        $payrollItems = PayrollItem::whereHas('payroll', function ($q) use ($company) {
            $q->where('company_id', $company->id)
                ->whereIn('status', ['draft', 'calculated', 'approved']);
        })
            ->with(['employee', 'details'])
            ->get();

        if ($payrollItems->isEmpty()) {
            return;
        }

        $recalculatedCount = 0;

        foreach ($payrollItems as $item) {
            $employee = $item->employee;

            if (! $employee) {
                continue;
            }

            $grossSalary = (float) $item->gross_salary;
            $basicSalary = (float) $item->basic_salary;

            // Calculate earnings from details (excluding BPJS)
            $totalEarnings = $item->details()
                ->where('type', 'earning')
                ->sum('amount');

            $taxableEarnings = $item->details()
                ->where('type', 'earning')
                ->where('is_taxable', true)
                ->sum('amount');

            // Calculate using the service
            $calculation = $calculationService->calculate($employee, [
                'gross_salary' => $grossSalary,
                'basic_salary' => $basicSalary,
                'total_earnings' => $totalEarnings,
                'taxable_earnings' => $taxableEarnings,
                'total_deductions' => 0,
            ], $company->id);

            $pph21 = $calculation['pph21'];
            $bpjsKesEmployee = $calculation['bpjs_kes_employee'];
            $bpjsTkEmployee = $calculation['bpjs_tk_employee'];
            $bpjsTkDetails = $calculation['bpjs_tk_details'];

            // Remove old BPJS details
            $item->details()
                ->where('category', 'bpjs')
                ->delete();

            // Add new BPJS details
            if ($bpjsKesEmployee > 0) {
                $item->addDetail(
                    null,
                    'BPJS Kesehatan',
                    'BPJS-KES',
                    'deduction',
                    'bpjs',
                    $bpjsKesEmployee,
                    false
                );
            }

            if (($bpjsTkDetails['jht_employee'] ?? 0) > 0) {
                $item->addDetail(
                    null,
                    'BPJS JHT',
                    'BPJS-JHT',
                    'deduction',
                    'bpjs',
                    $bpjsTkDetails['jht_employee'],
                    false
                );
            }

            if (($bpjsTkDetails['jp_employee'] ?? 0) > 0) {
                $item->addDetail(
                    null,
                    'BPJS JP',
                    'BPJS-JP',
                    'deduction',
                    'bpjs',
                    $bpjsTkDetails['jp_employee'],
                    false
                );
            }

            // Calculate total deductions from non-BPJS component deductions + BPJS
            $componentDeductions = $item->details()
                ->where('type', 'deduction')
                ->where('category', '!=', 'bpjs')
                ->sum('amount');

            $totalDeductions = $componentDeductions + $bpjsKesEmployee + $bpjsTkEmployee;

            // Update payroll item
            $item->update([
                'tax_amount' => $pph21,
                'total_deductions' => $totalDeductions,
                'net_salary' => $grossSalary - $totalDeductions - $pph21,
            ]);

            $recalculatedCount++;
        }

        if ($recalculatedCount > 0) {
            $this->command->info("    → Recalculated {$recalculatedCount} payroll items");
        }
    }

    /**
     * Restore soft-deleted employees that have active user accounts
     */
    protected function restoreSoftDeletedEmployees(Company $company): void
    {
        // Get soft-deleted employees for this company that have active users
        $deletedEmployees = Employee::withTrashed()
            ->where('company_id', $company->id)
            ->whereNotNull('deleted_at')
            ->whereHas('user', function ($q) {
                $q->whereNull('deleted_at');
            })
            ->get();

        foreach ($deletedEmployees as $employee) {
            $employee->restore();
        }

        if ($deletedEmployees->count() > 0) {
            $this->command->info("    → Restored {$deletedEmployees->count()} soft-deleted employees");
        }
    }

    /**
     * Ensure company has its own roles with permissions
     */
    protected function ensureCompanyRoles(Company $company): void
    {
        // Set team context
        setPermissionsTeamId($company->id);

        // Define roles and their permissions
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

        $createdCount = 0;
        foreach ($rolesPermissions as $roleName => $permissions) {
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
                $createdCount++;
            }

            // Sync permissions
            $role->syncPermissions($permissions);
        }

        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if ($createdCount > 0) {
            $this->command->info("    → Created {$createdCount} roles for company");
        }
    }

    /**
     * Reassign user roles to company-specific roles
     */
    protected function reassignUserRolesToCompanyRoles(Company $company): void
    {
        $users = User::where('company_id', $company->id)->get();

        foreach ($users as $user) {
            // Get current role names
            $currentRoles = $user->getRoleNames()->toArray();

            if (empty($currentRoles)) {
                continue;
            }

            // Set team context
            setPermissionsTeamId($company->id);

            // Find company-specific roles with same names
            foreach ($currentRoles as $roleName) {
                $companyRole = Role::where('name', $roleName)
                    ->where('company_id', $company->id)
                    ->first();

                if ($companyRole) {
                    // Remove old role assignment and assign company-specific role
                    DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->where('model_type', User::class)
                        ->where('role_id', '!=', $companyRole->id)
                        ->whereIn('role_id', function ($query) use ($roleName) {
                            $query->select('id')
                                ->from('roles')
                                ->where('name', $roleName);
                        })
                        ->delete();

                    // Ensure user has company role
                    if (! $user->hasRole($roleName)) {
                        $user->assignRole($companyRole);
                    }
                }
            }
        }
    }
}
