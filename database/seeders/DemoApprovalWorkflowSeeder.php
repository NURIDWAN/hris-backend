<?php

namespace Database\Seeders;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeder untuk konfigurasi Approval Workflows PT Demo GajiPro
 *
 * Workflow yang dibuat:
 * ====================
 *
 * 1. Pengajuan Cuti (leave_request)
 *    - Step 1: Kepala Departemen (dapat di-skip jika tidak ada)
 *    - Step 2: HR Manager
 *
 * 2. Pengajuan Lembur (overtime)
 *    - Step 1: Kepala Departemen
 *    - Step 2: HR Manager
 *
 * 3. Pengajuan Pengeluaran (expense)
 *    - Step 1: Kepala Departemen
 *    - Step 2: Finance Manager (role: payroll-manager)
 *    - Step 3: Admin (untuk pengeluaran besar)
 *
 * 4. Pengajuan Reimbursement (reimbursement)
 *    - Step 1: Kepala Departemen
 *    - Step 2: Finance/Payroll Manager
 */
class DemoApprovalWorkflowSeeder extends Seeder
{
    private Company $company;

    private array $workflows = [];

    public function run(): void
    {
        $this->company = Company::where('name', 'like', '%Demo%')->first();

        if (! $this->company) {
            $this->command->error('Company Demo tidak ditemukan!');

            return;
        }

        $this->command->info("Membuat Approval Workflows untuk: {$this->company->name}");

        // Hapus data lama
        $this->cleanExistingData();

        // Buat workflows
        $this->createLeaveRequestWorkflow();
        $this->createOvertimeWorkflow();
        $this->createExpenseWorkflow();
        $this->createReimbursementWorkflow();

        $this->printSummary();
    }

    private function cleanExistingData(): void
    {
        $this->command->info('Menghapus data approval workflow lama...');

        // Steps akan terhapus otomatis karena cascade
        ApprovalWorkflow::where('company_id', $this->company->id)->delete();
    }

