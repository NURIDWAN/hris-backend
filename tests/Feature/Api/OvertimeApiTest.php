<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\OvertimeRequest;
use App\Models\OvertimeSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'base_salary' => 5000000,
    ]);
    $this->overtimeSetting = OvertimeSetting::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('GET /api/v1/overtimes', function () {
    it('returns list of overtime requests', function () {
        Sanctum::actingAs($this->user);

        OvertimeRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson('/api/v1/overtimes');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'date',
                        'start_time',
                        'end_time',
                        'overtime_hours',
                        'overtime_type',
                        'overtime_type_label',
                        'overtime_amount',
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

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => OvertimeRequest::STATUS_APPROVED,
        ]);

        $response = $this->getJson('/api/v1/overtimes?status=pending');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('can filter by date range', function () {
        Sanctum::actingAs($this->user);

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subDays(3),
        ]);

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subMonth(),
        ]);

        $response = $this->getJson('/api/v1/overtimes?'.http_build_query([
            'start_date' => today()->subWeek()->toDateString(),
            'end_date' => today()->toDateString(),
        ]));

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });
});

describe('POST /api/v1/overtimes', function () {
    it('can create overtime request', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/overtimes', [
            'date' => today()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => OvertimeRequest::TYPE_WEEKDAY,
            'reason' => 'Project deadline',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'date',
                    'start_time',
                    'end_time',
                    'overtime_hours',
                    'overtime_type',
                    'overtime_amount',
                    'status',
                ],
            ]);

        expect($response->json('success'))->toBeTrue();
        expect((float) $response->json('data.overtime_hours'))->toBe(3.0);

        $this->assertDatabaseHas('overtime_requests', [
            'employee_id' => $this->employee->id,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);
    });

    it('calculates overtime amount based on settings', function () {
        Sanctum::actingAs($this->user);

        // Weekday overtime: 3 hours * 1.5 multiplier
        $response = $this->postJson('/api/v1/overtimes', [
            'date' => today()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => OvertimeRequest::TYPE_WEEKDAY,
            'reason' => 'Project deadline',
        ]);

        $response->assertStatus(201);
        // Amount should be calculated based on hourly rate * multiplier * hours
        expect($response->json('data.overtime_amount'))->toBeGreaterThan(0);
    });

    it('validates required fields', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/overtimes', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date', 'start_time', 'end_time', 'reason']);
    });

    it('validates time range', function () {
        Sanctum::actingAs($this->user);

        $response = $this->postJson('/api/v1/overtimes', [
            'date' => today()->toDateString(),
            'start_time' => '21:00',
            'end_time' => '18:00', // Before start
            'overtime_type' => OvertimeRequest::TYPE_WEEKDAY,
            'reason' => 'Testing',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    });

    it('prevents duplicate overtime on same date', function () {
        Sanctum::actingAs($this->user);

        $testDate = today()->addDays(5);

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => $testDate,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        $response = $this->postJson('/api/v1/overtimes', [
            'date' => $testDate->toDateString(),
            'start_time' => '18:00',
            'end_time' => '21:00',
            'overtime_type' => OvertimeRequest::TYPE_WEEKDAY,
            'reason' => 'Another request',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Sudah ada pengajuan lembur pada tanggal tersebut.',
            ]);
    });
});

describe('GET /api/v1/overtimes/{id}', function () {
    it('returns overtime request detail', function () {
        Sanctum::actingAs($this->user);

        $overtime = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJson("/api/v1/overtimes/{$overtime->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'date',
                    'start_time',
                    'end_time',
                    'overtime_hours',
                    'overtime_type',
                    'overtime_type_label',
                    'overtime_amount',
                    'formatted_amount',
                    'reason',
                    'status',
                    'status_label',
                    'approved_by',
                    'approved_at',
                    'rejection_reason',
                    'created_at',
                ],
            ]);
    });

    it('returns 404 for other employee overtime', function () {
        Sanctum::actingAs($this->user);

        $otherEmployee = Employee::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $overtime = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $otherEmployee->id,
        ]);

        $response = $this->getJson("/api/v1/overtimes/{$overtime->id}");

        $response->assertStatus(404);
    });
});

describe('POST /api/v1/overtimes/{id}/cancel', function () {
    it('can cancel pending overtime request', function () {
        Sanctum::actingAs($this->user);

        $overtime = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => OvertimeRequest::STATUS_PENDING,
        ]);

        $response = $this->postJson("/api/v1/overtimes/{$overtime->id}/cancel");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Pengajuan lembur berhasil dibatalkan.',
            ]);

        $this->assertDatabaseHas('overtime_requests', [
            'id' => $overtime->id,
            'status' => OvertimeRequest::STATUS_CANCELLED,
        ]);
    });

    it('cannot cancel approved overtime', function () {
        Sanctum::actingAs($this->user);

        $overtime = OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'status' => OvertimeRequest::STATUS_APPROVED,
        ]);

        $response = $this->postJson("/api/v1/overtimes/{$overtime->id}/cancel");

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Hanya pengajuan dengan status menunggu yang dapat dibatalkan.',
            ]);
    });
});

describe('GET /api/v1/overtimes/summary', function () {
    it('returns monthly overtime summary', function () {
        Sanctum::actingAs($this->user);

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today(),
            'overtime_hours' => 3,
            'overtime_amount' => 150000,
            'status' => OvertimeRequest::STATUS_APPROVED,
        ]);

        OvertimeRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $this->employee->id,
            'date' => today()->subDay(),
            'overtime_hours' => 2,
            'overtime_amount' => 100000,
            'status' => OvertimeRequest::STATUS_APPROVED,
        ]);

        $response = $this->getJson('/api/v1/overtimes/summary?'.http_build_query([
            'month' => today()->month,
            'year' => today()->year,
        ]));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total_requests',
                    'approved_requests',
                    'pending_requests',
                    'total_hours',
                    'total_amount',
                    'formatted_total_amount',
                ],
            ]);

        expect((float) $response->json('data.total_hours'))->toBe(5.0);
        expect((float) $response->json('data.total_amount'))->toBe(250000.0);
    });
});
