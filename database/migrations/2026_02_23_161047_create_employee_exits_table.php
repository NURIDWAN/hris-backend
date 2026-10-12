<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_exits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // Exit details
            $table->enum('exit_type', ['resignation', 'termination', 'retirement', 'contract_end', 'mutual_agreement', 'death']);
            $table->date('request_date');
            $table->date('last_working_day');
            $table->date('effective_date'); // When they officially leave
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // Status workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'completed', 'cancelled'])->default('pending');

            // Approval
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();

            // Exit checklist
            $table->boolean('assets_returned')->default(false);
            $table->boolean('knowledge_transfer_done')->default(false);
            $table->boolean('final_settlement_done')->default(false);
            $table->boolean('exit_interview_done')->default(false);
            $table->text('exit_interview_notes')->nullable();

            // Rehire eligibility
            $table->boolean('is_rehire_eligible')->default(true);
            $table->text('rehire_notes')->nullable();

            // Metadata
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'exit_type']);
            $table->index(['company_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_exits');
    }
};
