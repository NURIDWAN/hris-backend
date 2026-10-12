<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Performance indexes untuk mempercepat query dashboard dan listing pages.
     */
    public function up(): void
    {
        // Employees table indexes
        Schema::table('employees', function (Blueprint $table) {
            // Untuk filter karyawan aktif per company
            $table->index(['company_id', 'is_active'], 'idx_employees_company_active');

            // Untuk filter karyawan per department
            $table->index(['company_id', 'department_id'], 'idx_employees_company_department');

            // Untuk filter karyawan baru berdasarkan hire_date
            $table->index(['company_id', 'hire_date'], 'idx_employees_hire_date');

            // Untuk filter kontrak yang akan expire
            $table->index(['company_id', 'employment_status', 'contract_end_date'], 'idx_employees_contract_end');
        });

        // Attendances table indexes
        Schema::table('attendances', function (Blueprint $table) {
            // Untuk filter attendance per tanggal
            $table->index(['company_id', 'date'], 'idx_attendances_company_date');

            // Untuk filter attendance per employee per tanggal
            $table->index(['company_id', 'employee_id', 'date'], 'idx_attendances_company_employee_date');

            // Untuk aggregasi status attendance
            $table->index(['company_id', 'date', 'clock_in_status'], 'idx_attendances_status');
        });

        // Leave requests table indexes
        Schema::table('leave_requests', function (Blueprint $table) {
            // Untuk filter pending leaves
            $table->index(['company_id', 'status'], 'idx_leave_requests_company_status');

            // Untuk filter leaves by date range
            $table->index(['company_id', 'start_date', 'end_date'], 'idx_leave_requests_dates');
        });

        // Payrolls table indexes
        Schema::table('payrolls', function (Blueprint $table) {
            // Untuk filter payroll per periode
            $table->index(['company_id', 'period_year', 'period_month'], 'idx_payrolls_company_period');
        });

        // Payroll items table indexes
        Schema::table('payroll_items', function (Blueprint $table) {
            // Untuk join dengan payrolls
            $table->index('payroll_id', 'idx_payroll_items_payroll');
        });

        // Overtime requests table indexes
        Schema::table('overtime_requests', function (Blueprint $table) {
            // Untuk filter overtime per status
            $table->index(['company_id', 'status'], 'idx_overtime_requests_company_status');

            // Untuk filter overtime per tanggal
            $table->index(['company_id', 'date'], 'idx_overtime_requests_company_date');
        });

        // Reimbursements table indexes
        Schema::table('reimbursements', function (Blueprint $table) {
            // Untuk filter reimbursement per status
            $table->index(['company_id', 'status'], 'idx_reimbursements_company_status');
        });

        // Departments table indexes
        Schema::table('departments', function (Blueprint $table) {
            // Untuk filter department aktif
            $table->index(['company_id', 'is_active'], 'idx_departments_company_active');
        });

        // Positions table indexes
        Schema::table('positions', function (Blueprint $table) {
            // Untuk filter position aktif
            $table->index(['company_id', 'is_active'], 'idx_positions_company_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop employees indexes
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('idx_employees_company_active');
            $table->dropIndex('idx_employees_company_department');
            $table->dropIndex('idx_employees_hire_date');
            $table->dropIndex('idx_employees_contract_end');
        });

        // Drop attendances indexes
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex('idx_attendances_company_date');
            $table->dropIndex('idx_attendances_company_employee_date');
            $table->dropIndex('idx_attendances_status');
        });

        // Drop leave_requests indexes
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('idx_leave_requests_company_status');
            $table->dropIndex('idx_leave_requests_dates');
        });

        // Drop payrolls indexes
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('idx_payrolls_company_period');
        });

        // Drop payroll_items indexes
        Schema::table('payroll_items', function (Blueprint $table) {
            $table->dropIndex('idx_payroll_items_payroll');
        });

        // Drop overtime_requests indexes
        Schema::table('overtime_requests', function (Blueprint $table) {
            $table->dropIndex('idx_overtime_requests_company_status');
            $table->dropIndex('idx_overtime_requests_company_date');
        });

        // Drop reimbursements indexes
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropIndex('idx_reimbursements_company_status');
        });

        // Drop departments indexes
        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('idx_departments_company_active');
        });

        // Drop positions indexes
        Schema::table('positions', function (Blueprint $table) {
            $table->dropIndex('idx_positions_company_active');
        });
    }
};
