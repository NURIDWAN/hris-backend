<?php

use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('Device Token API', function () {
    describe('POST /api/v1/device-tokens/register', function () {
        it('registers a new device token', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/device-tokens/register', [
                'token' => 'fcm-token-123456789',
                'platform' => 'android',
                'device_name' => 'Samsung Galaxy S23',
                'device_model' => 'SM-S918B',
                'app_version' => '1.0.0',
            ]);

            $response->assertCreated()
                ->assertJsonPath('message', 'Device token registered successfully.')
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'platform',
                        'device_name',
                    ],
                ]);

            $this->assertDatabaseHas('device_tokens', [
                'user_id' => $this->user->id,
                'token' => 'fcm-token-123456789',
                'platform' => 'android',
            ]);
        });

        it('updates existing token if same token already exists', function () {
            Sanctum::actingAs($this->user, ['*']);

            $existingToken = DeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'token' => 'existing-fcm-token',
                'platform' => 'android',
                'device_name' => 'Old Device',
            ]);

            $response = $this->postJson('/api/v1/device-tokens/register', [
                'token' => 'existing-fcm-token',
                'platform' => 'android',
                'device_name' => 'New Device Name',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Device token updated successfully.');

            expect($existingToken->fresh()->device_name)->toBe('New Device Name');
            expect(DeviceToken::where('token', 'existing-fcm-token')->count())->toBe(1);
        });

        it('validates required fields', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/device-tokens/register', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['token', 'platform']);
        });

        it('validates platform enum', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/device-tokens/register', [
                'token' => 'fcm-token-123',
                'platform' => 'invalid_platform',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['platform']);
        });

        it('returns 401 when not authenticated', function () {
            $response = $this->postJson('/api/v1/device-tokens/register', [
                'token' => 'fcm-token-123',
                'platform' => 'android',
            ]);

            $response->assertUnauthorized();
        });

        it('marks token as active and updates last_used_at', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/device-tokens/register', [
                'token' => 'fcm-token-123',
                'platform' => 'ios',
            ]);

            $response->assertCreated();

            $token = DeviceToken::where('token', 'fcm-token-123')->first();
            expect($token->is_active)->toBeTrue();
            expect($token->last_used_at)->not->toBeNull();
        });
    });

    describe('DELETE /api/v1/device-tokens/unregister', function () {
        it('removes device token', function () {
            Sanctum::actingAs($this->user, ['*']);

            DeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'token' => 'token-to-remove',
            ]);

            $response = $this->deleteJson('/api/v1/device-tokens/unregister', [
                'token' => 'token-to-remove',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Device token unregistered successfully.');

            $this->assertDatabaseMissing('device_tokens', [
                'token' => 'token-to-remove',
            ]);
        });

        it('returns 404 when token not found', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->deleteJson('/api/v1/device-tokens/unregister', [
                'token' => 'non-existent-token',
            ]);

            $response->assertNotFound();
        });

        it('cannot remove other users token', function () {
            Sanctum::actingAs($this->user, ['*']);

            $otherUser = User::factory()->create(['company_id' => $this->company->id]);
            DeviceToken::factory()->create([
                'user_id' => $otherUser->id,
                'token' => 'other-user-token',
            ]);

            $response = $this->deleteJson('/api/v1/device-tokens/unregister', [
                'token' => 'other-user-token',
            ]);

            $response->assertNotFound();
        });

        it('validates required token', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->deleteJson('/api/v1/device-tokens/unregister', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['token']);
        });
    });

    describe('POST /api/v1/device-tokens/refresh', function () {
        it('refreshes device token with new value', function () {
            Sanctum::actingAs($this->user, ['*']);

            DeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'token' => 'old-token',
                'platform' => 'android',
            ]);

            $response = $this->postJson('/api/v1/device-tokens/refresh', [
                'old_token' => 'old-token',
                'new_token' => 'new-token-123',
            ]);

            $response->assertOk()
                ->assertJsonPath('message', 'Device token refreshed successfully.');

            $this->assertDatabaseMissing('device_tokens', [
                'token' => 'old-token',
            ]);

            $this->assertDatabaseHas('device_tokens', [
                'user_id' => $this->user->id,
                'token' => 'new-token-123',
            ]);
        });

        it('returns 404 when old token not found', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/device-tokens/refresh', [
                'old_token' => 'non-existent-token',
                'new_token' => 'new-token-123',
            ]);

            $response->assertNotFound();
        });

        it('validates required fields', function () {
            Sanctum::actingAs($this->user, ['*']);

            $response = $this->postJson('/api/v1/device-tokens/refresh', []);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['old_token', 'new_token']);
        });

        it('updates last_used_at on refresh', function () {
            Sanctum::actingAs($this->user, ['*']);

            DeviceToken::factory()->create([
                'user_id' => $this->user->id,
                'token' => 'old-token',
                'last_used_at' => now()->subDays(5),
            ]);

            $this->postJson('/api/v1/device-tokens/refresh', [
                'old_token' => 'old-token',
                'new_token' => 'new-token-123',
            ]);

            $token = DeviceToken::where('token', 'new-token-123')->first();
            expect($token->last_used_at->isToday())->toBeTrue();
        });
    });

    describe('GET /api/v1/device-tokens', function () {
        it('lists all device tokens for user', function () {
            Sanctum::actingAs($this->user, ['*']);

            DeviceToken::factory()->count(3)->create([
                'user_id' => $this->user->id,
            ]);

            $response = $this->getJson('/api/v1/device-tokens');

            $response->assertOk()
                ->assertJsonCount(3, 'data')
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'platform',
                            'device_name',
                            'device_model',
                            'app_version',
                            'is_active',
                            'last_used_at',
                        ],
                    ],
                ]);
        });

        it('does not list other users tokens', function () {
            Sanctum::actingAs($this->user, ['*']);

            DeviceToken::factory()->count(2)->create([
                'user_id' => $this->user->id,
            ]);

            $otherUser = User::factory()->create(['company_id' => $this->company->id]);
            DeviceToken::factory()->count(3)->create([
                'user_id' => $otherUser->id,
            ]);

            $response = $this->getJson('/api/v1/device-tokens');

            $response->assertOk()
                ->assertJsonCount(2, 'data');
        });
    });
});
