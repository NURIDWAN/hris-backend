<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thr_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('calculation_method')->default('one_month_salary'); // one_month_salary, prorata
            $table->integer('min_service_months')->default(1); // Minimum months of service to be eligible
            $table->string('prorata_formula')->default('months_worked_per_12'); // months_worked_per_12
            $table->boolean('include_allowances')->default(true); // Include allowances in calculation
            $table->string('religious_holiday')->default('idul_fitri'); // idul_fitri, christmas, etc
            $table->integer('payment_days_before')->default(7); // Days before holiday to pay
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thr_settings');
    }
};
