<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\PayrollItemDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create(['company_id' => $this->company->id]);
    $this->user->assignRole('admin');

    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);

    $this->employeeSalary = EmployeeSalary::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
    ]);

    $this->payroll = Payroll::factory()->calculated()->create([
        'company_id' => $this->company->id,
        'created_by' => $this->user->id,
    ]);

    $this->payrollItem = PayrollItem::factory()->calculated()->withSalary(10000000, 500000, 200000)->create([
        'payroll_id' => $this->payroll->id,
        'employee_id' => $this->employee->id,
        'employee_salary_id' => $this->employeeSalary->id,
    ]);
});

describe('Payroll Item Adjustment - Edit Page', function () {
    it('can view edit page for calculated payroll item', function () {
        $response = $this->actingAs($this->user)
            ->get(route('payroll-items.edit', $this->payrollItem));

        $response->assertOk()
            ->assertViewIs('payroll-items.edit')
            ->assertViewHas('payrollItem');
    });

    it('cannot view edit page for approved payroll item', function () {
        $this->payroll->update(['status' => 'approved']);

        $response = $this->actingAs($this->user)
            ->get(route('payroll-items.edit', $this->payrollItem));

        $response->assertRedirect(route('payroll-items.show', $this->payrollItem));
    });

    it('cannot view edit page for paid payroll item', function () {
        $this->payroll->update(['status' => 'paid']);

        $response = $this->actingAs($this->user)
            ->get(route('payroll-items.edit', $this->payrollItem));

        $response->assertRedirect(route('payroll-items.show', $this->payrollItem));
    });
});

