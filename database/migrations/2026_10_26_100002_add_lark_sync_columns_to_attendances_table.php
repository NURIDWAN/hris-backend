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
            $table->string('lark_record_id')->nullable()->after('admin_notes')->index();
            $table->timestamp('lark_synced_at')->nullable()->after('lark_record_id');
            $table->text('lark_sync_error')->nullable()->after('lark_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['lark_record_id', 'lark_synced_at', 'lark_sync_error']);
        });
    }
};
