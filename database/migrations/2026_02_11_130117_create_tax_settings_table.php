<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel untuk PTKP (Penghasilan Tidak Kena Pajak)
        Schema::create('ptkp_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('status_code'); // TK/0, TK/1, TK/2, TK/3, K/0, K/1, K/2, K/3, K/I/0, K/I/1, K/I/2, K/I/3
            $table->string('description'); // Tidak Kawin Tanpa Tanggungan, etc.
            $table->decimal('annual_amount', 15, 2); // PTKP per tahun
            $table->integer('year'); // Tahun berlaku
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'status_code', 'year']);
        });

        // Tabel untuk tarif PPh 21 progresif
        Schema::create('pph21_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_amount', 15, 2); // Batas bawah PKP
            $table->decimal('max_amount', 15, 2)->nullable(); // Batas atas PKP (null = tak terbatas)
            $table->decimal('rate', 5, 2); // Persentase tarif pajak
            $table->integer('year'); // Tahun berlaku
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'year']);
        });

        // Tabel untuk konfigurasi PPh 21 perusahaan
        Schema::create('pph21_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_gross_up')->default(false); // PPh ditanggung perusahaan
            $table->boolean('use_ter')->default(true); // Gunakan TER (Tarif Efektif Rata-rata)
            $table->decimal('npwp_discount_rate', 5, 2)->default(20); // Diskon 20% jika punya NPWP
            $table->integer('effective_year')->default(2024);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_id']);
        });

        // Tabel untuk TER (Tarif Efektif Rata-rata) PPh 21 - mulai 2024
        Schema::create('pph21_ter_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('category', ['A', 'B', 'C']); // Kategori TER
            $table->string('ptkp_status'); // TK/0, TK/1, K/0, dll
            $table->decimal('min_income', 15, 2); // Penghasilan bruto minimum
            $table->decimal('max_income', 15, 2)->nullable(); // Penghasilan bruto maksimum
            $table->decimal('rate', 5, 2); // Tarif efektif (%)
            $table->integer('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'category', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pph21_ter_rates');
        Schema::dropIfExists('pph21_settings');
        Schema::dropIfExists('pph21_rates');
        Schema::dropIfExists('ptkp_settings');
    }
};