    private function createLeaveRequestWorkflow(): void
    {
        $this->command->info('Membuat workflow: Pengajuan Cuti...');

        $workflow = ApprovalWorkflow::create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_LEAVE_REQUEST,
            'name' => 'Persetujuan Pengajuan Cuti',
            'description' => 'Workflow persetujuan untuk semua jenis pengajuan cuti karyawan. Dimulai dari atasan langsung (Kepala Departemen), kemudian ke HR Manager untuk approval final.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Step 1: Kepala Departemen
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 1,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
            'approver_role' => null,
            'approver_user_id' => null,
            'can_skip' => true, // Dapat di-skip jika tidak ada kepala departemen
            'is_active' => true,
        ]);

        // Step 2: HR Manager
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 2,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
            'approver_user_id' => null,
            'can_skip' => false,
            'is_active' => true,
        ]);

        $this->workflows['leave_request'] = $workflow;
    }

    private function createOvertimeWorkflow(): void
    {
        $this->command->info('Membuat workflow: Pengajuan Lembur...');

        $workflow = ApprovalWorkflow::create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_OVERTIME,
            'name' => 'Persetujuan Pengajuan Lembur',
            'description' => 'Workflow persetujuan untuk pengajuan lembur karyawan. Memerlukan persetujuan dari Kepala Departemen dan HR Manager untuk memastikan kepatuhan terhadap kebijakan perusahaan dan regulasi ketenagakerjaan.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        // Step 1: Kepala Departemen
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 1,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
            'approver_role' => null,
            'approver_user_id' => null,
            'can_skip' => false,
            'is_active' => true,
        ]);

        // Step 2: HR Manager
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 2,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'hr-manager',
            'approver_user_id' => null,
            'can_skip' => false,
            'is_active' => true,
        ]);

        $this->workflows['overtime'] = $workflow;
    }

    private function createExpenseWorkflow(): void
    {
        $this->command->info('Membuat workflow: Pengajuan Pengeluaran...');

        // Get Admin user for final approval
        $adminUser = User::where('company_id', $this->company->id)
            ->where('email', 'admin@demo.gajipro.com')
            ->first();

        $workflow = ApprovalWorkflow::create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_EXPENSE,
            'name' => 'Persetujuan Pengajuan Pengeluaran',
            'description' => 'Workflow persetujuan untuk pengajuan pengeluaran operasional. Melalui 3 tahap approval: Kepala Departemen, Finance/Payroll Manager, dan Admin untuk pengeluaran signifikan.',
            'is_active' => true,
            'sort_order' => 3,
        ]);

        // Step 1: Kepala Departemen
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 1,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
            'approver_role' => null,
            'approver_user_id' => null,
            'can_skip' => true,
            'is_active' => true,
        ]);

        // Step 2: Payroll/Finance Manager
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 2,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'payroll-manager',
            'approver_user_id' => null,
            'can_skip' => false,
            'is_active' => true,
        ]);

        // Step 3: Admin (untuk pengeluaran besar)
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 3,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_SPECIFIC_USER,
            'approver_role' => null,
            'approver_user_id' => $adminUser?->id,
            'can_skip' => true, // Dapat di-skip untuk pengeluaran kecil
            'is_active' => true,
        ]);

        $this->workflows['expense'] = $workflow;
    }

    private function createReimbursementWorkflow(): void
    {
        $this->command->info('Membuat workflow: Pengajuan Reimbursement...');

        $workflow = ApprovalWorkflow::create([
            'company_id' => $this->company->id,
            'type' => ApprovalWorkflow::TYPE_REIMBURSEMENT,
            'name' => 'Persetujuan Pengajuan Reimbursement',
            'description' => 'Workflow persetujuan untuk pengajuan reimbursement karyawan. Dimulai dari Kepala Departemen untuk verifikasi kebutuhan bisnis, kemudian ke Finance/Payroll Manager untuk verifikasi dan proses pembayaran.',
            'is_active' => true,
            'sort_order' => 4,
        ]);

        // Step 1: Kepala Departemen
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 1,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_DEPARTMENT_HEAD,
            'approver_role' => null,
            'approver_user_id' => null,
            'can_skip' => true,
            'is_active' => true,
        ]);

        // Step 2: Payroll/Finance Manager
        ApprovalWorkflowStep::create([
            'approval_workflow_id' => $workflow->id,
            'step_order' => 2,
            'approver_type' => ApprovalWorkflowStep::APPROVER_TYPE_ROLE,
            'approver_role' => 'payroll-manager',
            'approver_user_id' => null,
            'can_skip' => false,
            'is_active' => true,
        ]);

        $this->workflows['reimbursement'] = $workflow;
    }

    private function printSummary(): void
    {
        $this->command->newLine();
        $this->command->info('=== APPROVAL WORKFLOWS PT DEMO GAJIPRO ===');
        $this->command->newLine();

        $workflows = ApprovalWorkflow::where('company_id', $this->company->id)
            ->with('steps')
            ->orderBy('sort_order')
            ->get();

        foreach ($workflows as $workflow) {
            $statusBadge = $workflow->is_active ? '[AKTIF]' : '[NONAKTIF]';
            $this->command->info("{$workflow->sort_order}. {$workflow->name} {$statusBadge}");
            $this->command->line("   Tipe: {$workflow->type_label}");
            $this->command->line("   Deskripsi: {$workflow->description}");
            $this->command->line('   Steps:');

            foreach ($workflow->steps as $step) {
                $skipBadge = $step->can_skip ? '(dapat di-skip)' : '(wajib)';
                $activeBadge = $step->is_active ? '' : ' [NONAKTIF]';
                $this->command->line("     Step {$step->step_order}: {$step->approver_type_label} - {$step->approver_description} {$skipBadge}{$activeBadge}");
            }

            $this->command->newLine();
        }

        // Summary statistics
        $totalWorkflows = $workflows->count();
        $totalSteps = $workflows->sum(fn ($w) => $w->steps->count());
        $activeWorkflows = $workflows->where('is_active', true)->count();

        $this->command->info('=== RINGKASAN ===');
        $this->command->line("Total Workflows: {$totalWorkflows}");
        $this->command->line("Total Steps: {$totalSteps}");
        $this->command->line("Workflows Aktif: {$activeWorkflows}");
        $this->command->newLine();

        // Approval Flow explanation
        $this->command->info('=== ALUR PERSETUJUAN ===');
        $this->command->newLine();

        $this->command->line('Pengajuan Cuti:');
        $this->command->line('  Karyawan -> Kepala Dept (opsional) -> HR Manager -> Selesai');
        $this->command->newLine();

        $this->command->line('Pengajuan Lembur:');
        $this->command->line('  Karyawan -> Kepala Dept -> HR Manager -> Selesai');
        $this->command->newLine();

        $this->command->line('Pengajuan Pengeluaran:');
        $this->command->line('  Karyawan -> Kepala Dept (opsional) -> Finance -> Admin (opsional) -> Selesai');
        $this->command->newLine();

        $this->command->line('Pengajuan Reimbursement:');
        $this->command->line('  Karyawan -> Kepala Dept (opsional) -> Finance -> Selesai');
        $this->command->newLine();

        $this->command->info('Approval Workflows berhasil dibuat!');
    }
}
