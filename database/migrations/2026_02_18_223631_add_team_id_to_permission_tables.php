<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add team_id (company_id) column to permission tables for multi-tenant role isolation.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        // Add team_id to roles table
        if (! Schema::hasColumn($tableNames['roles'], $teamForeignKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('id');
                $table->index($teamForeignKey, 'roles_team_foreign_key_index');
            });

            // Update unique constraint
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->dropUnique(['name', 'guard_name']);
                $table->unique([$teamForeignKey, 'name', 'guard_name']);
            });
        }

        // Add team_id to model_has_roles table
        if (! Schema::hasColumn($tableNames['model_has_roles'], $teamForeignKey)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('role_id');
                $table->index($teamForeignKey, 'model_has_roles_team_foreign_key_index');
            });
        }

        // Add team_id to model_has_permissions table
        if (! Schema::hasColumn($tableNames['model_has_permissions'], $teamForeignKey)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
                $table->unsignedBigInteger($teamForeignKey)->nullable()->after('permission_id');
                $table->index($teamForeignKey, 'model_has_permissions_team_foreign_key_index');
            });
        }

        // Clear permission cache
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $teamForeignKey = $columnNames['team_foreign_key'] ?? 'team_id';

        // Remove from model_has_permissions
        if (Schema::hasColumn($tableNames['model_has_permissions'], $teamForeignKey)) {
            Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($teamForeignKey) {
                $table->dropIndex('model_has_permissions_team_foreign_key_index');
                $table->dropColumn($teamForeignKey);
            });
        }

        // Remove from model_has_roles
        if (Schema::hasColumn($tableNames['model_has_roles'], $teamForeignKey)) {
            Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->dropIndex('model_has_roles_team_foreign_key_index');
                $table->dropColumn($teamForeignKey);
            });
        }

        // Remove from roles
        if (Schema::hasColumn($tableNames['roles'], $teamForeignKey)) {
            Schema::table($tableNames['roles'], function (Blueprint $table) use ($teamForeignKey) {
                $table->dropUnique([$teamForeignKey, 'name', 'guard_name']);
                $table->unique(['name', 'guard_name']);
                $table->dropIndex('roles_team_foreign_key_index');
                $table->dropColumn($teamForeignKey);
            });
        }
    }
};
