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
        Schema::create('lark_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('app_id');
            $table->text('app_secret');
            $table->boolean('is_active')->default(false);
            $table->boolean('push_clock_in')->default(true);
            $table->boolean('push_clock_out')->default(true);
            $table->boolean('enable_bot_notifications')->default(false);
            $table->string('bot_webhook_url')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lark_integrations');
    }
};
