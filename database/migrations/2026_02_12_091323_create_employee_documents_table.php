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
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('employee_id')->constrained()->onDelete('cascade');

            // Document type: ktp, npwp, kk, bpjs_kesehatan, bpjs_ketenagakerjaan, ijazah, sertifikat, kontrak_kerja, other
            $table->string('document_type', 50);
            $table->string('document_number')->nullable(); // Nomor dokumen (NIK, NPWP, dll)
            $table->string('document_name')->nullable(); // Nama/judul dokumen (untuk sertifikat, ijazah)

            // File info
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size'); // in bytes
            $table->string('mime_type', 100);

            // Dates
            $table->date('issue_date')->nullable(); // Tanggal terbit
            $table->date('expiry_date')->nullable(); // Tanggal kadaluarsa

            // Verification
            $table->boolean('is_verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();

            // Upload tracking
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();

            // Notes
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['company_id', 'employee_id']);
            $table->index(['employee_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
    }
};
