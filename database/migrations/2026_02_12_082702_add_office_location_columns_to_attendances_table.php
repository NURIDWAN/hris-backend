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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('office_location_id')
                ->nullable()
                ->after('employee_id')
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('clock_out_office_location_id')
                ->nullable()
                ->after('clock_out_longitude')
                ->constrained('office_locations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['office_location_id']);
            $table->dropColumn('office_location_id');
            $table->dropForeign(['clock_out_office_location_id']);
            $table->dropColumn('clock_out_office_location_id');
        });
    }
};
