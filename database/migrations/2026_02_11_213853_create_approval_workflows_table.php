<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('type'); // leave_request, overtime, expense, etc.
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'type']);
        });

        Schema::create('approval_workflow_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_workflow_id')->constrained()->onDelete('cascade');
            $table->integer('step_order');
            $table->string('approver_type'); // role, specific_user, department_head
            $table->string('approver_role')->nullable(); // hr-manager, admin
            $table->foreignId('approver_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('can_skip')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['approval_workflow_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_steps');
        Schema::dropIfExists('approval_workflows');
    }
};
