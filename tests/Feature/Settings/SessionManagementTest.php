<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);
    setPermissionsTeamId($this->company->id);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
        'is_active' => true,
    ]);
    $this->user->assignRole('admin');
});

describe('Session Management (Settings)', function () {
    it('can access sessions page', function () {
        $response = $this->actingAs($this->user)
            ->get('/settings/sessions');

        $response->assertOk()
            ->assertViewIs('settings.sessions.index')
            ->assertViewHas('sessions');
    });

    it('displays user sessions', function () {
        // Insert a fake session for this user
        DB::table('sessions')->insert([
            'id' => 'test-session-id',
            'user_id' => $this->user->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->user)
            ->get('/settings/sessions');

        $response->assertOk()
            ->assertSee('192.168.1.1')
            ->assertSee('Chrome')
            ->assertSee('Windows');
    });

    it('marks current session', function () {
        $response = $this->actingAs($this->user)
            ->withSession(['_token' => 'test'])
            ->get('/settings/sessions');

        $response->assertOk()
            ->assertViewHas('currentSessionId');
    });

    it('can revoke other session', function () {
        DB::table('sessions')->insert([
            'id' => 'other-session-id',
            'user_id' => $this->user->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Test Agent',
            'payload' => '',
            'last_activity' => now()->subHour()->timestamp,
        ]);

        $response = $this->actingAs($this->user)
            ->delete('/settings/sessions/other-session-id');

        $response->assertRedirect();
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session-id']);
    });

    it('returns current session id in view', function () {
        $response = $this->actingAs($this->user)
            ->get('/settings/sessions');

        $response->assertOk();
        $currentSessionId = $response->viewData('currentSessionId');
        expect($currentSessionId)->toBeString()->not->toBeEmpty();
    });

    it('cannot revoke another users session', function () {
        $otherUser = User::factory()->create(['company_id' => $this->company->id]);

        DB::table('sessions')->insert([
            'id' => 'other-user-session',
            'user_id' => $otherUser->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Test Agent',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $response = $this->actingAs($this->user)
            ->delete('/settings/sessions/other-user-session');

        $response->assertNotFound();
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->get('/settings/sessions');

        $response->assertRedirect('/login');
    });
});
