<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('overtime_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('weekday_rate_first_hour', 5, 2)->default(1.5);
            $table->decimal('weekday_rate_next_hours', 5, 2)->default(2.0);
            $table->decimal('weekend_rate', 5, 2)->default(2.0);
            $table->decimal('holiday_rate', 5, 2)->default(3.0);
            $table->integer('max_overtime_hours_per_day')->default(4);
            $table->integer('max_overtime_hours_per_week')->default(14);
            $table->integer('min_overtime_minutes')->default(30);
            $table->integer('working_hours_per_month')->default(173);
            $table->boolean('requires_approval')->default(true);
            $table->boolean('auto_calculate_from_attendance')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });

        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('overtime_hours', 5, 2)->nullable();
            $table->enum('overtime_type', ['weekday', 'weekend', 'holiday']);
            $table->decimal('overtime_amount', 15, 2)->nullable();
            $table->text('reason');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'employee_id']);
            $table->index(['company_id', 'date']);
            $table->index(['company_id', 'status']);
            $table->unique(['company_id', 'employee_id', 'date'], 'overtime_unique_per_employee_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('overtime_settings');
    }
};
