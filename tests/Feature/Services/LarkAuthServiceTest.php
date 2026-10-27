<?php

use App\Models\Company;
use App\Models\LarkIntegration;
use App\Services\Lark\LarkAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->integration = LarkIntegration::create([
        'company_id' => $this->company->id,
        'app_id' => 'cli_test_123',
        'app_secret' => 'sec_test_secret',
        'is_active' => true,
    ]);
    $this->service = new LarkAuthService;
});

test('requestTenantAccessToken returns token on successful Lark response', function () {
    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-test_token_xyz',
            'expire' => 7200,
        ], 200),
    ]);

    $token = $this->service->requestTenantAccessToken('cli_test_123', 'sec_test_secret');

    expect($token)->toBe('t-test_token_xyz');
});

test('requestTenantAccessToken throws exception on Lark API error code', function () {
    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 10003,
            'msg' => 'app secret invalid',
        ], 200),
    ]);

    expect(fn () => $this->service->requestTenantAccessToken('cli_test_123', 'sec_bad_secret'))
        ->toThrow(Exception::class, 'Lark API Error (10003): app secret invalid');
});

test('getTenantAccessToken caches the token', function () {
    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-cached_token_123',
            'expire' => 7200,
        ], 200),
    ]);

    $token1 = $this->service->getTenantAccessToken($this->integration);
    $token2 = $this->service->getTenantAccessToken($this->integration);

    expect($token1)->toBe('t-cached_token_123');
    expect($token2)->toBe('t-cached_token_123');

    // Only 1 HTTP call made due to caching
    Http::assertSentCount(1);
});

test('clearToken clears the cached token', function () {
    $cacheKey = config('lark.cache_prefix').$this->integration->company_id;
    Cache::put($cacheKey, 'pre_existing_token', 600);

    expect(Cache::get($cacheKey))->toBe('pre_existing_token');

    $this->service->clearToken($this->integration);

    expect(Cache::get($cacheKey))->toBeNull();
});

test('testCredentials returns success status and message', function () {
    Http::fake([
        '*/auth/v3/tenant_access_token/internal' => Http::response([
            'code' => 0,
            'msg' => 'ok',
            'tenant_access_token' => 't-valid_token',
        ], 200),
    ]);

    $result = $this->service->testCredentials('cli_valid', 'sec_valid');

    expect($result['success'])->toBeTrue();
    expect($result['message'])->toContain('berhasil');
});
