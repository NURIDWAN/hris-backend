<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->time('scheduled_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->datetime('clock_in')->nullable();
            $table->datetime('clock_out')->nullable();
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();
            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();
            $table->string('clock_in_photo')->nullable();
            $table->string('clock_out_photo')->nullable();
            $table->text('clock_in_notes')->nullable();
            $table->text('clock_out_notes')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('working_minutes')->default(0);
            $table->enum('status', ['present', 'absent', 'late', 'half_day', 'leave', 'holiday', 'weekend'])->default('present');
            $table->enum('clock_in_status', ['on_time', 'late', 'very_late'])->nullable();
            $table->enum('clock_out_status', ['on_time', 'early', 'overtime'])->nullable();
            $table->boolean('is_manual_entry')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('approved_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['employee_id', 'date']);
            $table->index(['company_id', 'date']);
            $table->index(['employee_id', 'date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
