<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Collection;
use Nodex\Nexus\Models\Module;
use Nodex\Nexus\Models\ModuleSetting;
use Nodex\Nexus\Services\Interfaces\SettingsProviderInterface;

/**
 * The concrete SettingsProviderInterface backing SettingsBuilder — was
 * declared and bound nowhere until now, so every #[Setting(...)] on any
 * module was dead metadata (AttributeSchemaReader read it into the config
 * DTO, but SettingsBuilder::get() had no provider to fall through to and
 * always returned the caller's own $default; ::set() silently no-op'd).
 *
 * Stores one row per (module, key) in nexus_module_settings rather than a
 * column per setting — see that migration's docblock. Fails soft (returns
 * $default / no-ops) when the named module isn't installed yet, matching
 * ModuleRegistry::getEnabledModules()'s own fail-open precedent rather than
 * throwing from what's meant to be a passive read/write helper.
 */
class DatabaseSettingsProvider implements SettingsProviderInterface
{
    public function get(string $module, string $key, mixed $default = null): mixed
    {
        $moduleRow = Module::findByName($module);

        if (! $moduleRow) {
            return $default;
        }

        $setting = ModuleSetting::query()
            ->where('module_id', $moduleRow->id)
            ->where('key', $key)
            ->first();

        return $setting?->value ?? $default;
    }

    public function set(string $module, string $key, mixed $value): void
    {
        $moduleRow = Module::findByName($module);

        if (! $moduleRow) {
            return;
        }

        ModuleSetting::query()->updateOrCreate(
            ['module_id' => $moduleRow->id, 'key' => $key],
            ['value' => $value]
        );
    }

    public function getAll(string $module): Collection
    {
        $moduleRow = Module::findByName($module);

        if (! $moduleRow) {
            return collect();
        }

        return ModuleSetting::query()
            ->where('module_id', $moduleRow->id)
            ->pluck('value', 'key');
    }
}
