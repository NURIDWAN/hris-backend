<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\SalaryComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Admin Payroll API', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        $this->dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Finance',
            'code' => 'FIN',
        ]);

        $this->pos = Position::create([
            'company_id' => $this->company->id,
            'department_id' => $this->dept->id,
            'name' => 'Accountant',
            'code' => 'ACC',
        ]);

        $empUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $empUser->id,
            'department_id' => $this->dept->id,
            'position_id' => $this->pos->id,
            'employee_id' => 'EMP-FIN-01',
            'first_name' => 'Bob',
            'last_name' => 'Finance',
            'hire_date' => '2025-01-01',
            'employment_status' => 'permanent',
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->adminUser);
    });

    it('can manage salary components', function () {
        // Create component
        $createRes = $this->postJson('/api/v1/admin/salary-components', [
            'name' => 'Tunjangan Transportasi',
            'code' => 'TJ_TRANS',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 500000,
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'TJ_TRANS');

        $compId = $createRes->json('data.id');

        // Toggle status
        $toggleRes = $this->postJson("/api/v1/admin/salary-components/{$compId}/toggle-status");
        $toggleRes->assertStatus(200)
            ->assertJsonPath('data.is_active', false);

        // Update component
        $upRes = $this->putJson("/api/v1/admin/salary-components/{$compId}", [
            'name' => 'Tunjangan Transportasi Jabodetabek',
            'default_amount' => 600000,
        ]);
        $upRes->assertStatus(200);
        expect((float) $upRes->json('data.default_amount'))->toEqual(600000.0);

        // Delete component
        $delRes = $this->deleteJson("/api/v1/admin/salary-components/{$compId}");
        $delRes->assertStatus(200);
        $this->assertSoftDeleted('salary_components', ['id' => $compId]);
    });

    it('can manage employee salary configuration', function () {
        $component = SalaryComponent::create([
            'company_id' => $this->company->id,
            'name' => 'Uang Makan',
            'code' => 'MEAL',
            'type' => 'earning',
            'category' => 'benefit',
            'calculation_type' => 'fixed',
            'default_amount' => 20000,
        ]);

        $res = $this->postJson('/api/v1/admin/employee-salaries', [
            'employee_id' => $this->employee->id,
            'basic_salary' => 8000000,
            'effective_date' => '2026-01-01',
            'payment_method' => 'transfer',
            'bank_name' => 'BCA',
            'bank_account_number' => '1234567890',
            'bank_account_name' => 'Bob Finance',
            'components' => [
                [
                    'salary_component_id' => $component->id,
                    'amount' => 500000,
                ],
            ],
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true);
        expect((float) $res->json('data.basic_salary'))->toEqual(8000000.0);

        $salaryId = $res->json('data.id');

        // List
        $listRes = $this->getJson('/api/v1/admin/employee-salaries');
        $listRes->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        // Show
        $showRes = $this->getJson("/api/v1/admin/employee-salaries/{$salaryId}");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.bank_name', 'BCA');
    });

    it('can manage payroll settings and full payroll lifecycle', function () {
        // 1. Payroll Settings
        $settingRes = $this->getJson('/api/v1/admin/payroll-settings');
        $settingRes->assertStatus(200);

        $updateSettingRes = $this->putJson('/api/v1/admin/payroll-settings', [
            'cutoff_day' => 20,
            'payment_day' => 25,
            'late_penalty_amount' => 25000,
        ]);
        $updateSettingRes->assertStatus(200)
            ->assertJsonPath('data.cutoff_day', 20);

        // 2. Setup employee salary
        EmployeeSalary::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'basic_salary' => 6000000,
            'effective_date' => '2026-01-01',
            'payment_method' => 'transfer',
            'bank_name' => 'BCA',
            'bank_account_number' => '99887766',
            'bank_account_name' => 'Bob',
            'is_active' => true,
        ]);

        // 3. Create Payroll draft
        $payrollRes = $this->postJson('/api/v1/admin/payrolls', [
            'name' => 'Gaji Periode Maret 2026',
            'period_month' => 3,
            'period_year' => 2026,
            'period_start' => '2026-02-21',
            'period_end' => '2026-03-20',
        ]);
        $payrollRes->assertStatus(201)
            ->assertJsonPath('data.status', 'draft');

        $payrollId = $payrollRes->json('data.id');

        // 4. Process Payroll Calculation
        $processRes = $this->postJson("/api/v1/admin/payrolls/{$payrollId}/process");
        $processRes->assertStatus(200)
            ->assertJsonPath('success', true);

        // 5. Show Payroll detail with summary
        $showRes = $this->getJson("/api/v1/admin/payrolls/{$payrollId}");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.summary.total_employees', 1);

        // 6. View Payroll Items
        $itemsRes = $this->getJson("/api/v1/admin/payroll-items?payroll_id={$payrollId}");
        $itemsRes->assertStatus(200);
        $firstItem = $itemsRes->json('data.0');
        expect($firstItem)->not->toBeNull();

        $itemId = $firstItem['id'];

        // 7. Update/Adjust Payroll Item
        $adjustRes = $this->putJson("/api/v1/admin/payroll-items/{$itemId}", [
            'notes' => 'Bonus insentif tambahan',
            'total_earnings' => 200000,
        ]);
        $adjustRes->assertStatus(200)
            ->assertJsonPath('data.notes', 'Bonus insentif tambahan');

        // 8. Approve Payroll
        $approveRes = $this->postJson("/api/v1/admin/payrolls/{$payrollId}/approve");
        $approveRes->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // 9. Export Bank
        $exportRes = $this->getJson("/api/v1/admin/payrolls/{$payrollId}/export-bank");
        $exportRes->assertStatus(200)
            ->assertJsonPath('data.total_transfers', 1);

        // 10. Mark Payroll as Paid
        $payRes = $this->postJson("/api/v1/admin/payrolls/{$payrollId}/pay");
        $payRes->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');
    });
});
