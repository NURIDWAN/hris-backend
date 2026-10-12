<?php

use App\Models\Announcement;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);

    $this->company = Company::factory()->create();
    $this->admin = User::factory()->create(['company_id' => $this->company->id]);
    $this->admin->assignRole('admin');

    $this->department = Department::factory()->create(['company_id' => $this->company->id]);
    $this->position = Position::factory()->create([
        'company_id' => $this->company->id,
        'department_id' => $this->department->id,
    ]);

    $employeeUser = User::factory()->create(['company_id' => $this->company->id]);
    $employeeUser->assignRole('employee');
    $this->employee = Employee::factory()->create([
        'company_id' => $this->company->id,
        'user_id' => $employeeUser->id,
        'department_id' => $this->department->id,
        'position_id' => $this->position->id,
    ]);

    app()->instance('tenant', $this->company);
});

describe('Admin - Announcement Management', function () {
    it('displays announcement index page', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('announcements.index'));

        $response->assertOk();
        $response->assertViewIs('announcements.index');
    });

    it('lists all announcements for company', function () {
        Announcement::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // Other company's announcement
        Announcement::factory()->create();

        $this->actingAs($this->admin);

        $response = $this->get(route('announcements.index'));

        $response->assertOk();
        $response->assertViewHas('announcements', fn ($announcements) => $announcements->count() === 3);
    });

    it('displays create announcement form', function () {
        $this->actingAs($this->admin);

        $response = $this->get(route('announcements.create'));

        $response->assertOk();
        $response->assertViewIs('announcements.create');
    });

    it('creates new announcement', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('announcements.store'), [
            'title' => 'Pengumuman Penting',
            'content' => 'Ini adalah isi pengumuman yang sangat penting.',
            'priority' => 'high',
            'target_audience' => 'all',
            'is_pinned' => true,
        ]);

        $response->assertRedirect(route('announcements.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('announcements', [
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'title' => 'Pengumuman Penting',
            'priority' => 'high',
            'is_pinned' => true,
        ]);
    });

    it('creates announcement for specific department', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('announcements.store'), [
            'title' => 'Info Departemen IT',
            'content' => 'Khusus untuk departemen IT.',
            'priority' => 'normal',
            'target_audience' => 'department',
            'target_ids' => [$this->department->id],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'title' => 'Info Departemen IT',
            'target_audience' => 'department',
        ]);

        $announcement = Announcement::where('title', 'Info Departemen IT')->first();
        expect($announcement->target_ids)->toContain($this->department->id);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('announcements.store'), []);

        $response->assertSessionHasErrors(['title', 'content', 'priority', 'target_audience']);
    });

    it('displays edit form', function () {
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->get(route('announcements.edit', $announcement));

        $response->assertOk();
        $response->assertViewIs('announcements.edit');
    });

    it('updates announcement', function () {
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'title' => 'Original Title',
        ]);

        $this->actingAs($this->admin);

        $response = $this->put(route('announcements.update', $announcement), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'priority' => 'urgent',
            'target_audience' => 'all',
        ]);

        $response->assertRedirect(route('announcements.index'));

        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => 'Updated Title',
            'priority' => 'urgent',
        ]);
    });

    it('deletes announcement', function () {
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->delete(route('announcements.destroy', $announcement));

        $response->assertRedirect();
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    });

    it('cannot access other company announcement', function () {
        $otherAnnouncement = Announcement::factory()->create();

        $this->actingAs($this->admin);

        $response = $this->get(route('announcements.edit', $otherAnnouncement));

        $response->assertNotFound();
    });
});

describe('Admin - Publish/Unpublish', function () {
    it('publishes announcement', function () {
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'is_published' => false,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('announcements.publish', $announcement));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $announcement->refresh();
        expect($announcement->is_published)->toBeTrue();
        expect($announcement->published_at)->not->toBeNull();
    });

    it('unpublishes announcement', function () {
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);

        $response = $this->post(route('announcements.unpublish', $announcement));

        $response->assertRedirect();

        $announcement->refresh();
        expect($announcement->is_published)->toBeFalse();
    });
});

describe('Employee Portal - Announcements', function () {
    it('displays announcements for employee', function () {
        // Create published announcements
        Announcement::factory()->published()->count(2)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // Unpublished - should not appear
        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'is_published' => false,
        ]);

        $this->actingAs($this->employee->user);

        $response = $this->get(route('portal.announcements.index'));

        $response->assertOk();
        $response->assertViewIs('portal.announcements.index');
        $response->assertViewHas('announcements', fn ($a) => $a->count() === 2);
    });

    it('shows only relevant announcements for department', function () {
        // For all
        $forAll = Announcement::factory()->published()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // For this employee's department
        $forDept = Announcement::factory()->published()->forDepartment($this->department->id)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // For other department - should not appear
        $otherDept = Department::factory()->create(['company_id' => $this->company->id]);
        $forOther = Announcement::factory()->published()->forDepartment($otherDept->id)->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // Refresh employee to ensure relations are loaded
        $this->employee->refresh();
        $this->employee->user->refresh();

        $this->actingAs($this->employee->user);

        $response = $this->get(route('portal.announcements.index'));

        $announcements = $response->viewData('announcements');
        // Expect 2: forAll (target_audience=all) + forDept (target_audience=department with this dept id)
        expect($announcements->count())->toBe(2);
    });

    it('can view single announcement', function () {
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->employee->user);

        $response = $this->get(route('portal.announcements.show', $announcement));

        $response->assertOk();
        $response->assertViewIs('portal.announcements.show');
    });

    it('marks announcement as read when viewed', function () {
        $announcement = Announcement::factory()->published()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->employee->user);

        $this->get(route('portal.announcements.show', $announcement));

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $announcement->id,
            'user_id' => $this->employee->user->id,
        ]);
    });

    it('cannot view expired announcement', function () {
        $announcement = Announcement::factory()->expired()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->employee->user);

        $response = $this->get(route('portal.announcements.show', $announcement));

        $response->assertNotFound();
    });

    it('cannot view unpublished announcement', function () {
        $announcement = Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'is_published' => false,
        ]);

        $this->actingAs($this->employee->user);

        $response = $this->get(route('portal.announcements.show', $announcement));

        $response->assertNotFound();
    });
});

describe('Announcement Model', function () {
    it('returns correct priority label', function () {
        $announcement = Announcement::factory()->urgent()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        expect($announcement->priority_label)->toBe('Mendesak');
    });

    it('detects expired announcement', function () {
        $expired = Announcement::factory()->expired()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $active = Announcement::factory()->published()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
            'expires_at' => now()->addWeek(),
        ]);

        expect($expired->is_expired)->toBeTrue();
        expect($active->is_expired)->toBeFalse();
    });

    it('scopes active announcements correctly', function () {
        // Published, not expired
        Announcement::factory()->published()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // Published, expired
        Announcement::factory()->expired()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        // Not published
        Announcement::factory()->create([
            'company_id' => $this->company->id,
            'created_by' => $this->admin->id,
        ]);

        $active = Announcement::where('company_id', $this->company->id)->active()->get();

        expect($active)->toHaveCount(1);
    });
});
