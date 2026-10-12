<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Enable face recognition for all existing companies
     */
    public function up(): void
    {
        DB::table('companies')->update([
            'enable_face_recognition' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to false (original default)
        DB::table('companies')->update([
            'enable_face_recognition' => false,
        ]);
    }
};
