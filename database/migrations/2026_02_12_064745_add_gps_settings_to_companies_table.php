<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->decimal('office_latitude', 10, 8)->nullable()->after('settings');
            $table->decimal('office_longitude', 11, 8)->nullable()->after('office_latitude');
            $table->integer('office_radius')->nullable()->default(100)->after('office_longitude');
            $table->boolean('enable_gps_validation')->default(false)->after('office_radius');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['office_latitude', 'office_longitude', 'office_radius', 'enable_gps_validation']);
        });
    }
};
