<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_salary_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_name');
            $table->string('employee_number');
            $table->string('department_name')->nullable();
            $table->string('position_name')->nullable();

            // Attendance summary
            $table->integer('working_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0);
            $table->integer('leave_days')->default(0);
            $table->integer('overtime_hours')->default(0);

            // Salary breakdown
            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_earnings', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('gross_salary', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);

            // Payment info
            $table->enum('payment_method', ['transfer', 'cash'])->default('transfer');
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            $table->enum('status', ['pending', 'calculated', 'approved', 'paid'])->default('pending');
            $table->datetime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['payroll_id', 'employee_id']);
            $table->index(['payroll_id', 'status']);
        });

        // Detail komponen gaji per payroll item
        Schema::create('payroll_item_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()->constrained()->nullOnDelete();
            $table->string('component_name');
            $table->string('component_code')->nullable();
            $table->enum('type', ['earning', 'deduction']);
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->boolean('is_taxable')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payroll_item_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_details');
        Schema::dropIfExists('payroll_items');
    }
};
