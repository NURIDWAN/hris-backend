<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration untuk SPT Tahunan PPh 21 (Formulir 1721)
 *
 * Struktur:
 * 1. spt_1721 - SPT Induk (header SPT tahunan)
 * 2. spt_1721_monthly - Lampiran 1721-I (data bulanan)
 * 3. spt_1721_employees - Lampiran 1721-II (daftar karyawan)
 */
return new class extends Migration
{
    public function up(): void
    {
        // SPT Induk 1721 - Header SPT Tahunan
        Schema::create('spt_1721', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->year('tax_year');
            $table->enum('spt_type', ['normal', 'pembetulan'])->default('normal');
            $table->unsignedTinyInteger('pembetulan_ke')->default(0);

            // Data Pemotong Pajak (dari Company)
            $table->string('company_npwp', 20);
            $table->string('company_name');
            $table->text('company_address')->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_email')->nullable();
            $table->string('klu_code', 10)->nullable(); // Klasifikasi Lapangan Usaha
            $table->string('kpp_code', 10)->nullable(); // Kantor Pelayanan Pajak

            // Penandatangan
            $table->string('signer_name');
            $table->string('signer_npwp', 20)->nullable();
            $table->string('signer_position')->nullable();
            $table->date('sign_date')->nullable();
            $table->string('sign_place')->nullable();

            // Summary Tahunan - Bagian A (PPh 21 Final)
            $table->decimal('pph21_final_bruto', 18, 2)->default(0);
            $table->decimal('pph21_final_pph', 18, 2)->default(0);

            // Summary Tahunan - Bagian B (PPh 21 Tidak Final)
            $table->integer('total_employees')->default(0);
            $table->decimal('total_bruto', 18, 2)->default(0);
            $table->decimal('total_pph21_terutang', 18, 2)->default(0);
            $table->decimal('total_pph21_disetor', 18, 2)->default(0);
            $table->decimal('total_pph21_kurang_lebih', 18, 2)->default(0);

            // Status SPT
            $table->enum('status', ['draft', 'calculated', 'submitted', 'reported'])->default('draft');
            $table->string('ntpn')->nullable(); // Nomor Transaksi Penerimaan Negara
            $table->date('submission_date')->nullable();
            $table->string('submission_receipt')->nullable(); // Bukti Penerimaan Elektronik

            // Meta
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint
            $table->unique(['company_id', 'tax_year', 'spt_type', 'pembetulan_ke'], 'spt_1721_unique');
        });

        // Lampiran 1721-I - Data Bulanan PPh 21
        Schema::create('spt_1721_monthly', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spt_1721_id')->constrained('spt_1721')->onDelete('cascade');
            $table->unsignedTinyInteger('month'); // 1-12

            // Jumlah Penerima Penghasilan
            $table->integer('employee_count')->default(0);
            $table->integer('employee_count_npwp')->default(0); // Yang punya NPWP
            $table->integer('employee_count_non_npwp')->default(0);

            // Penghasilan Bruto
            $table->decimal('bruto_pegawai_tetap', 18, 2)->default(0);
            $table->decimal('bruto_pegawai_tidak_tetap', 18, 2)->default(0);
            $table->decimal('bruto_bukan_pegawai', 18, 2)->default(0);
            $table->decimal('bruto_pesangon', 18, 2)->default(0);
            $table->decimal('bruto_honorarium', 18, 2)->default(0);
            $table->decimal('bruto_lainnya', 18, 2)->default(0);
            $table->decimal('total_bruto', 18, 2)->default(0);

            // PPh 21 Terutang
            $table->decimal('pph21_pegawai_tetap', 18, 2)->default(0);
            $table->decimal('pph21_pegawai_tidak_tetap', 18, 2)->default(0);
            $table->decimal('pph21_bukan_pegawai', 18, 2)->default(0);
            $table->decimal('pph21_pesangon', 18, 2)->default(0);
            $table->decimal('pph21_honorarium', 18, 2)->default(0);
            $table->decimal('pph21_lainnya', 18, 2)->default(0);
            $table->decimal('total_pph21', 18, 2)->default(0);

            // Penyetoran
            $table->decimal('pph21_disetor', 18, 2)->default(0);
            $table->string('ssp_ntpn', 50)->nullable();
            $table->date('ssp_date')->nullable();

            // Selisih
            $table->decimal('pph21_kurang_lebih', 18, 2)->default(0);

            $table->timestamps();

            $table->unique(['spt_1721_id', 'month']);
        });

        // Lampiran 1721-II - Daftar Bukti Potong (referensi ke 1721-A1)
        Schema::create('spt_1721_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spt_1721_id')->constrained('spt_1721')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');
            $table->foreignId('tax_form_1721a1_id')->nullable()->constrained('tax_form_1721a1')->nullOnDelete();

            // Data Karyawan (snapshot)
            $table->string('employee_npwp', 20)->nullable();
            $table->string('employee_nik', 20)->nullable();
            $table->string('employee_name');
            $table->string('ptkp_status', 10);
            $table->unsignedTinyInteger('work_start_month')->default(1);
            $table->unsignedTinyInteger('work_end_month')->default(12);

            // Penghasilan & Pajak
            $table->decimal('gross_salary', 18, 2)->default(0);
            $table->decimal('neto_salary', 18, 2)->default(0);
            $table->decimal('ptkp', 18, 2)->default(0);
            $table->decimal('pkp', 18, 2)->default(0);
            $table->decimal('pph21_terutang', 18, 2)->default(0);
            $table->decimal('pph21_ditanggung_pemerintah', 18, 2)->default(0); // DTP jika ada
            $table->decimal('pph21_telah_dipotong', 18, 2)->default(0);
            $table->decimal('pph21_kurang_lebih', 18, 2)->default(0);

            // Status Karyawan
            $table->enum('employee_type', ['tetap', 'tidak_tetap', 'keluar'])->default('tetap');
            $table->date('exit_date')->nullable();
            $table->string('exit_reason')->nullable();

            $table->timestamps();

            $table->unique(['spt_1721_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spt_1721_employees');
        Schema::dropIfExists('spt_1721_monthly');
        Schema::dropIfExists('spt_1721');
    }
};