describe('Payroll Item Adjustment - Update', function () {
    it('can update existing detail amount', function () {
        $detail = PayrollItemDetail::factory()->earning()->withAmount(500000)->create([
            'payroll_item_id' => $this->payrollItem->id,
            'category' => 'fixed',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('payroll-items.update', $this->payrollItem), [
                'basic_salary' => 10000000,
                'details' => [
                    ['id' => $detail->id, 'amount' => 750000],
                ],
            ]);

        $response->assertRedirect(route('payroll-items.show', $this->payrollItem));

        $detail->refresh();
        expect((float) $detail->amount)->toBe(750000.00);
    });

    it('can update basic salary', function () {
        $response = $this->actingAs($this->user)
            ->put(route('payroll-items.update', $this->payrollItem), [
                'basic_salary' => 12000000,
            ]);

        $response->assertRedirect(route('payroll-items.show', $this->payrollItem));

        $this->payrollItem->refresh();
        expect((float) $this->payrollItem->basic_salary)->toBe(12000000.00);
    });

    it('cannot update when payroll is approved', function () {
        $this->payroll->update(['status' => 'approved']);

        $response = $this->actingAs($this->user)
            ->put(route('payroll-items.update', $this->payrollItem), [
                'basic_salary' => 12000000,
            ]);

        $response->assertRedirect(route('payroll-items.show', $this->payrollItem));
    });
});

describe('Payroll Item Adjustment - Add Component', function () {
    it('can add new earning component', function () {
        $response = $this->actingAs($this->user)
            ->post(route('payroll-items.add-detail', $this->payrollItem), [
                'component_name' => 'Bonus Kinerja',
                'type' => 'earning',
                'category' => 'variable',
                'amount' => 1000000,
                'is_taxable' => true,
            ]);

        $response->assertRedirect(route('payroll-items.edit', $this->payrollItem));

        $this->assertDatabaseHas('payroll_item_details', [
            'payroll_item_id' => $this->payrollItem->id,
            'component_name' => 'Bonus Kinerja',
            'type' => 'earning',
            'category' => 'variable',
        ]);
    });

    it('can add new deduction component', function () {
        $response = $this->actingAs($this->user)
            ->post(route('payroll-items.add-detail', $this->payrollItem), [
                'component_name' => 'Kasbon',
                'type' => 'deduction',
                'category' => 'loan',
                'amount' => 500000,
                'is_taxable' => false,
            ]);

        $response->assertRedirect(route('payroll-items.edit', $this->payrollItem));

        $this->assertDatabaseHas('payroll_item_details', [
            'payroll_item_id' => $this->payrollItem->id,
            'component_name' => 'Kasbon',
            'type' => 'deduction',
            'category' => 'loan',
        ]);
    });

    it('validates required fields when adding component', function () {
        $response = $this->actingAs($this->user)
            ->post(route('payroll-items.add-detail', $this->payrollItem), []);

        $response->assertSessionHasErrors(['component_name', 'type', 'category', 'amount']);
    });
});

describe('Payroll Item Adjustment - Remove Component', function () {
    it('can remove manually added component', function () {
        $detail = PayrollItemDetail::factory()->earning()->withAmount(500000)->create([
            'payroll_item_id' => $this->payrollItem->id,
            'category' => 'variable',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('payroll-items.remove-detail', [$this->payrollItem, $detail]));

        $response->assertRedirect(route('payroll-items.edit', $this->payrollItem));

        $this->assertDatabaseMissing('payroll_item_details', ['id' => $detail->id]);
    });

    it('cannot remove auto-calculated bpjs component', function () {
        $bpjsDetail = PayrollItemDetail::factory()->deduction()->withAmount(100000)->create([
            'payroll_item_id' => $this->payrollItem->id,
            'component_name' => 'BPJS Kesehatan',
            'category' => 'bpjs',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('payroll-items.remove-detail', [$this->payrollItem, $bpjsDetail]));

        $response->assertRedirect(route('payroll-items.edit', $this->payrollItem));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_item_details', ['id' => $bpjsDetail->id]);
    });

    it('cannot remove auto-calculated tax component', function () {
        $taxDetail = PayrollItemDetail::factory()->deduction()->withAmount(200000)->create([
            'payroll_item_id' => $this->payrollItem->id,
            'component_name' => 'PPh 21',
            'category' => 'tax',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('payroll-items.remove-detail', [$this->payrollItem, $taxDetail]));

        $response->assertRedirect(route('payroll-items.edit', $this->payrollItem));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payroll_item_details', ['id' => $taxDetail->id]);
    });
});

describe('Payroll Item Adjustment - Recalculation', function () {
    it('recalculates totals after adjustment', function () {
        // Add an earning detail first
        $earning = PayrollItemDetail::factory()->create([
            'payroll_item_id' => $this->payrollItem->id,
            'type' => 'earning',
            'category' => 'fixed',
            'component_name' => 'Tunjangan Makan',
            'amount' => 500000,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('payroll-items.update', $this->payrollItem), [
                'basic_salary' => 10000000,
                'details' => [
                    ['id' => $earning->id, 'amount' => 1000000],
                ],
            ]);

        $response->assertRedirect(route('payroll-items.show', $this->payrollItem));

        $this->payrollItem->refresh();

        // Total earnings should reflect the updated amount
        expect((float) $this->payrollItem->total_earnings)->toBe(1000000.00);
        // Gross = basic + earnings
        expect((float) $this->payrollItem->gross_salary)->toBe(11000000.00);
    });

    it('updates parent payroll totals after adjustment', function () {
        $this->actingAs($this->user)
            ->put(route('payroll-items.update', $this->payrollItem), [
                'basic_salary' => 15000000,
            ]);

        $this->payroll->refresh();

        // Parent payroll totals should be updated
        expect((float) $this->payroll->total_net_salary)->toBeGreaterThan(0);
    });
});

describe('Payroll Item Adjustment - Tenant Isolation', function () {
    it('prevents access to other tenant payroll items', function () {
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        $otherPayroll = Payroll::factory()->calculated()->create([
            'company_id' => $otherCompany->id,
            'created_by' => $otherUser->id,
        ]);
        $otherEmployee = Employee::factory()->create(['company_id' => $otherCompany->id]);
        $otherSalary = EmployeeSalary::factory()->create([
            'company_id' => $otherCompany->id,
            'employee_id' => $otherEmployee->id,
        ]);
        $otherPayrollItem = PayrollItem::factory()->calculated()->create([
            'payroll_id' => $otherPayroll->id,
            'employee_id' => $otherEmployee->id,
            'employee_salary_id' => $otherSalary->id,
        ]);

        // Try to access other company's payroll item
        $this->actingAs($this->user)
            ->get(route('payroll-items.edit', $otherPayrollItem))
            ->assertNotFound();

        $this->actingAs($this->user)
            ->put(route('payroll-items.update', $otherPayrollItem), ['basic_salary' => 999])
            ->assertNotFound();

        $this->actingAs($this->user)
            ->post(route('payroll-items.add-detail', $otherPayrollItem), [
                'component_name' => 'Test',
                'type' => 'earning',
                'category' => 'other',
                'amount' => 100,
            ])
            ->assertNotFound();
    });
});
