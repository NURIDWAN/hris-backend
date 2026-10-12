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
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Payroll cycle configuration
            // cycle_type: monthly, bi-weekly, custom
            $table->string('cycle_type')->default('monthly');

            // For monthly cycles: the cutoff day (1-28)
            // Example: cutoff_day = 25 means period is 25th to 24th
            $table->unsignedTinyInteger('cutoff_day')->default(1);

            // Working days configuration
            $table->unsignedTinyInteger('default_working_days')->default(22);

            // Whether to auto-calculate attendance deductions
            $table->boolean('auto_deduct_absent')->default(true);
            $table->boolean('auto_deduct_late')->default(false);

            // Late penalty configuration
            $table->unsignedInteger('late_penalty_threshold')->default(3); // times per month
            $table->decimal('late_penalty_amount', 15, 2)->default(0);
            $table->string('late_penalty_type')->default('fixed'); // fixed, percentage, daily_rate

            // Absent deduction configuration
            $table->string('absent_deduction_type')->default('daily_rate'); // daily_rate, fixed, percentage
            $table->decimal('absent_deduction_amount', 15, 2)->default(0);

            // Prorate settings
            $table->boolean('prorate_new_employees')->default(true);
            $table->boolean('prorate_resigned_employees')->default(true);

            // Overtime settings
            $table->boolean('include_overtime_in_payroll')->default(true);
            $table->boolean('require_overtime_approval')->default(true);

            // Payment date configuration
            $table->unsignedTinyInteger('payment_day')->default(1); // day of month for payment

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_settings');
    }
};
