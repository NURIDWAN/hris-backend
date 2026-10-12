<?php

use App\Models\Company;
use App\Models\EmailLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

describe('Email Logs Index', function () {
    it('can access email logs page as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/email-logs');

        $response->assertOk()
            ->assertViewIs('superadmin.system.email-logs.index')
            ->assertViewHas('emailLogs')
            ->assertViewHas('stats');
    });

    it('displays email logs in the list', function () {
        EmailLog::factory()->count(3)->create();

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/email-logs');

        $response->assertOk();
        expect($response->viewData('stats')['total'])->toBe(3);
    });

    it('can filter by status', function () {
        EmailLog::factory()->sent()->count(2)->create();
        EmailLog::factory()->failed()->create();

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/email-logs?status=sent');

        $response->assertOk();
        $emailLogs = $response->viewData('emailLogs');
        expect($emailLogs->count())->toBe(2);
    });

    it('can filter by search term', function () {
        EmailLog::factory()->create(['subject' => 'Welcome to GajiPro']);
        EmailLog::factory()->create(['subject' => 'Password Reset']);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/email-logs?search=Welcome');

        $response->assertOk();
        $emailLogs = $response->viewData('emailLogs');
        expect($emailLogs->count())->toBe(1);
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/email-logs');

        $response->assertRedirect('/superadmin/login');
    });
});

describe('Email Log Show', function () {
    it('can view email log detail', function () {
        $emailLog = EmailLog::factory()->create();

        $response = $this->actingAs($this->superadmin)
            ->get("/superadmin/system/email-logs/{$emailLog->id}");

        $response->assertOk()
            ->assertViewIs('superadmin.system.email-logs.show')
            ->assertViewHas('emailLog');
    });
});

describe('Notification Log', function () {
    it('can access notifications page as superadmin', function () {
        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/notifications');

        $response->assertOk()
            ->assertViewIs('superadmin.system.notifications.index')
            ->assertViewHas('notifications')
            ->assertViewHas('stats');
    });

    it('displays notifications from all companies', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        $user1 = User::factory()->create(['company_id' => $company1->id]);
        $user2 = User::factory()->create(['company_id' => $company2->id]);

        Notification::factory()->create(['company_id' => $company1->id, 'user_id' => $user1->id]);
        Notification::factory()->create(['company_id' => $company2->id, 'user_id' => $user2->id]);

        $response = $this->actingAs($this->superadmin)
            ->get('/superadmin/system/notifications');

        $response->assertOk();
        expect($response->viewData('stats')['total'])->toBe(2);
    });

    it('denies access to regular users', function () {
        $response = $this->actingAs($this->regularUser)
            ->get('/superadmin/system/notifications');

        $response->assertRedirect('/superadmin/login');
    });
});
