<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);

    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');

    $employeeUser = User::factory()->create(['company_id' => $this->company->id]);
    $employeeUser->assignRole('employee');

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $employeeUser->id,
    ]);

    app()->instance('tenant', $this->company);

    Storage::fake('public');
});

describe('Reimbursement Categories', function () {
    it('displays category index page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('reimbursement-categories.index'));

        $response->assertOk();
        $response->assertViewIs('reimbursement-categories.index');
    });

    it('creates new category', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursement-categories.store'), [
            'name' => 'Transportasi',
            'description' => 'Biaya transportasi dinas',
            'max_amount' => 500000,
            'requires_receipt' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reimbursement_categories', [
            'company_id' => $this->company->id,
            'name' => 'Transportasi',
            'max_amount' => 500000,
        ]);
    });

    it('updates category', function () {
        $category = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Transport',
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('reimbursement-categories.update', $category), [
            'name' => 'Transportasi',
            'description' => 'Updated description',
            'max_amount' => 750000,
            'requires_receipt' => true,
            'is_active' => true,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('reimbursement_categories', [
            'id' => $category->id,
            'name' => 'Transportasi',
            'max_amount' => 750000,
        ]);
    });

    it('deletes category', function () {
        $category = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('reimbursement-categories.destroy', $category));

        $response->assertRedirect();
        $this->assertDatabaseMissing('reimbursement_categories', ['id' => $category->id]);
    });

    it('validates category name uniqueness', function () {
        ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Transportasi',
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursement-categories.store'), [
            'name' => 'Transportasi',
            'max_amount' => 500000,
            'requires_receipt' => true,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors(['name']);
    });
});

describe('Reimbursement Requests', function () {
    beforeEach(function () {
        $this->category = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Transportasi',
            'max_amount' => 500000,
            'requires_receipt' => true,
        ]);
    });

    it('displays reimbursement index page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('reimbursements.index'));

        $response->assertOk();
        $response->assertViewIs('reimbursements.index');
    });

    it('displays reimbursement create page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('reimbursements.create'));

        $response->assertOk();
        $response->assertViewIs('reimbursements.create');
    });

    it('creates new reimbursement request', function () {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post(route('reimbursements.store'), [
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 250000,
            'description' => 'Biaya taksi meeting client',
            'expense_date' => now()->subDays(3)->toDateString(),
            'receipt' => $file,
        ]);

        $response->assertRedirect(route('reimbursements.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reimbursements', [
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 250000,
            'status' => 'pending',
        ]);
    });

    it('creates reimbursement with receipt upload', function () {
        $this->actingAs($this->admin);

        $file = UploadedFile::fake()->image('receipt.jpg');

        $response = $this->post(route('reimbursements.store'), [
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 250000,
            'description' => 'Test',
            'expense_date' => now()->toDateString(),
            'receipt' => $file,
        ]);

        $response->assertRedirect();

        $reimbursement = Reimbursement::where('employee_id', $this->employee->id)->first();
        expect($reimbursement->receipt_path)->not->toBeNull();
    });

    it('validates amount against category max', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.store'), [
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 750000, // Exceeds max 500000
            'description' => 'Test',
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
    });

    it('requires receipt when category requires it', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.store'), [
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 250000,
            'description' => 'Test',
            'expense_date' => now()->toDateString(),
            // No receipt uploaded
        ]);

        $response->assertSessionHasErrors(['receipt']);
    });

    it('does not require receipt when category does not require it', function () {
        $categoryNoReceipt = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Makan',
            'requires_receipt' => false,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.store'), [
            'employee_id' => $this->employee->id,
            'category_id' => $categoryNoReceipt->id,
            'amount' => 250000,
            'description' => 'Test',
            'expense_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });

    it('displays reimbursement detail page', function () {
        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('reimbursements.show', $reimbursement));

        $response->assertOk();
        $response->assertViewIs('reimbursements.show');
    });

    it('filters reimbursements by status', function () {
        Reimbursement::factory()->pending()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        Reimbursement::factory()->approved()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('reimbursements.index', ['status' => 'pending']));

        $response->assertOk();
    });

    it('filters reimbursements by category', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('reimbursements.index', ['category_id' => $this->category->id]));

        $response->assertOk();
    });
});

