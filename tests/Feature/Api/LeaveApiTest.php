<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
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
    $this->leaveType = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Cuti Tahunan',
        'default_days' => 12,
    ]);
    $this->leaveBalance = LeaveBalance::factory()->create([
        'employee_id' => $this->employee->id,
        'leave_type_id' => $this->leaveType->id,
        'year' => now()->year,
        'entitled_days' => 12,
        'used_days' => 0,
        'pending_days' => 0,
    ]);
});

describe('GET /api/v1/leaves', function () {
    it('returns list of leave requests', function () {
        Sanctum::actingAs($this->user);

        LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->getJson('/api/v1/leaves');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'request_number',
                        'leave_type',
                        'start_date',
                        'end_date',
                        'total_days',
                        'reason',
                        'status',
                        'status_label',
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

        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
        ]);

        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'approved',
        ]);

        $response = $this->getJson('/api/v1/leaves?status=pending');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
        expect($response->json('data.0.status'))->toBe('pending');
    });

    it('can filter by year', function () {
        Sanctum::actingAs($this->user);

        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => now(),
            'end_date' => now(),
        ]);

        $response = $this->getJson('/api/v1/leaves?year='.now()->year);

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });
});

describe('POST /api/v1/leaves', function () {
    it('can create leave request', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(9)->toDateString(),
            'reason' => 'Family vacation',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'request_number',
                    'leave_type',
                    'start_date',
                    'end_date',
                    'total_days',
                    'status',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect($response->json('data.total_days'))->toBe(3);

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
        ]);
    });

    it('can create half day leave', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
            'is_half_day' => true,
            'half_day_type' => 'morning',
            'reason' => 'Doctor appointment',
        ]);

        $response->assertStatus(201);
        expect($response->json('data.total_days'))->toBe(0.5);
    });

    it('can upload attachment', function () {
        Sanctum::actingAs($this->user);

        $attachment = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(),
            'reason' => 'Sick leave',
            'attachment' => $attachment,
        ]);

        $response->assertStatus(201);

        $leaveRequest = LeaveRequest::find($response->json('data.id'));
        expect($leaveRequest->attachment)->not->toBeNull();
    });

    it('validates leave balance', function () {
        Sanctum::actingAs($this->user);

        // Use all balance
        $this->leaveBalance->update(['used_days' => 12]);

        $response = $this->postJson('/api/v1/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7)->toDateString(),
            'end_date' => today()->addDays(9)->toDateString(),
            'reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Sisa cuti tidak mencukupi.',
            ]);
    });

    it('validates date range', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(9)->toDateString(),
            'end_date' => today()->addDays(7)->toDateString(), // Before start
            'reason' => 'Vacation',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    });

    it('prevents overlapping leave requests', function () {
        Sanctum::actingAs($this->user);

        LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(7),
            'end_date' => today()->addDays(10),
            'status' => 'approved',
        ]);

        $response = $this->postJson('/api/v1/leaves', [
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(8)->toDateString(), // Overlaps
            'end_date' => today()->addDays(12)->toDateString(),
            'reason' => 'Another vacation',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Tanggal cuti bertabrakan dengan cuti yang sudah diajukan.',
            ]);
    });
});

describe('GET /api/v1/leaves/{id}', function () {
    it('returns leave request detail', function () {
        Sanctum::actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->getJson("/api/v1/leaves/{$leaveRequest->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'request_number',
                    'leave_type',
                    'start_date',
                    'end_date',
                    'total_days',
                    'is_half_day',
                    'reason',
                    'attachment',
                    'status',
                    'status_label',
                    'approved_by',
                    'approved_at',
                    'rejection_reason',
                    'created_at',
                ],
            ]);
    });

    it('returns 404 for other employee leave', function () {
        Sanctum::actingAs($this->user);

        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
            'leave_type_id' => $this->leaveType->id,
        ]);

        $response = $this->getJson("/api/v1/leaves/{$leaveRequest->id}");

        $response->assertStatus(404);
    });
});

describe('POST /api/v1/leaves/{id}/cancel', function () {
    it('can cancel pending leave request', function () {
        Sanctum::actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/v1/leaves/{$leaveRequest->id}/cancel", [
            'reason' => 'Plans changed',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Cuti berhasil dibatalkan.',
            ]);

        $this->assertDatabaseHas('leave_requests', [
            'id' => $leaveRequest->id,
            'status' => 'cancelled',
        ]);
    });

    it('can cancel approved leave if not started', function () {
        Sanctum::actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->addDays(5),
            'end_date' => today()->addDays(7),
            'status' => 'approved',
        ]);

        $response = $this->postJson("/api/v1/leaves/{$leaveRequest->id}/cancel", [
            'reason' => 'Emergency work',
        ]);

        $response->assertOk();
    });

    it('cannot cancel started leave', function () {
        Sanctum::actingAs($this->user);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => today()->subDay(), // Already started
            'end_date' => today()->addDays(2),
            'status' => 'approved',
        ]);

        $response = $this->postJson("/api/v1/leaves/{$leaveRequest->id}/cancel", [
            'reason' => 'Want to cancel',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Cuti yang sudah berjalan tidak dapat dibatalkan.',
            ]);
    });
});

describe('GET /api/v1/leaves/balance', function () {
    it('returns leave balances for all types', function () {
        Sanctum::actingAs($this->user);

        // Create another leave type with balance
        $sickLeave = LeaveType::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Cuti Sakit',
            'default_days' => 6,
        ]);

        LeaveBalance::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $sickLeave->id,
            'year' => now()->year,
            'entitled_days' => 6,
            'used_days' => 2,
            'pending_days' => 1,
        ]);

        $response = $this->getJson('/api/v1/leaves/balance');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'leave_type_id',
                        'leave_type_name',
                        'year',
                        'entitled_days',
                        'used_days',
                        'pending_days',
                        'remaining_days',
                    ],
                ],
            ]);

        expect(count($response->json('data')))->toBe(2);
    });

    it('can filter by year', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/leaves/balance?year='.now()->year);

        $response->assertOk();
        expect($response->json('data.0.year'))->toBe(now()->year);
    });
});

describe('GET /api/v1/leaves/types', function () {
    it('returns available leave types', function () {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/leaves/types');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'quota',
                        'is_paid',
                        'requires_attachment',
                    ],
                ],
            ]);
    });
});
