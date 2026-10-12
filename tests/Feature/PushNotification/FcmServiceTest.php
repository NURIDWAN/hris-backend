<?php

use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('FcmService', function () {
    describe('sendToDevice', function () {
        it('sends notification to single device token', function () {
            $fcmService = app(FcmService::class);

            $deviceToken = DeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'token' => 'test-fcm-token-123',
                'platform' => 'android',
                'is_active' => true,
            ]);

            $result = $fcmService->sendToDevice(
                $deviceToken->token,
                [
                    'title' => 'Test Notification',
                    'body' => 'This is a test message',
                ],
                ['type' => 'test', 'id' => '123']
            );

            // In test mode, should return simulated success
            expect($result)->toBeArray();
            expect($result)->toHaveKey('success');
        });

        it('handles invalid token gracefully', function () {
            $fcmService = app(FcmService::class);

            $result = $fcmService->sendToDevice(
                'invalid-token',
                [
                    'title' => 'Test',
                    'body' => 'Test message',
                ],
                []
            );

            expect($result)->toBeArray();
            expect($result)->toHaveKey('success');
        });
    });

    describe('sendToUser', function () {
        it('sends notification to all user devices', function () {
            $fcmService = app(FcmService::class);

            // Create multiple device tokens for user
            DeviceToken::factory()->count(3)->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            $result = $fcmService->sendToUser(
                $this->user,
                [
                    'title' => 'Test Notification',
                    'body' => 'Sent to all devices',
                ],
                ['type' => 'broadcast']
            );

            expect($result)->toBeArray();
            expect($result)->toHaveKey('total');
            expect($result['total'])->toBe(3);
        });

        it('returns empty result when user has no active tokens', function () {
            $fcmService = app(FcmService::class);

            // User has no device tokens
            $result = $fcmService->sendToUser(
                $this->user,
                [
                    'title' => 'Test',
                    'body' => 'No devices',
                ],
                []
            );

            expect($result)->toBeArray();
            expect($result['total'])->toBe(0);
        });

        it('only sends to active device tokens', function () {
            $fcmService = app(FcmService::class);

            // Create active and inactive tokens
            DeviceToken::factory()->count(2)->create([
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);

            DeviceToken::factory()->count(3)->create([
                'user_id' => $this->user->id,
                'is_active' => false,
            ]);

            $result = $fcmService->sendToUser(
                $this->user,
                ['title' => 'Test', 'body' => 'Active only'],
                []
            );

            expect($result['total'])->toBe(2);
        });
    });

    describe('sendToMultipleDevices', function () {
        it('sends notification to multiple tokens', function () {
            $fcmService = app(FcmService::class);

            $tokens = [
                'token-1',
                'token-2',
                'token-3',
            ];

            $result = $fcmService->sendToMultipleDevices(
                $tokens,
                [
                    'title' => 'Broadcast',
                    'body' => 'To multiple devices',
                ],
                ['type' => 'announcement']
            );

            expect($result)->toBeArray();
            expect($result)->toHaveKey('success_count');
            expect($result)->toHaveKey('failure_count');
        });

        it('handles empty token array', function () {
            $fcmService = app(FcmService::class);

            $result = $fcmService->sendToMultipleDevices(
                [],
                ['title' => 'Test', 'body' => 'Empty'],
                []
            );

            expect($result['success_count'])->toBe(0);
            expect($result['failure_count'])->toBe(0);
        });
    });

    describe('buildPayload', function () {
        it('creates proper FCM payload structure', function () {
            $fcmService = app(FcmService::class);

            $payload = $fcmService->buildPayload(
                'test-token',
                [
                    'title' => 'Notification Title',
                    'body' => 'Notification body text',
                    'image' => 'https://example.com/image.png',
                ],
                [
                    'type' => 'attendance',
                    'attendance_id' => '123',
                ]
            );

            expect($payload)->toBeArray();
            expect($payload)->toHaveKey('token');
            expect($payload)->toHaveKey('notification');
            expect($payload)->toHaveKey('data');
            expect($payload['token'])->toBe('test-token');
            expect($payload['notification']['title'])->toBe('Notification Title');
            expect($payload['data']['type'])->toBe('attendance');
        });

        it('sets android and ios specific config', function () {
            $fcmService = app(FcmService::class);

            $payload = $fcmService->buildPayload(
                'test-token',
                ['title' => 'Test', 'body' => 'Test'],
                []
            );

            expect($payload)->toHaveKey('android');
            expect($payload)->toHaveKey('apns');
        });
    });
});
