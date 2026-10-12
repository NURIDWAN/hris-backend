<?php

use App\Models\Company;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
});

describe('DeviceToken Model', function () {
    it('can be created with factory', function () {
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
        ]);

        expect($deviceToken)->toBeInstanceOf(DeviceToken::class);
        expect($deviceToken->user_id)->toBe($this->user->id);
    });

    it('belongs to a user', function () {
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
        ]);

        expect($deviceToken->user)->toBeInstanceOf(User::class);
        expect($deviceToken->user->id)->toBe($this->user->id);
    });

    it('stores token as unique', function () {
        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'unique-fcm-token-123',
        ]);

        expect(fn () => DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'unique-fcm-token-123',
        ]))->toThrow(\Illuminate\Database\QueryException::class);
    });

    it('casts is_active to boolean', function () {
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        expect($deviceToken->is_active)->toBeTrue();
    });

    it('casts last_used_at to datetime', function () {
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now(),
        ]);

        expect($deviceToken->last_used_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('stores platform as enum', function () {
        $androidToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'android',
        ]);

        $iosToken = DeviceToken::factory()->create([
            'user_id' => User::factory()->create(['company_id' => $this->company->id])->id,
            'platform' => 'ios',
        ]);

        $webToken = DeviceToken::factory()->create([
            'user_id' => User::factory()->create(['company_id' => $this->company->id])->id,
            'platform' => 'web',
        ]);

        expect($androidToken->platform)->toBe('android');
        expect($iosToken->platform)->toBe('ios');
        expect($webToken->platform)->toBe('web');
    });

    it('can store device info', function () {
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'device_name' => 'iPhone 14 Pro',
            'device_model' => 'iPhone14,3',
            'app_version' => '1.2.0',
        ]);

        expect($deviceToken->device_name)->toBe('iPhone 14 Pro');
        expect($deviceToken->device_model)->toBe('iPhone14,3');
        expect($deviceToken->app_version)->toBe('1.2.0');
    });

    it('has scope for active tokens', function () {
        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $user2 = User::factory()->create(['company_id' => $this->company->id]);
        DeviceToken::factory()->create([
            'user_id' => $user2->id,
            'is_active' => false,
        ]);

        $activeTokens = DeviceToken::active()->get();

        expect($activeTokens)->toHaveCount(1);
    });

    it('has scope for platform filter', function () {
        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'platform' => 'android',
        ]);

        $user2 = User::factory()->create(['company_id' => $this->company->id]);
        DeviceToken::factory()->create([
            'user_id' => $user2->id,
            'platform' => 'ios',
        ]);

        $androidTokens = DeviceToken::platform('android')->get();
        $iosTokens = DeviceToken::platform('ios')->get();

        expect($androidTokens)->toHaveCount(1);
        expect($iosTokens)->toHaveCount(1);
    });

    it('can update last used timestamp', function () {
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'last_used_at' => now()->subDays(5),
        ]);

        $deviceToken->markAsUsed();

        expect($deviceToken->fresh()->last_used_at->isToday())->toBeTrue();
    });

    it('user can have multiple device tokens', function () {
        DeviceToken::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        expect($this->user->deviceTokens)->toHaveCount(3);
    });
});

describe('User deviceTokens relationship', function () {
    it('has many device tokens', function () {
        DeviceToken::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        expect($this->user->deviceTokens)->toHaveCount(2);
    });

    it('can get active device tokens only', function () {
        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => false,
        ]);

        $activeTokens = $this->user->deviceTokens()->where('is_active', true)->get();

        expect($activeTokens)->toHaveCount(1);
    });
});

describe('Notification push columns', function () {
    it('can track push_sent_at', function () {
        $notification = \App\Models\Notification::factory()->create([
            'user_id' => $this->user->id,
            'push_sent_at' => now(),
        ]);

        expect($notification->push_sent_at)->toBeInstanceOf(\Carbon\Carbon::class);
    });

    it('can track push_error', function () {
        $notification = \App\Models\Notification::factory()->create([
            'user_id' => $this->user->id,
            'push_error' => 'Invalid token',
        ]);

        expect($notification->push_error)->toBe('Invalid token');
    });
});
