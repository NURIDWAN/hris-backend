<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BPJS Ketenagakerjaan Settings
        Schema::create('bpjs_tk_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // JHT (Jaminan Hari Tua)
            $table->decimal('jht_company_rate', 5, 2)->default(3.70); // % ditanggung perusahaan
            $table->decimal('jht_employee_rate', 5, 2)->default(2.00); // % ditanggung karyawan
            $table->boolean('jht_enabled')->default(true);

            // JKK (Jaminan Kecelakaan Kerja) - berdasarkan risiko
            $table->decimal('jkk_rate', 5, 2)->default(0.24); // 0.24% - 1.74% tergantung risiko
            $table->enum('jkk_risk_level', ['very_low', 'low', 'medium', 'high', 'very_high'])->default('very_low');
            $table->boolean('jkk_enabled')->default(true);

            // JKM (Jaminan Kematian)
            $table->decimal('jkm_rate', 5, 2)->default(0.30); // % ditanggung perusahaan
            $table->boolean('jkm_enabled')->default(true);

            // JP (Jaminan Pensiun)
            $table->decimal('jp_company_rate', 5, 2)->default(2.00); // % ditanggung perusahaan
            $table->decimal('jp_employee_rate', 5, 2)->default(1.00); // % ditanggung karyawan
            $table->decimal('jp_max_salary', 15, 2)->default(10042300); // Batas maksimum upah untuk JP (2024)
            $table->boolean('jp_enabled')->default(true);

            // Batas maksimum upah
            $table->decimal('max_salary_basis', 15, 2)->nullable(); // Batas upah untuk perhitungan

            $table->integer('effective_year')->default(2024);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id']);
        });

        // BPJS Kesehatan Settings
        Schema::create('bpjs_kes_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            // Iuran BPJS Kesehatan
            $table->decimal('company_rate', 5, 2)->default(4.00); // % ditanggung perusahaan
            $table->decimal('employee_rate', 5, 2)->default(1.00); // % ditanggung karyawan

            // Batas upah
            $table->decimal('min_salary_basis', 15, 2)->default(2900000); // UMK minimum (2024)
            $table->decimal('max_salary_basis', 15, 2)->default(12000000); // Batas maksimum upah

            // Tambahan anggota keluarga
            $table->decimal('additional_family_rate', 5, 2)->default(1.00); // % per anggota tambahan
            $table->integer('max_additional_family')->default(5); // Maksimum anggota tambahan

            $table->integer('effective_year')->default(2024);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id']);
        });

        // Tabel untuk JKK rates berdasarkan tingkat risiko
        Schema::create('jkk_risk_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('risk_level', ['very_low', 'low', 'medium', 'high', 'very_high']);
            $table->string('description');
            $table->decimal('rate', 5, 2);
            $table->integer('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'risk_level', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jkk_risk_rates');
        Schema::dropIfExists('bpjs_kes_settings');
        Schema::dropIfExists('bpjs_tk_settings');
    }
};
