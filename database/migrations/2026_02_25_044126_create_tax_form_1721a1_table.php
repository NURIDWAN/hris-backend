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
        Schema::create('tax_form_1721a1', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('tax_year');
            $table->string('form_number')->nullable();

            // Data Pemotong Pajak (Company)
            $table->string('company_npwp')->nullable();
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();

            // Data Penerima Penghasilan (Employee)
            $table->string('employee_npwp')->nullable();
            $table->string('employee_nik')->nullable();
            $table->string('employee_name')->nullable();
            $table->text('employee_address')->nullable();
            $table->string('ptkp_status')->nullable(); // TK/0, K/1, etc

            // Masa Perolehan Penghasilan
            $table->integer('work_start_month')->default(1);
            $table->integer('work_end_month')->default(12);

            // Penghasilan Bruto
            $table->decimal('gaji_pokok', 15, 2)->default(0); // Gaji/Pensiun/THT/JHT
            $table->decimal('tunjangan_pph', 15, 2)->default(0); // Tunjangan PPh
            $table->decimal('tunjangan_lainnya', 15, 2)->default(0); // Tunjangan lainnya, uang lembur, dsb
            $table->decimal('honorarium', 15, 2)->default(0); // Honorarium dan imbalan lain
            $table->decimal('premi_asuransi', 15, 2)->default(0); // Premi asuransi yang dibayar pemberi kerja
            $table->decimal('natura', 15, 2)->default(0); // Penerimaan dalam bentuk natura
            $table->decimal('tantiem_bonus_thr', 15, 2)->default(0); // Tantiem, Bonus, Gratifikasi, THR
            $table->decimal('gross_salary', 15, 2)->default(0); // Jumlah Penghasilan Bruto

            // Pengurang
            $table->decimal('biaya_jabatan', 15, 2)->default(0); // 5% dari bruto, max 6 juta/tahun
            $table->decimal('iuran_pensiun', 15, 2)->default(0); // Iuran pensiun/JHT yang dibayar sendiri
            $table->decimal('total_pengurang', 15, 2)->default(0);

            // Penghasilan Neto
            $table->decimal('neto_salary', 15, 2)->default(0);

            // PTKP
            $table->decimal('ptkp', 15, 2)->default(0);

            // PKP (Penghasilan Kena Pajak)
            $table->decimal('pkp', 15, 2)->default(0);

            // PPh 21
            $table->decimal('pph21_terutang', 15, 2)->default(0); // PPh 21 Terutang
            $table->decimal('pph21_dipotong_sebelumnya', 15, 2)->default(0); // PPh 21 yang sudah dipotong
            $table->decimal('total_pph21_withheld', 15, 2)->default(0); // Total PPh 21 yang dipotong

            // Timestamps
            $table->date('generated_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Unique constraint per employee per year per company
            $table->unique(['company_id', 'employee_id', 'tax_year'], 'unique_tax_form_1721a1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_form_1721a1');
    }
};
