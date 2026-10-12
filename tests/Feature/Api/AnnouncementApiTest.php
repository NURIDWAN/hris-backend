<?php

use App\Models\Announcement;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->department = Department::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $this->user->id,
        'department_id' => $this->department->id,
    ]);
});

describe('GET /api/v1/announcements', function () {
    it('returns list of announcements for user', function () {
        Sanctum::actingAs($this->user);

        // Create announcement for all employees
        Announcement::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'content',
                        'priority',
                        'priority_label',
                        'is_pinned',
                        'is_read',
                        'published_at',
                        'created_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'last_page',
                    'per_page',
                    'total',
                ],
            ]);

        expect(count($response->json('data')))->toBe(3);
    });

    it('shows announcements targeted to user department', function () {
        Sanctum::actingAs($this->user);

        // Announcement for user's department
        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_DEPARTMENT,
            'target_ids' => [$this->department->id],
            'is_published' => true,
        ]);

        // Announcement for other department
        $otherDept = Department::factory()->create(['company_id' => $this->company->id]);
        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_DEPARTMENT,
            'target_ids' => [$otherDept->id],
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('does not show unpublished announcements', function () {
        Sanctum::actingAs($this->user);

        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => false,
        ]);

        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('does not show expired announcements', function () {
        Sanctum::actingAs($this->user);

        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
            'expires_at' => now()->subDay(), // Expired
        ]);

        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
            'expires_at' => now()->addDay(), // Not expired
        ]);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertOk();
        expect(count($response->json('data')))->toBe(1);
    });

    it('orders pinned announcements first', function () {
        Sanctum::actingAs($this->user);

        $regularAnnouncement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
            'is_pinned' => false,
            'created_at' => now(),
        ]);

        $pinnedAnnouncement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
            'is_pinned' => true,
            'created_at' => now()->subDay(),
        ]);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertOk();
        $data = $response->json('data');
        expect($data[0]['id'])->toBe($pinnedAnnouncement->id);
    });

    it('shows read status correctly', function () {
        Sanctum::actingAs($this->user);

        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        // Mark as read
        $announcement->markAsRead($this->user->id);

        $response = $this->getJson('/api/v1/announcements');

        $response->assertOk();
        expect($response->json('data.0.is_read'))->toBeTrue();
    });
});

describe('GET /api/v1/announcements/{id}', function () {
    it('returns announcement detail', function () {
        Sanctum::actingAs($this->user);

        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/announcements/{$announcement->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'title',
                    'content',
                    'priority',
                    'priority_label',
                    'is_pinned',
                    'is_read',
                    'published_at',
                    'created_at',
                    'creator' => [
                        'name',
                    ],
                ],
            ]);
    });

    it('returns 404 for unpublished announcement', function () {
        Sanctum::actingAs($this->user);

        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => false,
        ]);

        $response = $this->getJson("/api/v1/announcements/{$announcement->id}");

        $response->assertStatus(404);
    });

    it('returns 404 for announcement not targeted to user', function () {
        Sanctum::actingAs($this->user);

        $otherDept = Department::factory()->create(['company_id' => $this->company->id]);

        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_DEPARTMENT,
            'target_ids' => [$otherDept->id],
            'is_published' => true,
        ]);

        $response = $this->getJson("/api/v1/announcements/{$announcement->id}");

        $response->assertStatus(404);
    });
});

describe('POST /api/v1/announcements/{id}/read', function () {
    it('marks announcement as read', function () {
        Sanctum::actingAs($this->user);

        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $response = $this->postJson("/api/v1/announcements/{$announcement->id}/read");

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Pengumuman ditandai sudah dibaca.',
            ]);

        expect($announcement->isReadBy($this->user->id))->toBeTrue();
    });

    it('handles already read announcement', function () {
        Sanctum::actingAs($this->user);

        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $announcement->markAsRead($this->user->id);

        $response = $this->postJson("/api/v1/announcements/{$announcement->id}/read");

        // Should still succeed (idempotent)
        $response->assertOk();
    });
});

describe('GET /api/v1/announcements/unread-count', function () {
    it('returns unread announcement count', function () {
        Sanctum::actingAs($this->user);

        Announcement::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $response = $this->getJson('/api/v1/announcements/unread-count');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => [
                    'count',
                ],
            ]);

        expect($response->json('data.count'))->toBe(3);
    });

    it('excludes read announcements from count', function () {
        Sanctum::actingAs($this->user);

        $announcement1 = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'target_audience' => Announcement::TARGET_ALL,
            'is_published' => true,
        ]);

        $announcement1->markAsRead($this->user->id);

        $response = $this->getJson('/api/v1/announcements/unread-count');

        $response->assertOk();
        expect($response->json('data.count'))->toBe(1);
    });
});
