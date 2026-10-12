<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thr_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->string('religious_holiday'); // idul_fitri, christmas, etc
            $table->decimal('base_salary', 15, 2);
            $table->decimal('allowances', 15, 2)->default(0);
            $table->decimal('amount', 15, 2); // Final THR amount
            $table->integer('service_months'); // Months of service at calculation time
            $table->string('calculation_method'); // one_month_salary, prorata
            $table->string('status')->default('pending'); // pending, paid, cancelled
            $table->date('payment_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'year', 'religious_holiday'], 'thr_unique_per_employee_year');
            $table->index(['company_id', 'year']);
            $table->index(['employee_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thr_payments');
    }
};
