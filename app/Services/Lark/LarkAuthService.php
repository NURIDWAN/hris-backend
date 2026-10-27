<?php

namespace App\Services\Lark;

use App\Models\LarkIntegration;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LarkAuthService
{
    /**
     * Get a valid tenant_access_token for the given integration.
     * Caches the token to avoid redundant API roundtrips.
     */
    public function getTenantAccessToken(LarkIntegration $integration): string
    {
        $cacheKey = config('lark.cache_prefix', 'lark_tenant_token:').$integration->company_id;

        return Cache::remember($cacheKey, config('lark.token_ttl', 7000), function () use ($integration) {
            return $this->requestTenantAccessToken($integration->app_id, $integration->app_secret);
        });
    }

    /**
     * Request a fresh tenant_access_token directly from Lark Open API.
     */
    public function requestTenantAccessToken(string $appId, string $appSecret): string
    {
        $baseUrl = rtrim(config('lark.base_url', 'https://open.larksuite.com/open-apis'), '/');
        $endpoint = $baseUrl.'/auth/v3/tenant_access_token/internal';

        $response = Http::timeout(config('lark.timeout', 15))
            ->asJson()
            ->post($endpoint, [
                'app_id' => $appId,
                'app_secret' => $appSecret,
            ]);

        if (! $response->successful()) {
            Log::error('Lark auth request failed (HTTP error)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Lark HTTP Error: '.$response->status());
        }

        $data = $response->json();

        if (($data['code'] ?? -1) !== 0) {
            $msg = $data['msg'] ?? 'Unknown Lark error';
            Log::error('Lark auth response code error', [
                'code' => $data['code'] ?? null,
                'msg' => $msg,
            ]);
            throw new Exception('Lark API Error ('.$data['code'].'): '.$msg);
        }

        return (string) $data['tenant_access_token'];
    }

    /**
     * Test given credentials without saving them to DB.
     */
    public function testCredentials(string $appId, string $appSecret): array
    {
        try {
            $token = $this->requestTenantAccessToken($appId, $appSecret);

            return [
                'success' => ! empty($token),
                'message' => 'Koneksi ke Lark berhasil diverifikasi.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Gagal terhubung ke Lark: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Clear cached token for the integration.
     */
    public function clearToken(LarkIntegration $integration): void
    {
        $cacheKey = config('lark.cache_prefix', 'lark_tenant_token:').$integration->company_id;
        Cache::forget($cacheKey);
    }
}
