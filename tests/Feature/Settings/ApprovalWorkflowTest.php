<?php

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->company = Company::factory()->create();
    createStandardRoles($this->company->id);

    $this->admin = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Admin User',
    ]);
    $this->admin->assignRole('admin');

    $this->hrManager = User::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'HR Manager',
    ]);
    $this->hrManager->assignRole('hr-manager');
});

describe('Approval Workflow Index', function () {
    it('displays approval workflow list page for admin', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Alur Persetujuan Cuti',
        ]);

        $response = $this->get('/settings/approval-workflows');

        $response->assertStatus(200);
        $response->assertViewIs('settings.approval-workflows.index');
        $response->assertSee('Alur Persetujuan Cuti');
    });

    it('only shows workflows from same company', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'My Company Workflow',
        ]);

        $otherCompany = Company::factory()->create();
        $otherWorkflow = ApprovalWorkflow::factory()->create([
            'company_id' => $otherCompany->id,
            'name' => 'Other Company Workflow',
        ]);

        $response = $this->get('/settings/approval-workflows');

        $response->assertStatus(200);
        $response->assertSee('My Company Workflow');
        $response->assertDontSee('Other Company Workflow');
    });

    it('redirects unauthenticated user to login', function () {
        $response = $this->get('/settings/approval-workflows');

        $response->assertRedirect('/login');
    });

    it('shows all workflow types with their status', function () {
        $this->actingAs($this->admin);

        // Create one workflow
        ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'is_active' => true,
        ]);

        $response = $this->get('/settings/approval-workflows');

        $response->assertStatus(200);
        // Should show leave_request as configured
        $response->assertSee('Pengajuan Cuti');
    });
});

describe('Approval Workflow Create', function () {
    it('displays create workflow form', function () {
        $this->actingAs($this->admin);

        $response = $this->get('/settings/approval-workflows/create');

        $response->assertStatus(200);
        $response->assertViewIs('settings.approval-workflows.create');
    });

    it('can create new workflow', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/approval-workflows', [
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Alur Cuti Baru',
            'description' => 'Deskripsi alur persetujuan cuti',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('settings.approval-workflows.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('approval_workflows', [
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Alur Cuti Baru',
        ]);
    });

    it('validates required fields', function () {
        $this->actingAs($this->admin);

        $response = $this->post('/settings/approval-workflows', [
            'type' => '',
            'name' => '',
        ]);

        $response->assertSessionHasErrors(['type', 'name']);
    });

    it('validates unique type per company', function () {
        $this->actingAs($this->admin);

        ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
        ]);

        $response = $this->post('/settings/approval-workflows', [
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Another Leave Workflow',
        ]);

        $response->assertSessionHasErrors(['type']);
    });
});

describe('Approval Workflow Edit', function () {
    it('displays edit workflow form', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'name' => 'Test Workflow',
        ]);

        $response = $this->get("/settings/approval-workflows/{$workflow->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('settings.approval-workflows.edit');
        $response->assertSee('Test Workflow');
    });

    it('cannot edit workflow from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->get("/settings/approval-workflows/{$workflow->id}/edit");

        $response->assertStatus(404);
    });

    it('can update workflow', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Old Name',
        ]);

        $response = $this->put("/settings/approval-workflows/{$workflow->id}", [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'is_active' => false,
        ]);

        $response->assertRedirect(route('settings.approval-workflows.index'));
        $response->assertSessionHas('success');

        $workflow->refresh();
        expect($workflow->name)->toBe('Updated Name');
        expect($workflow->description)->toBe('Updated description');
        expect($workflow->is_active)->toBeFalse();
    });
});

describe('Approval Workflow Delete', function () {
    it('can delete workflow', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->delete("/settings/approval-workflows/{$workflow->id}");

        $response->assertRedirect(route('settings.approval-workflows.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('approval_workflows', ['id' => $workflow->id]);
    });

    it('cannot delete workflow from another company', function () {
        $this->actingAs($this->admin);

        $otherCompany = Company::factory()->create();
        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $response = $this->delete("/settings/approval-workflows/{$workflow->id}");

        $response->assertStatus(404);
    });
});

describe('Approval Workflow Steps', function () {
    it('can add step to workflow', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post("/settings/approval-workflows/{$workflow->id}/steps", [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
            'can_skip' => false,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('approval_workflow_steps', [
            'approval_workflow_id' => $workflow->id,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
            'step_order' => 1,
        ]);
    });

    it('auto increments step order', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        // Add first step
        $this->post("/settings/approval-workflows/{$workflow->id}/steps", [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
        ]);

        // Add second step
        $this->post("/settings/approval-workflows/{$workflow->id}/steps", [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'admin',
        ]);

        $steps = $workflow->steps()->get();
        expect($steps)->toHaveCount(2);
        expect($steps[0]->step_order)->toBe(1);
        expect($steps[1]->step_order)->toBe(2);
    });

    it('can update step', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $step = ApprovalWorkflowStep::factory()->create([
            'approval_workflow_id' => $workflow->id,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
        ]);

        $response = $this->put("/settings/approval-workflows/{$workflow->id}/steps/{$step->id}", [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'admin',
            'can_skip' => true,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $step->refresh();
        expect($step->approver_role)->toBe('admin');
        expect($step->can_skip)->toBeTrue();
    });

    it('can delete step', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $step = ApprovalWorkflowStep::factory()->create([
            'approval_workflow_id' => $workflow->id,
        ]);

        $response = $this->delete("/settings/approval-workflows/{$workflow->id}/steps/{$step->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('approval_workflow_steps', ['id' => $step->id]);
    });

    it('can add specific user as approver', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post("/settings/approval-workflows/{$workflow->id}/steps", [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER,
            'approver_user_id' => $this->hrManager->id,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('approval_workflow_steps', [
            'approval_workflow_id' => $workflow->id,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER,
            'approver_user_id' => $this->hrManager->id,
        ]);
    });

    it('can add department head as approver', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $response = $this->post("/settings/approval-workflows/{$workflow->id}/steps", [
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('approval_workflow_steps', [
            'approval_workflow_id' => $workflow->id,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
        ]);
    });
});

describe('Approval Workflow Toggle Status', function () {
    it('can toggle workflow active status', function () {
        $this->actingAs($this->admin);

        $workflow = ApprovalWorkflow::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $response = $this->patch("/settings/approval-workflows/{$workflow->id}/toggle-status");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $workflow->refresh();
        expect($workflow->is_active)->toBeFalse();
    });
});
