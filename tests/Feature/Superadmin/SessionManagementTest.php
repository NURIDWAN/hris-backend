<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'is_superadmin' => true,
        'company_id' => null,
        'is_active' => true,
    ]);

    $this->regularUser = User::factory()->create([
        'is_superadmin' => false,
        'is_active' => true,
    ]);
});

describe('Session Management (Superadmin)', function () {
    it('can access sessions page as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/sessions');

        $response->assertOk()
            ->assertViewIs('superadmin.system.sessions.index')
            ->assertViewHas('sessions')
            ->assertViewHas('stats');
    });

    it('displays all active sessions with user info', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        DB::table('sessions')->insert([
            'id' => 'test-session-1',
            'user_id' => $user->id,
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X) Chrome/120.0.0.0 Safari/537.36',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/sessions');

        $response->assertOk()
            ->assertSee('192.168.1.100')
            ->assertSee($user->name);
    });

    it('displays session stats', function () {
        DB::table('sessions')->insert([
            ['id' => 'sess-1', 'user_id' => $this->superadmin->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'Chrome', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'sess-2', 'user_id' => $this->regularUser->id, 'ip_address' => '10.0.0.2', 'user_agent' => 'Firefox', 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => 'sess-3', 'user_id' => null, 'ip_address' => '10.0.0.3', 'user_agent' => 'Safari', 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/sessions');

        $stats = $response->viewData('stats');
        expect($stats['total'])->toBe(3)
            ->and($stats['authenticated'])->toBe(2)
            ->and($stats['guest'])->toBe(1);
    });

    it('can revoke a session', function () {
        DB::table('sessions')->insert([
            'id' => 'revoke-me',
            'user_id' => $this->regularUser->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Test',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->superadmin)
            ->delete('/superadmin/system/sessions/revoke-me');

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'revoke-me']);
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/sessions');

        $response->assertRedirect('/superadmin/login');
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->get('/superadmin/system/sessions');

        $response->assertRedirect('/login');
    });
});
