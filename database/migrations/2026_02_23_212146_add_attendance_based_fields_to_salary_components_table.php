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
        Schema::table('salary_components', function (Blueprint $table) {
            // Whether this component is based on attendance (e.g., meal allowance, transport)
            $table->boolean('is_attendance_based')->default(false)->after('is_mandatory');

            // How to calculate attendance-based components
            // daily: amount per present day (e.g., Rp 50,000/day for meal)
            // monthly: fixed amount, deducted proportionally for absences
            $table->string('attendance_calculation')->default('daily')->after('is_attendance_based');

            // Daily rate for attendance-based components
            // If attendance_calculation = 'daily', this is the per-day amount
            // If attendance_calculation = 'monthly', this is ignored (uses default_amount)
            $table->decimal('daily_rate', 15, 2)->nullable()->after('attendance_calculation');

            // Whether to deduct for late arrivals
            $table->boolean('deduct_for_late')->default(false)->after('daily_rate');

            // Whether to deduct for early leave
            $table->boolean('deduct_for_early_leave')->default(false)->after('deduct_for_late');

            // Whether to include half days (count as 0.5)
            $table->boolean('include_half_days')->default(true)->after('deduct_for_early_leave');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_components', function (Blueprint $table) {
            $table->dropColumn([
                'is_attendance_based',
                'attendance_calculation',
                'daily_rate',
                'deduct_for_late',
                'deduct_for_early_leave',
                'include_half_days',
            ]);
        });
    }
};
