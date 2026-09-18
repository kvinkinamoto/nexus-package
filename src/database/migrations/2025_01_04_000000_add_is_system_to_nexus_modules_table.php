<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Module::$fillable/$casts already declared is_system, and ModuleManager::
 * uninstall() already reads it to block uninstalling a system module — but
 * the column itself was never migrated, so that check threw
 * MissingAttributeException (Model::shouldBeStrict() is on outside
 * production) the moment uninstall() actually ran, rather than doing what
 * it was written to do.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nexus_modules', function (Blueprint $table) {
            $table->boolean('is_system')->default(false)->after('is_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('nexus_modules', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};
