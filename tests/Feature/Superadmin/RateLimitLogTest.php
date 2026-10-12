<?php

use App\Http\Middleware\LogRateLimitHit;
use App\Models\RateLimitLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

describe('Rate Limit Logs', function () {
    it('can access rate limit logs page as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/rate-limits');

        $response->assertOk()
            ->assertViewIs('superadmin.system.rate-limits.index')
            ->assertViewHas('logs')
            ->assertViewHas('stats');
    });

    it('displays rate limit logs', function () {
        RateLimitLog::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/rate-limits');

        $response->assertOk();
        $logs = $response->viewData('logs');
        expect($logs->total())->toBe(3);
    });

    it('displays correct stats', function () {
        RateLimitLog::factory()->count(5)->create([
            'ip_address' => '10.0.0.1',
            'route' => '/login',
            'created_at' => now(),
        ]);
        RateLimitLog::factory()->count(3)->create([
            'ip_address' => '10.0.0.2',
            'route' => '/api/v1/auth',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/rate-limits');

        $stats = $response->viewData('stats');
        expect($stats['total_24h'])->toBe(8)
            ->and($stats['unique_ips_24h'])->toBe(2);
    });

    it('can clear old logs', function () {
        RateLimitLog::factory()->create(['created_at' => now()->subDays(40)]);
        RateLimitLog::factory()->create(['created_at' => now()]);

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/system/rate-limits/clear', ['days' => 30]);

        $response->assertRedirect();
        expect(RateLimitLog::count())->toBe(1);
    });

    it('middleware logs 429 responses', function () {
        $middleware = new LogRateLimitHit;

        $request = Request::create('/login', 'POST');
        $request->server->set('REMOTE_ADDR', '192.168.1.50');

        $response = new Response('Too Many Requests', 429);

        $middleware->terminate($request, $response);

        expect(RateLimitLog::count())->toBe(1);
        $log = RateLimitLog::first();
        expect($log->ip_address)->toBe('192.168.1.50')
            ->and($log->route)->toBe('login')
            ->and($log->method)->toBe('POST');
    });

    it('middleware does not log non-429 responses', function () {
        $middleware = new LogRateLimitHit;

        $request = Request::create('/login', 'POST');
        $response = new Response('OK', 200);

        $middleware->terminate($request, $response);

        expect(RateLimitLog::count())->toBe(0);
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/rate-limits');

        $response->assertRedirect('/superadmin/login');
    });

    it('denies access to unauthenticated users', function () {
        $response = $this->get('/superadmin/system/rate-limits');

        $response->assertRedirect('/login');
    });
});
