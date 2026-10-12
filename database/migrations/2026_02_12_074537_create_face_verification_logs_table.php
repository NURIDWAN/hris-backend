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
        Schema::create('face_verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('verification_type', ['clock_in', 'clock_out', 'enrollment']);
            $table->boolean('is_successful')->default(false);
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->boolean('liveness_passed')->nullable();
            $table->string('failure_reason')->nullable();
            $table->string('photo_path')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['employee_id', 'created_at']);
            $table->index('verification_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('face_verification_logs');
    }
};
