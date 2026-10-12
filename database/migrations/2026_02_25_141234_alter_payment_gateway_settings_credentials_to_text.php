<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change credentials column from JSON to TEXT to support encrypted data
        // Laravel's encrypted:array cast produces base64 strings, not valid JSON
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            // SQLite doesn't support MODIFY, but also doesn't have JSON CHECK constraints
            // So we don't need to do anything for SQLite
            return;
        }

        DB::statement('ALTER TABLE payment_gateway_settings MODIFY credentials LONGTEXT NULL');
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        // Note: Reverting this would require decrypting and re-encrypting data
        // This is a one-way migration for encrypted credentials support
        DB::statement('ALTER TABLE payment_gateway_settings MODIFY credentials LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(`credentials`))');
    }
};
