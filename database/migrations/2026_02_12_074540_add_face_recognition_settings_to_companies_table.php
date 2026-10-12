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
        Schema::table('companies', function (Blueprint $table) {
            $table->boolean('enable_face_recognition')->default(true)->after('is_active');
            $table->decimal('face_match_threshold', 3, 2)->default(0.60)->after('enable_face_recognition');
            $table->boolean('require_liveness_detection')->default(true)->after('face_match_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn([
                'enable_face_recognition',
                'face_match_threshold',
                'require_liveness_detection',
            ]);
        });
    }
};
