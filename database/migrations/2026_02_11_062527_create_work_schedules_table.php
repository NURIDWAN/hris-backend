<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Regular Office", "Shift Pagi", "Shift Malam"
            $table->string('code')->nullable();
            $table->time('start_time'); // e.g., 08:00
            $table->time('end_time'); // e.g., 17:00
            $table->time('break_start')->nullable(); // e.g., 12:00
            $table->time('break_end')->nullable(); // e.g., 13:00
            $table->unsignedInteger('break_duration')->default(60); // in minutes
            $table->unsignedInteger('working_hours')->default(8); // hours per day
            $table->json('working_days')->nullable(); // [1,2,3,4,5] = Mon-Fri
            $table->unsignedInteger('late_tolerance')->default(15); // minutes
            $table->unsignedInteger('early_leave_tolerance')->default(15); // minutes
            $table->boolean('is_flexible')->default(false);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_schedules');
    }
};