describe('Reimbursement Approval', function () {
    beforeEach(function () {
        $this->category = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);
    });

    it('approves pending reimbursement', function () {
        $reimbursement = Reimbursement::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.approve', $reimbursement));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reimbursement->refresh();
        expect($reimbursement->status)->toBe('approved');
        expect($reimbursement->approved_by)->toBe($this->admin->id);
    });

    it('rejects pending reimbursement with reason', function () {
        $reimbursement = Reimbursement::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.reject', $reimbursement), [
            'rejection_reason' => 'Bukti tidak lengkap',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reimbursement->refresh();
        expect($reimbursement->status)->toBe('rejected');
        expect($reimbursement->rejection_reason)->toBe('Bukti tidak lengkap');
    });

    it('marks reimbursement as paid', function () {
        $reimbursement = Reimbursement::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.pay', $reimbursement), [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'transfer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $reimbursement->refresh();
        expect($reimbursement->status)->toBe('paid');
        expect($reimbursement->paid_at)->not->toBeNull();
    });

    it('cannot approve non-pending reimbursement', function () {
        $reimbursement = Reimbursement::factory()->approved()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.approve', $reimbursement));

        $response->assertRedirect();
        $response->assertSessionHas('error');
    });

    it('requires rejection reason when rejecting', function () {
        $reimbursement = Reimbursement::factory()->pending()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('reimbursements.reject', $reimbursement), [
            'rejection_reason' => '',
        ]);

        $response->assertSessionHasErrors(['rejection_reason']);
    });
});

describe('Employee Portal - Reimbursement', function () {
    beforeEach(function () {
        $this->category = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
            'requires_receipt' => false,
        ]);
    });

    it('displays employee reimbursement history', function () {
        $employeeUser = $this->employee->user;

        Reimbursement::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($employeeUser);

        $response = $this->get(route('portal.reimbursements.index'));

        $response->assertOk();
        $response->assertViewIs('portal.reimbursements.index');
    });

    it('allows employee to create reimbursement request', function () {
        $employeeUser = $this->employee->user;

        $this->actingAs($employeeUser);

        $response = $this->get(route('portal.reimbursements.create'));

        $response->assertOk();
        $response->assertViewIs('portal.reimbursements.create');
    });

    it('employee can submit reimbursement request', function () {
        $employeeUser = $this->employee->user;

        $this->actingAs($employeeUser);

        $response = $this->post(route('portal.reimbursements.store'), [
            'category_id' => $this->category->id,
            'amount' => 150000,
            'description' => 'Biaya parkir',
            'expense_date' => now()->subDay()->toDateString(),
        ]);

        $response->assertRedirect(route('portal.reimbursements.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('reimbursements', [
            'employee_id' => $this->employee->id,
            'amount' => 150000,
            'status' => 'pending',
        ]);
    });

    it('employee can view reimbursement detail', function () {
        $employeeUser = $this->employee->user;

        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($employeeUser);

        $response = $this->get(route('portal.reimbursements.show', $reimbursement));

        $response->assertOk();
    });

    it('employee cannot view other employee reimbursement', function () {
        $employeeUser = $this->employee->user;

        $otherEmployee = Employee::factory()->create(['company_id' => $this->company->id]);
        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
            'category_id' => $this->category->id,
        ]);

        $this->actingAs($employeeUser);

        $response = $this->get(route('portal.reimbursements.show', $reimbursement));

        $response->assertForbidden();
    });
});

describe('Reimbursement Model', function () {
    beforeEach(function () {
        $this->category = ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
        ]);
    });

    it('returns correct status label', function () {
        $pending = Reimbursement::factory()->pending()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        $approved = Reimbursement::factory()->approved()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        $rejected = Reimbursement::factory()->rejected()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        $paid = Reimbursement::factory()->paid()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        expect($pending->status_label)->toBe('Menunggu');
        expect($approved->status_label)->toBe('Disetujui');
        expect($rejected->status_label)->toBe('Ditolak');
        expect($paid->status_label)->toBe('Dibayar');
    });

    it('has status check methods', function () {
        $pending = Reimbursement::factory()->pending()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);
        $approved = Reimbursement::factory()->approved()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        expect($pending->isPending())->toBeTrue();
        expect($pending->isApproved())->toBeFalse();

        expect($approved->isApproved())->toBeTrue();
        expect($approved->isPending())->toBeFalse();
    });

    it('formats amount correctly', function () {
        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
            'amount' => 250000,
        ]);

        expect($reimbursement->formatted_amount)->toBe('Rp 250.000');
    });

    it('belongs to category', function () {
        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'category_id' => $this->category->id,
        ]);

        expect($reimbursement->category)->toBeInstanceOf(ReimbursementCategory::class);
        expect($reimbursement->category->id)->toBe($this->category->id);
    });
});
