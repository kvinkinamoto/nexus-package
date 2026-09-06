<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Backs Nodex\Nexus\Services\DatabaseSettingsProvider — the storage for any
 * module's #[Setting(...)]-declared configuration (see Attributes\Setting).
 * One row per (module, key) rather than a column per setting, so a new
 * setting is a one-line attribute on the module's class, never a migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nexus_module_settings')) {
            return;
        }

        Schema::create('nexus_module_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('nexus_modules')->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['module_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nexus_module_settings');
    }
};
