<?php

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

function createFailedJob(array $overrides = []): int
{
    return DB::table('failed_jobs')->insertGetId(array_merge([
        'uuid' => (string) \Illuminate\Support\Str::uuid(),
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode([
            'displayName' => 'App\\Jobs\\TestJob',
            'job' => 'Illuminate\\Queue\\CallQueuedHandler@call',
            'data' => ['commandName' => 'App\\Jobs\\TestJob'],
        ]),
        'exception' => "RuntimeException: Test exception in /app/Jobs/TestJob.php:25\nStack trace:\n#0 ...",
        'failed_at' => now(),
    ], $overrides));
}

describe('Queue Monitor Index', function () {
    it('can access queue monitor page as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/queue');

        $response->assertOk()
            ->assertViewIs('superadmin.system.queue.index')
            ->assertViewHas('failedJobs')
            ->assertViewHas('stats');
    });

    it('displays failed jobs in the list', function () {
        createFailedJob();
        createFailedJob();

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/queue');

        $response->assertOk();
        expect($response->viewData('stats')['failed'])->toBe(2);
    });

    it('displays queue statistics', function () {
        createFailedJob();
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => json_encode(['displayName' => 'TestJob']),
            'attempts' => 0,
            'available_at' => time(),
            'created_at' => time(),
        ]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/queue');

        $stats = $response->viewData('stats');
        expect($stats['pending'])->toBe(1);
        expect($stats['failed'])->toBe(1);
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/queue');

        $response->assertRedirect('/superadmin/login');
    });
});

describe('Queue Monitor Show', function () {
    it('can view failed job detail', function () {
        $id = createFailedJob();

        $response = $this->actingAs($this->superadmin)
            ->get("/superadmin/system/queue/{$id}");

        $response->assertOk()
            ->assertViewIs('superadmin.system.queue.show')
            ->assertViewHas('job');
    });

    it('returns 404 for non-existent job', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/queue/99999');

        $response->assertNotFound();
    });
});

describe('Queue Monitor Actions', function () {
    it('can retry a single failed job', function () {
        $id = createFailedJob();

        $response = $this->actingAs($this->superadmin)
            ->post("/superadmin/system/queue/{$id}/retry");

        $response->assertRedirect();
        $response->assertSessionHas('success');
    });

    it('can delete a single failed job', function () {
        $id = createFailedJob();

        $response = $this->actingAs($this->superadmin)
            ->delete("/superadmin/system/queue/{$id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('failed_jobs', ['id' => $id]);
    });

    it('can flush all failed jobs', function () {
        createFailedJob();
        createFailedJob();

        $response = $this->actingAs($this->superadmin)
            ->post('/superadmin/system/queue/flush');

        $response->assertRedirect();
        expect(DB::table('failed_jobs')->count())->toBe(0);
    });
});
