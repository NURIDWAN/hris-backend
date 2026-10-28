<?php

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('Admin Reimbursement API', function () {
    beforeEach(function () {
        $this->company = Company::factory()->create();

        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $this->adminUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole($role);

        $dept = Department::create([
            'company_id' => $this->company->id,
            'name' => 'Technology',
            'code' => 'TECH',
        ]);

        $pos = Position::create([
            'company_id' => $this->company->id,
            'department_id' => $dept->id,
            'name' => 'Software Engineer',
            'code' => 'SE',
        ]);

        $empUser = User::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->employee = Employee::create([
            'company_id' => $this->company->id,
            'user_id' => $empUser->id,
            'department_id' => $dept->id,
            'position_id' => $pos->id,
            'employee_id' => 'EMP-001',
            'first_name' => 'Alice',
            'last_name' => 'Developer',
            'hire_date' => '2025-01-01',
            'employment_status' => 'permanent',
        ]);

        Sanctum::actingAs($this->adminUser);
    });

    it('can manage reimbursement categories', function () {
        // Create category
        $res = $this->postJson('/api/v1/admin/reimbursement-categories', [
            'name' => 'Transport & Bensin',
            'description' => 'Klaim biaya bahan bakar dinas',
            'max_amount' => 500000,
            'requires_receipt' => true,
        ]);

        $res->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Transport & Bensin');

        $catId = $res->json('data.id');

        // List categories
        $listRes = $this->getJson('/api/v1/admin/reimbursement-categories');
        $listRes->assertStatus(200)
            ->assertJsonPath('meta.total', 1);

        // Update category
        $upRes = $this->putJson("/api/v1/admin/reimbursement-categories/{$catId}", [
            'name' => 'Transportasi & BBM',
            'max_amount' => 750000,
        ]);
        $upRes->assertStatus(200)
            ->assertJsonPath('data.name', 'Transportasi & BBM');

        // Delete category
        $delRes = $this->deleteJson("/api/v1/admin/reimbursement-categories/{$catId}");
        $delRes->assertStatus(200);
        $this->assertDatabaseMissing('reimbursement_categories', ['id' => $catId]);
    });

    it('can process reimbursement request lifecycle', function () {
        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Medical Klaim',
            'is_active' => true,
        ]);

        // Create reimbursement
        $createRes = $this->postJson('/api/v1/admin/reimbursements', [
            'employee_id' => $this->employee->id,
            'category_id' => $category->id,
            'amount' => 150000,
            'description' => 'Beli vitamin dan obat flu',
            'expense_date' => '2026-03-25',
        ]);

        $createRes->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending');

        $reimbId = $createRes->json('data.id');

        // Show detail
        $showRes = $this->getJson("/api/v1/admin/reimbursements/{$reimbId}");
        $showRes->assertStatus(200)
            ->assertJsonPath('data.description', 'Beli vitamin dan obat flu');

        // Approve
        $appRes = $this->postJson("/api/v1/admin/reimbursements/{$reimbId}/approve", [
            'notes' => 'Kuitansi terverifikasi',
        ]);
        $appRes->assertStatus(200)
            ->assertJsonPath('data.status', 'approved');

        // Pay
        $payRes = $this->postJson("/api/v1/admin/reimbursements/{$reimbId}/pay", [
            'payment_method' => 'bank_transfer',
        ]);
        $payRes->assertStatus(200)
            ->assertJsonPath('data.status', 'paid');
    });

    it('can reject a pending reimbursement request', function () {
        $category = ReimbursementCategory::create([
            'company_id' => $this->company->id,
            'name' => 'Office Supplies',
            'is_active' => true,
        ]);

        $reimb = Reimbursement::create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $category->id,
            'amount' => 80000,
            'description' => 'Buku catatan',
            'expense_date' => '2026-03-25',
            'status' => 'pending',
        ]);

        $rejectRes = $this->postJson("/api/v1/admin/reimbursements/{$reimb->id}/reject", [
            'reason' => 'Nota tidak terbaca jelas',
        ]);

        $rejectRes->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected')
            ->assertJsonPath('data.rejection_reason', 'Nota tidak terbaca jelas');
    });
});
