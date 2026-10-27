<?php

namespace App\Services\Lark;

use App\Models\LarkIntegration;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LarkApiClient
{
    public function __construct(
        protected LarkAuthService $authService
    ) {}

    /**
     * Perform an authenticated GET request to Lark Open API.
     */
    public function get(LarkIntegration $integration, string $endpoint, array $query = []): array
    {
        return $this->request($integration, 'GET', $endpoint, $query);
    }

    /**
     * Perform an authenticated POST request to Lark Open API.
     */
    public function post(LarkIntegration $integration, string $endpoint, array $payload = []): array
    {
        return $this->request($integration, 'POST', $endpoint, $payload);
    }

    /**
     * Core request handler with token injection and automatic token refresh on expiry.
     */
    protected function request(LarkIntegration $integration, string $method, string $endpoint, array $data = []): array
    {
        $baseUrl = rtrim(config('lark.base_url', 'https://open.larksuite.com/open-apis'), '/');
        $url = str_starts_with($endpoint, 'http') ? $endpoint : $baseUrl.'/'.ltrim($endpoint, '/');

        $token = $this->authService->getTenantAccessToken($integration);

        $response = $this->sendHttpRequest($method, $url, $token, $data);

        // Check if token expired error returned by Lark (code 99991663 or 99991664)
        $body = $response->json();
        $code = $body['code'] ?? null;

        if (in_array($code, [99991663, 99991664], true)) {
            Log::info('Lark token expired during request, refreshing token...', [
                'company_id' => $integration->company_id,
            ]);
            $this->authService->clearToken($integration);
            $token = $this->authService->getTenantAccessToken($integration);
            $response = $this->sendHttpRequest($method, $url, $token, $data);
            $body = $response->json();
            $code = $body['code'] ?? null;
        }

        if (! $response->successful()) {
            Log::error('Lark API HTTP Error', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new Exception('Lark HTTP Error: '.$response->status());
        }

        if (($code ?? -1) !== 0) {
            $msg = $body['msg'] ?? 'Unknown error';
            Log::error('Lark API Business Error', [
                'url' => $url,
                'code' => $code,
                'msg' => $msg,
            ]);
            throw new Exception("Lark Error ({$code}): {$msg}");
        }

        return $body;
    }

    /**
     * Send HTTP request with retry policy.
     */
    protected function sendHttpRequest(string $method, string $url, string $token, array $data = [])
    {
        $http = Http::withToken($token)
            ->timeout(config('lark.timeout', 15))
            ->retry(config('lark.retry_times', 3), config('lark.retry_sleep', 250));

        if (strtoupper($method) === 'GET') {
            return $http->get($url, $data);
        }

        return $http->asJson()->post($url, $data);
    }
}
