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
        Schema::table('lark_integrations', function (Blueprint $table) {
            $table->string('verification_token', 100)->nullable()->after('bot_webhook_url');
            $table->text('encrypt_key')->nullable()->after('verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lark_integrations', function (Blueprint $table) {
            $table->dropColumn(['verification_token', 'encrypt_key']);
        });
    }
};
