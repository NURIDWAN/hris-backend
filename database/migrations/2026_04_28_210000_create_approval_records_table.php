<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_records', function (Blueprint $table) {
            $table->id();
            $table->morphs('approvable');
            $table->foreignId('approval_workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approval_workflow_step_id')->constrained()->cascadeOnDelete();
            $table->integer('step_order');
            $table->enum('status', ['pending', 'approved', 'rejected', 'skipped'])->default('pending');
            $table->foreignId('acted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamp('acted_at')->nullable();
            $table->timestamps();

            $table->index(['approvable_type', 'approvable_id', 'status'], 'approval_records_approvable_status_index');
            $table->index(['approval_workflow_step_id', 'status']);
        });

        Schema::table('leave_requests', function (Blueprint $table) {
            $table->foreignId('approval_workflow_id')->nullable()->after('status')->constrained()->nullOnDelete();
            $table->integer('current_approval_step')->nullable()->after('approval_workflow_id');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approval_workflow_id');
            $table->dropColumn('current_approval_step');
        });

        Schema::dropIfExists('approval_records');
    }
};
