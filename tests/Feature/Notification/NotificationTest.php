<?php

use App\Models\Company;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->user = User::factory()->create([
        'company_id' => $this->company->id,
    ]);
    $this->user->assignRole('admin');
});

describe('Notification Index', function () {
    it('displays notification page', function () {
        $this->actingAs($this->user);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $response->assertViewIs('notifications.index');
        $response->assertSee('Notifikasi');
    });

    it('shows user notifications', function () {
        $this->actingAs($this->user);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Pengajuan cuti baru',
            'message' => 'John Doe mengajukan cuti',
        ]);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $response->assertSee('Pengajuan cuti baru');
        $response->assertSee('John Doe mengajukan cuti');
    });

    it('only shows notifications for current user', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $myNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'My Notification',
        ]);

        $otherNotification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
            'title' => 'Other Notification',
        ]);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $response->assertSee('My Notification');
        $response->assertDontSee('Other Notification');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/notifications');

        $response->assertRedirect('/login');
    });

    it('shows notifications ordered by newest first', function () {
        $this->actingAs($this->user);

        $old = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'Old Notification',
            'created_at' => now()->subDay(),
        ]);

        $new = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'title' => 'New Notification',
            'created_at' => now(),
        ]);

        $response = $this->get('/notifications');

        $response->assertStatus(200);
        $response->assertSeeInOrder(['New Notification', 'Old Notification']);
    });
});

describe('Mark Notification as Read', function () {
    it('can mark single notification as read', function () {
        $this->actingAs($this->user);

        $notification = Notification::factory()->unread()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->patch("/notifications/{$notification->id}/read");

        $response->assertRedirect();

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();
    });

    it('can mark all notifications as read', function () {
        $this->actingAs($this->user);

        $notifications = Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->post('/notifications/mark-all-read');

        $response->assertRedirect();

        foreach ($notifications as $notification) {
            $notification->refresh();
            expect($notification->read_at)->not->toBeNull();
        }
    });

    it('cannot mark notification from other user as read', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->patch("/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    });
});

describe('Delete Notification', function () {
    it('can delete notification', function () {
        $this->actingAs($this->user);

        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete("/notifications/{$notification->id}");

        $response->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    });

    it('cannot delete notification from other user', function () {
        $this->actingAs($this->user);

        $otherUser = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $notification = Notification::factory()->create([
            'user_id' => $otherUser->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete("/notifications/{$notification->id}");

        $response->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    });
});

describe('Unread Count API', function () {
    it('returns unread notification count', function () {
        $this->actingAs($this->user);

        Notification::factory()->count(3)->unread()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        Notification::factory()->count(2)->read()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJson(['count' => 3]);
    });
});

describe('Recent Notifications API', function () {
    it('returns recent notifications for dropdown', function () {
        $this->actingAs($this->user);

        Notification::factory()->count(10)->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        $response = $this->getJson('/notifications/recent');

        $response->assertStatus(200);
        $response->assertJsonCount(5, 'notifications');
        $response->assertJsonStructure([
            'notifications' => [
                '*' => ['id', 'title', 'message', 'type', 'icon', 'read_at', 'created_at', 'time_ago'],
            ],
            'unread_count',
        ]);
    });
});

describe('Notification Model', function () {
    it('correctly identifies unread notification', function () {
        $notification = Notification::factory()->unread()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        expect($notification->isUnread())->toBeTrue();
        expect($notification->isRead())->toBeFalse();
    });

    it('correctly identifies read notification', function () {
        $notification = Notification::factory()->read()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
        ]);

        expect($notification->isRead())->toBeTrue();
        expect($notification->isUnread())->toBeFalse();
    });

    it('has correct icon based on type', function () {
        $leaveNotif = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'type' => 'leave_request',
        ]);

        $payrollNotif = Notification::factory()->create([
            'user_id' => $this->user->id,
            'company_id' => $this->company->id,
            'type' => 'payroll',
        ]);

        expect($leaveNotif->icon)->toBe('calendar');
        expect($payrollNotif->icon)->toBe('banknotes');
    });
});
