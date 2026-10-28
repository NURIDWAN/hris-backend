<?php

namespace App\Services\Lark;

use App\Models\Employee;
use App\Models\LarkIntegration;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LarkWebhookService
{
    /**
     * Process incoming webhook request from Lark.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();

        // Check if payload is encrypted
        if (! empty($payload['encrypt'])) {
            $payload = $this->decryptPayload($payload['encrypt']);
            if (! $payload) {
                return response()->json(['code' => 400, 'msg' => 'Failed to decrypt payload'], 400);
            }
        }

        // 1. URL Verification Challenge (Handshake Lark Developer Console)
        if (($payload['type'] ?? null) === 'url_verification') {
            Log::info('Lark Webhook: URL verification challenge received', [
                'challenge' => $payload['challenge'] ?? null,
            ]);

            return response()->json([
                'challenge' => $payload['challenge'] ?? '',
            ]);
        }

        // 2. Token verification
        $token = $payload['header']['token'] ?? ($payload['token'] ?? null);
        $appId = $payload['header']['app_id'] ?? ($payload['app_id'] ?? null);

        $integration = null;
        if ($appId) {
            $integration = LarkIntegration::where('app_id', $appId)->first();
        } elseif ($token) {
            $integration = LarkIntegration::where('verification_token', $token)->first();
        }

        if ($integration && ! empty($integration->verification_token)) {
            if ($token !== $integration->verification_token) {
                Log::warning('Lark Webhook: Verification token mismatch', [
                    'received_token' => $token,
                ]);

                return response()->json(['code' => 403, 'msg' => 'Invalid verification token'], 403);
            }
        }

        // 3. Process Event Schema 2.0
        $schema = $payload['schema'] ?? null;
        $eventType = $payload['header']['event_type'] ?? ($payload['event']['type'] ?? null);

        if ($eventType) {
            $this->dispatchEvent($eventType, $payload['event'] ?? [], $integration);
        }

        return response()->json(['code' => 0, 'msg' => 'success']);
    }

    /**
     * Dispatch Lark events to their corresponding internal handlers.
     */
    protected function dispatchEvent(string $eventType, array $eventData, ?LarkIntegration $integration): void
    {
        Log::info("Lark Webhook: Received event [{$eventType}]", [
            'event_type' => $eventType,
        ]);

        switch ($eventType) {
            case 'contact.user.created_v3':
            case 'contact.user.updated_v3':
                $this->handleContactUserEvent($eventData, $integration);
                break;

            case 'attendance.user_task.updated_v1':
                $this->handleAttendanceTaskUpdated($eventData, $integration);
                break;

            default:
                Log::info("Lark Webhook: Unhandled event type [{$eventType}]");
                break;
        }
    }

    /**
     * Handle contact user created or updated event in Lark.
     * Automatically maps employee email to Lark User ID.
     */
    protected function handleContactUserEvent(array $eventData, ?LarkIntegration $integration): void
    {
        $user = $eventData['object'] ?? ($eventData['user'] ?? []);
        $email = strtolower(trim($user['primary_email'] ?? ($user['email'] ?? '')));
        $larkUserId = $user['user_id'] ?? null;
        $larkOpenId = $user['open_id'] ?? null;

        if (empty($email) || empty($larkUserId)) {
            return;
        }

        $query = Employee::whereRaw('LOWER(email) = ?', [$email]);
        if ($integration) {
            $query->where('company_id', $integration->company_id);
        }

        $employee = $query->first();

        if ($employee) {
            $employee->update([
                'lark_user_id' => $larkUserId,
                'lark_open_id' => $larkOpenId ?: $employee->lark_open_id,
            ]);

            Log::info("Lark Webhook: Auto-mapped employee [{$employee->id}] to Lark User ID [{$larkUserId}]");
        }
    }

    /**
     * Handle attendance task updated event from Lark.
     */
    protected function handleAttendanceTaskUpdated(array $eventData, ?LarkIntegration $integration): void
    {
        Log::info('Lark Webhook: Attendance task update received', [
            'data' => $eventData,
        ]);
    }

    /**
     * Decrypt encrypted payload from Lark using AES-256-CBC.
     */
    public function decryptPayload(string $encryptedData): ?array
    {
        $integrations = LarkIntegration::whereNotNull('encrypt_key')->get();

        foreach ($integrations as $integration) {
            $encryptKey = $integration->encrypt_key;
            if (empty($encryptKey)) {
                continue;
            }

            try {
                $decryptedJson = $this->decryptAes($encryptedData, $encryptKey);
                if ($decryptedJson) {
                    $decoded = json_decode($decryptedJson, true);
                    if (is_array($decoded)) {
                        return $decoded;
                    }
                }
            } catch (Exception) {
                continue;
            }
        }

        Log::warning('Lark Webhook: Failed to decrypt payload with available integration keys');

        return null;
    }

    /**
     * Perform AES-256-CBC decryption matching Lark's algorithm.
     */
    protected function decryptAes(string $encryptedBase64, string $key): ?string
    {
        $raw = base64_decode($encryptedBase64);
        if (strlen($raw) < 16) {
            return null;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $keyHash = hash('sha256', $key, true);

        $decrypted = openssl_decrypt(
            $ciphertext,
            'AES-256-CBC',
            $keyHash,
            OPENSSL_RAW_DATA,
            $iv
        );

        return $decrypted ?: null;
    }
}
