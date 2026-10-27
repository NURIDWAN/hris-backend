<?php

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LarkIntegration;
use App\Services\Lark\LarkApiClient;
use App\Services\Lark\LarkAttendanceService;
use App\Services\Lark\LarkAuthService;
use App\Services\Lark\LarkEmployeeService;
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

    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'employee@company.com',
        'lark_user_id' => 'ou_emp_123',
    ]);

    $this->attendance = Attendance::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'date' => Carbon::today(),
        'clock_in' => Carbon::now()->subHours(8),
        'clock_out' => Carbon::now(),
    ]);

    $authService = new LarkAuthService;
    $apiClient = new LarkApiClient($authService);
    $employeeService = new LarkEmployeeService($apiClient);
    $this->service = new LarkAttendanceService($apiClient, $employeeService);
});

test('pushAttendance skips when integration is inactive', function () {
    $this->integration->update(['is_active' => false]);

    $result = $this->service->pushAttendance($this->attendance, 'clock_in');

    expect($result['success'])->toBeFalse();
    expect($result['status'])->toBe('skipped');
});

test('pushAttendance skips clock_in when push_clock_in is false', function () {
    $this->integration->update(['push_clock_in' => false]);

    $result = $this->service->pushAttendance($this->attendance, 'clock_in');

    expect($result['success'])->toBeFalse();
    expect($result['status'])->toBe('skipped');
});

test('pushAttendance successfully pushes record to Lark API and updates attendance', function () {
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
                        'record_id' => 'rec_lark_9999',
                        'user_id' => 'ou_emp_123',
                        'check_time' => (string) $this->attendance->clock_in->timestamp,
                    ],
                ],
            ],
        ], 200),
    ]);

    $result = $this->service->pushAttendance($this->attendance, 'clock_in');

    expect($result['success'])->toBeTrue();
    expect($result['record_id'])->toBe('rec_lark_9999');

    $this->attendance->refresh();
    expect($this->attendance->lark_record_id)->toBe('rec_lark_9999');
    expect($this->attendance->lark_synced_at)->not->toBeNull();
    expect($this->attendance->lark_sync_error)->toBeNull();
});

test('pushAttendance records lark_sync_error on API failure', function () {
    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-token_123',
            'expire' => 7200,
        ], 200),
        '*/attendance/v1/user_flow_records*' => Http::response([
            'code' => 40001,
            'msg' => 'user_id not found in tenant',
        ], 200),
    ]);

    expect(fn () => $this->service->pushAttendance($this->attendance, 'clock_in'))
        ->toThrow(Exception::class);

    $this->attendance->refresh();
    expect($this->attendance->lark_synced_at)->toBeNull();
    expect($this->attendance->lark_sync_error)->toContain('user_id not found in tenant');
});
