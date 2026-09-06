<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * nexus_module_settings (the table backing Nodex\Nexus\Services\
 * DatabaseSettingsProvider — see project-nexus-settings-registry memory)
 * turned out to already be scaffolded by 2025_01_01_000000_create_nexus_
 * tables.php, but without the (module_id, key) unique index the provider's
 * updateOrCreate() calls assume. A first attempt at this feature wrongly
 * re-created the whole table in a new migration guarded by
 * Schema::hasTable() — since the table already existed, that guard made the
 * migration a permanent no-op and its intended unique index never applied.
 * Fixed the right way per this codebase's own convention (extend via an
 * additive migration, never edit the original create_*_table one — see
 * app/Nexus/Modules/Demo/database/migrations/ for the established pattern).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nexus_module_settings', function (Blueprint $table) {
            $table->unique(['module_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('nexus_module_settings', function (Blueprint $table) {
            $table->dropUnique(['module_id', 'key']);
        });
    }
};
