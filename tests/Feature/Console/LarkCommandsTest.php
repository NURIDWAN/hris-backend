<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LarkIntegration;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->integration = LarkIntegration::create([
        'company_id' => $this->company->id,
        'app_id' => 'cli_test_123',
        'app_secret' => 'sec_test_secret',
        'is_active' => true,
        'push_clock_in' => true,
        'push_clock_out' => true,
    ]);
});

test('lark:sync-employees command matches employees with Lark', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'staff@company.com',
        'is_active' => true,
    ]);

    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-token_123',
            'expire' => 7200,
        ], 200),
        '*/contact/v3/users/batch_get_id*' => Http::response([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'user_list' => [
                    [
                        'email' => 'staff@company.com',
                        'user_id' => 'ou_matched_user_1',
                        'open_id' => 'ou_open_user_1',
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->artisan('lark:sync-employees', ['--company' => $this->company->id])
        ->assertSuccessful();

    $employee->refresh();
    expect($employee->lark_user_id)->toBe('ou_matched_user_1');
    expect($employee->lark_open_id)->toBe('ou_open_user_1');
});

test('lark:retry-sync command retries unsynced attendances', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'lark_user_id' => 'ou_user_retried',
    ]);

    $attendance = Attendance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $employee->id,
        'date' => Carbon::today(),
        'clock_in' => Carbon::now()->subHours(4),
        'lark_synced_at' => null,
    ]);

    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-token_123',
            'expire' => 7200,
        ], 200),
        '*/attendance/v1/user_flow_records*' => Http::response([
            'code' => 0,
            'msg' => 'success',
            'data' => [
                'user_flow_records' => [
                    [
                        'record_id' => 'rec_retried_888',
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->artisan('lark:retry-sync', [
        '--company' => $this->company->id,
        '--date' => Carbon::today()->toDateString(),
    ])->assertSuccessful();

    $attendance->refresh();
    expect($attendance->lark_record_id)->toBe('rec_retried_888');
    expect($attendance->lark_synced_at)->not->toBeNull();
});
