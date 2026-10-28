<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\LarkIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->integration = LarkIntegration::create([
        'company_id' => $this->company->id,
        'app_id' => 'cli_test_app_id',
        'app_secret' => 'sec_test_secret',
        'verification_token' => 'test_verification_token_123',
        'is_active' => true,
    ]);
});

test('it handles Lark url_verification challenge handshake', function () {
    $response = $this->postJson('/api/v1/webhooks/lark', [
        'type' => 'url_verification',
        'challenge' => 'sample_challenge_code_98765',
        'token' => 'test_verification_token_123',
    ]);

    $response->assertOk()
        ->assertJson([
            'challenge' => 'sample_challenge_code_98765',
        ]);
});

test('it handles Lark url_verification challenge on /api/webhooks/lark alias', function () {
    $response = $this->postJson('/api/webhooks/lark', [
        'type' => 'url_verification',
        'challenge' => 'sample_challenge_code_98765',
        'token' => 'test_verification_token_123',
    ]);

    $response->assertOk()
        ->assertJson([
            'challenge' => 'sample_challenge_code_98765',
        ]);
});

test('it rejects event with invalid verification token', function () {
    $response = $this->postJson('/api/v1/webhooks/lark', [
        'schema' => '2.0',
        'header' => [
            'event_id' => 'evt_123',
            'event_type' => 'contact.user.created_v3',
            'app_id' => 'cli_test_app_id',
            'token' => 'invalid_wrong_token',
        ],
        'event' => [],
    ]);

    $response->assertStatus(403)
        ->assertJson([
            'code' => 403,
            'msg' => 'Invalid verification token',
        ]);
});

test('it auto-maps employee lark_user_id on contact.user.created_v3 event', function () {
    $employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'email' => 'employee.lark@company.com',
        'lark_user_id' => null,
    ]);

    $response = $this->postJson('/api/v1/webhooks/lark', [
        'schema' => '2.0',
        'header' => [
            'event_id' => 'evt_456',
            'event_type' => 'contact.user.created_v3',
            'app_id' => 'cli_test_app_id',
            'token' => 'test_verification_token_123',
        ],
        'event' => [
            'object' => [
                'user_id' => 'ou_new_lark_user_888',
                'open_id' => 'on_new_open_id_888',
                'email' => 'employee.lark@company.com',
            ],
        ],
    ]);

    $response->assertOk()
        ->assertJson(['code' => 0, 'msg' => 'success']);

    $employee->refresh();
    expect($employee->lark_user_id)->toBe('ou_new_lark_user_888');
    expect($employee->lark_open_id)->toBe('on_new_open_id_888');
});
