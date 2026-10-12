<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');

    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
    ]);
    $this->category = ReimbursementCategory::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Transportasi',
        'max_amount' => 500000,
    ]);
});

describe('GET /api/v1/reimbursements', function () {
    it('returns list of reimbursements', function () {
        Sanctum::actingAs($this->user);

        Reimbursement::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson('/api/v1/reimbursements');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'category',
                        'amount',
                        'formatted_amount',
                        'description',
                        'expense_date',
                        'status',
                        'status_label',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);
    });

    it('can filter by status', function () {
        Sanctum::actingAs($this->user);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'status' => Reimbursement::STATUS_PENDING,
        ]);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'status' => Reimbursement::STATUS_APPROVED,
        ]);

        $response = $this->getJson('/api/v1/reimbursements?status=pending');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('can filter by date range', function () {
        Sanctum::actingAs($this->user);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'expense_date' => today()->subDays(3),
        ]);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'expense_date' => today()->subMonth(),
        ]);

        $response = $this->getJson('/api/v1/reimbursements?'.http_build_query([
            'start_date' => today()->subWeek()->toDateString(),
            'end_date' => today()->toDateString(),
        ]));

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });
});

describe('POST /api/v1/reimbursements', function () {
    it('can create reimbursement with receipt', function () {
        Sanctum::actingAs($this->user);

        $receipt = UploadedFile::fake()->create('receipt.pdf', 100);

        $response = $this->postJson('/api/v1/reimbursements', [
            'category_id' => $this->category->id,
            'amount' => 150000,
            'description' => 'Taxi to client meeting',
            'expense_date' => today()->toDateString(),
            'receipt' => $receipt,
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'category',
                    'amount',
                    'formatted_amount',
                    'description',
                    'expense_date',
                    'receipt_url',
                    'status',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();

        $this->assertDatabaseHas('reimbursements', [
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 150000,
            'status' => Reimbursement::STATUS_PENDING,
        ]);
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/reimbursements', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id', 'amount', 'description', 'expense_date']);
    });

    it('validates amount against category max', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/reimbursements', [
            'category_id' => $this->category->id,
            'amount' => 600000, // Exceeds max 500000
            'description' => 'Testing',
            'expense_date' => today()->toDateString(),
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Jumlah melebihi batas maksimal kategori (Rp 500.000).',
            ]);
    });

    it('validates receipt file type', function () {
        Sanctum::actingAs($this->user);

        $receipt = UploadedFile::fake()->create('receipt.exe', 100);

        $response = $this->postJson('/api/v1/reimbursements', [
            'category_id' => $this->category->id,
            'amount' => 100000,
            'description' => 'Testing',
            'expense_date' => today()->toDateString(),
            'receipt' => $receipt,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receipt']);
    });
});

describe('GET /api/v1/reimbursements/{id}', function () {
    it('returns reimbursement detail', function () {
        Sanctum::actingAs($this->user);

        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/reimbursements/{$reimbursement->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'category' => [
                        'id',
                        'name',
                    ],
                    'amount',
                    'formatted_amount',
                    'description',
                    'expense_date',
                    'receipt_url',
                    'status',
                    'status_label',
                    'approved_by',
                    'approved_at',
                    'rejection_reason',
                    'paid_at',
                    'payment_method',
                    'created_at',
                ],
            ]);
    });

    it('returns 404 for other employee reimbursement', function () {
        Sanctum::actingAs($this->user);

        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $reimbursement = Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson("/api/v1/reimbursements/{$reimbursement->id}");

        $response->assertStatus(404);
    });
});

describe('GET /api/v1/reimbursements/categories', function () {
    it('returns available categories', function () {
        Sanctum::actingAs($this->user);

        ReimbursementCategory::factory()->count(2)->sequence(
            ['name' => 'Category A'],
            ['name' => 'Category B'],
        )->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/reimbursements/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'description',
                        'max_amount',
                        'formatted_max_amount',
                        'requires_receipt',
                    ],
                ],
            ]);
    });

    it('only shows active categories', function () {
        Sanctum::actingAs($this->user);

        ReimbursementCategory::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Inactive Category',
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/reimbursements/categories');

        $response->assertOk();
        // Should only show the one created in beforeEach
        expect(count($response->json('data')))->toBe(1);
    });
});

describe('GET /api/v1/reimbursements/summary', function () {
    it('returns monthly reimbursement summary', function () {
        Sanctum::actingAs($this->user);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 100000,
            'expense_date' => today(),
            'status' => Reimbursement::STATUS_APPROVED,
        ]);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 150000,
            'expense_date' => today(),
            'status' => Reimbursement::STATUS_PAID,
        ]);

        Reimbursement::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'category_id' => $this->category->id,
            'amount' => 50000,
            'expense_date' => today(),
            'status' => Reimbursement::STATUS_PENDING,
        ]);

        $response = $this->getJson('/api/v1/reimbursements/summary?'.http_build_query([
            'month' => today()->month,
            'year' => today()->year,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_requests',
                    'pending_requests',
                    'approved_requests',
                    'paid_requests',
                    'total_amount',
                    'approved_amount',
                    'paid_amount',
                    'pending_amount',
                    'formatted_total_amount',
                ],
            ]);

        expect($response->json('data.total_requests'))->toBe(3);
        expect((float) $response->json('data.total_amount'))->toBe(300000.0);
    });
});
