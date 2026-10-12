<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreApprovalWorkflowRequest;
use App\Http\Requests\Settings\StoreApprovalWorkflowStepRequest;
use App\Http\Requests\Settings\UpdateApprovalWorkflowRequest;
use App\Http\Requests\Settings\UpdateApprovalWorkflowStepRequest;
use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ApprovalWorkflowController extends Controller
{
    public function index(): View
    {
        $tenant = app('tenant');

        $workflows = ApprovalWorkflow::where('company_id', $tenant->id)
            ->with('steps')
            ->orderBy('sort_order')
            ->get();

        $workflowTypes = ApprovalWorkflow::TYPES;

        return view('settings.approval-workflows.index', compact('workflows', 'workflowTypes'));
    }

    public function create(): View
    {
        $tenant = app('tenant');

        $existingTypes = ApprovalWorkflow::where('company_id', $tenant->id)
            ->pluck('type')
            ->toArray();

        $availableTypes = array_diff_key(
            ApprovalWorkflow::TYPES,
            array_flip($existingTypes)
        );

        return view('settings.approval-workflows.create', compact('availableTypes'));
    }

    public function store(StoreApprovalWorkflowRequest $request): RedirectResponse
    {
        $tenant = app('tenant');
        $validated = $request->validated();

        ApprovalWorkflow::create([
            'company_id' => $tenant->id,
            'type' => $validated['type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('settings.approval-workflows.index')
            ->with('success', 'Alur persetujuan berhasil dibuat.');
    }

    public function edit(ApprovalWorkflow $approvalWorkflow): View
    {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $approvalWorkflow->load('steps.approverUser');
        // Get company-scoped roles
        $roles = Role::where('company_id', $tenant->id)->orderBy('name')->get();
        $users = User::where('company_id', $tenant->id)->orderBy('name')->get();

        return view('settings.approval-workflows.edit', compact('approvalWorkflow', 'roles', 'users'));
    }

    public function update(UpdateApprovalWorkflowRequest $request, ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validated();

        $approvalWorkflow->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('settings.approval-workflows.index')
            ->with('success', 'Alur persetujuan berhasil diperbarui.');
    }

    public function destroy(ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $approvalWorkflow->delete();

        return redirect()->route('settings.approval-workflows.index')
            ->with('success', 'Alur persetujuan berhasil dihapus.');
    }

    public function toggleStatus(ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $approvalWorkflow->update(['is_active' => ! $approvalWorkflow->is_active]);

        $status = $approvalWorkflow->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return redirect()->route('settings.approval-workflows.index')
            ->with('success', "Alur persetujuan berhasil {$status}.");
    }

    public function storeStep(StoreApprovalWorkflowStepRequest $request, ApprovalWorkflow $approvalWorkflow): RedirectResponse
    {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validated();

        $maxOrder = $approvalWorkflow->steps()->max('step_order') ?? 0;

        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $approvalWorkflow->id,
            'step_order' => $maxOrder + 1,
            'approver_type' => $validated['approver_type'],
            'approver_role' => $validated['approver_role'] ?? null,
            'approver_user_id' => $validated['approver_user_id'] ?? null,
            'can_skip' => $validated['can_skip'] ?? false,
            'is_active' => true,
        ]);

        return redirect()->route('settings.approval-workflows.edit', $approvalWorkflow)
            ->with('success', 'Langkah persetujuan berhasil ditambahkan.');
    }

    public function updateStep(
        UpdateApprovalWorkflowStepRequest $request,
        ApprovalWorkflow $approvalWorkflow,
        ApprovalWorkflowStep $step
    ): RedirectResponse {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $validated = $request->validated();

        $step->update([
            'approver_type' => $validated['approver_type'],
            'approver_role' => $validated['approver_role'] ?? null,
            'approver_user_id' => $validated['approver_user_id'] ?? null,
            'can_skip' => $validated['can_skip'] ?? false,
        ]);

        return redirect()->route('settings.approval-workflows.edit', $approvalWorkflow)
            ->with('success', 'Langkah persetujuan berhasil diperbarui.');
    }

    public function destroyStep(ApprovalWorkflow $approvalWorkflow, ApprovalWorkflowStep $step): RedirectResponse
    {
        $tenant = app('tenant');

        if ($approvalWorkflow->company_id !== $tenant->id) {
            abort(404);
        }

        $step->delete();

        // Reorder remaining steps
        $approvalWorkflow->steps()
            ->orderBy('step_order')
            ->get()
            ->each(function ($s, $index) {
                $s->update(['step_order' => $index + 1]);
            });

        return redirect()->route('settings.approval-workflows.edit', $approvalWorkflow)
            ->with('success', 'Langkah persetujuan berhasil dihapus.');
    }
}
