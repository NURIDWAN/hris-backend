<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected string $projectId;

    protected ?string $serviceAccountPath;

    protected bool $isTestMode;

    public function __construct()
    {
        $this->projectId = config('services.firebase.project_id', '');
        $this->serviceAccountPath = config('services.firebase.credentials');
        $this->isTestMode = app()->environment('testing') || empty($this->projectId);
    }

    /**
     * Send notification to a single device token.
     *
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    public function sendToDevice(string $token, array $notification, array $data = []): array
    {
        if ($this->isTestMode) {
            return $this->simulateSuccess($token);
        }

        $payload = $this->buildPayload($token, $notification, $data);

        return $this->sendRequest($payload);
    }

    /**
     * Send notification to all active device tokens of a user.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function sendToUser(User $user, array $notification, array $data = []): array
    {
        $tokens = DeviceToken::where('user_id', $user->id)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'total' => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'results' => [],
            ];
        }

        return $this->sendToMultipleDevices($tokens, $notification, $data);
    }

    /**
     * Send notification to multiple device tokens.
     *
     * @return array{total: int, success_count: int, failure_count: int, results: array}
     */
    public function sendToMultipleDevices(array $tokens, array $notification, array $data = []): array
    {
        if (empty($tokens)) {
            return [
                'total' => 0,
                'success_count' => 0,
                'failure_count' => 0,
                'results' => [],
            ];
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($tokens as $token) {
            $result = $this->sendToDevice($token, $notification, $data);
            $results[] = $result;

            if ($result['success']) {
                $successCount++;
            } else {
                $failureCount++;

                // Deactivate invalid tokens
                if ($this->isInvalidTokenError($result['error'] ?? '')) {
                    $this->deactivateToken($token);
                }
            }
        }

        return [
            'total' => count($tokens),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ];
    }

    /**
     * Build FCM payload structure.
     */
    public function buildPayload(string $token, array $notification, array $data = []): array
    {
        $payload = [
            'token' => $token,
            'notification' => [
                'title' => $notification['title'] ?? '',
                'body' => $notification['body'] ?? '',
            ],
            'data' => array_map('strval', $data), // FCM requires string values
            'android' => [
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ],
            'apns' => [
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ],
        ];

        // Add image if provided
        if (! empty($notification['image'])) {
            $payload['notification']['image'] = $notification['image'];
        }

        return $payload;
    }

    /**
     * Send FCM request via HTTP v1 API.
     *
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    protected function sendRequest(array $payload): array
    {
        try {
            $accessToken = $this->getAccessToken();

            if (! $accessToken) {
                return [
                    'success' => false,
                    'message_id' => null,
                    'error' => 'Failed to get access token',
                ];
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post($url, ['message' => $payload]);

            if ($response->successful()) {
                $body = $response->json();

                return [
                    'success' => true,
                    'message_id' => $body['name'] ?? null,
                    'error' => null,
                ];
            }

            $error = $response->json('error.message') ?? 'Unknown error';
            Log::warning('FCM send failed', [
                'error' => $error,
                'token' => substr($payload['token'], 0, 20).'...',
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $error,
            ];
        } catch (\Exception $e) {
            Log::error('FCM exception', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get OAuth2 access token for FCM HTTP v1 API.
     */
    protected function getAccessToken(): ?string
    {
        if (! $this->serviceAccountPath || ! file_exists($this->serviceAccountPath)) {
            return null;
        }

        try {
            $credentials = json_decode(file_get_contents($this->serviceAccountPath), true);

            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = base64_encode(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signature = '';
            openssl_sign(
                "$header.$payload",
                $signature,
                $credentials['private_key'],
                'sha256'
            );
            $signature = base64_encode($signature);

            $jwt = "$header.$payload.$signature";

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                return $response->json('access_token');
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Failed to get FCM access token', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Simulate successful send for testing.
     *
     * @return array{success: bool, message_id: string|null, error: string|null}
     */
    protected function simulateSuccess(string $token): array
    {
        return [
            'success' => true,
            'message_id' => 'test-message-id-'.uniqid(),
            'error' => null,
        ];
    }

    /**
     * Check if error indicates invalid/expired token.
     */
    protected function isInvalidTokenError(string $error): bool
    {
        $invalidErrors = [
            'UNREGISTERED',
            'INVALID_ARGUMENT',
            'NOT_FOUND',
        ];

        foreach ($invalidErrors as $invalidError) {
            if (str_contains(strtoupper($error), $invalidError)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deactivate an invalid device token.
     */
    protected function deactivateToken(string $token): void
    {
        DeviceToken::where('token', $token)->update(['is_active' => false]);
    }
}
