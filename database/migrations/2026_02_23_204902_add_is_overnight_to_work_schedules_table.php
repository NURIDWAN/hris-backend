<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->boolean('is_overnight')->default(false)->after('end_time');
        });

        // Auto-detect and update existing overnight shifts (where end_time < start_time)
        DB::statement('
            UPDATE work_schedules
            SET is_overnight = 1
            WHERE end_time < start_time
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('work_schedules', function (Blueprint $table) {
            $table->dropColumn('is_overnight');
        });
    }
};
