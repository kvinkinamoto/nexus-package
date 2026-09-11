<?php

use Nodex\Nexus\Services\HookManager;
use Nodex\Nexus\Services\IconManager;

if (!function_exists('nexus_icon')) {

    function nexus_icon(?string $key, ?string $module = null, ?string $default = null): string
    {
        return app(IconManager::class)->getIcon($key, $module, $default);
    }
}

if (!function_exists('nexus_filter')) {

    /**
     * Run a named filter hook, transforming and returning $value.
     * See Nodex\Nexus\Services\HookManager for the full explanation.
     */
    function nexus_filter(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookManager::class)->filter($hook, $value, ...$args);
    }
}

if (!function_exists('nexus_action')) {

    /**
     * Run a named action hook (side effects, no return value).
     */
    function nexus_action(string $hook, mixed ...$args): void
    {
        app(HookManager::class)->action($hook, ...$args);
    }
}

if (!function_exists('nexus_trans_label')) {

    /**
     * Resolve the display text for a module's own column/field/section/
     * filter/setting label — every one of those blade partials builds a key
     * as {module}::translate.{lowercased label or field name} and hands it
     * straight to @lang(), which (Laravel's default) echoes the raw key
     * string back when the module's own lang file doesn't have that entry
     * yet (e.g. "shopCategory::translate.slug") instead of failing loudly.
     * Every module's DefaultModuleConfigurationDto ships the same built-in
     * columns/sections ("id", "information", "relations", "main"...), so
     * without a shared fallback every module's lang file would need the
     * exact same handful of entries duplicated into it. This tries the
     * module's own namespace first (so a module can still override/localize
     * any of these), then this package's own shared nexus::translate (see
     * its "id"/"information"/... entries), then finally falls back to the
     * raw human label itself — still readable, just not localized — rather
     * than the ugly namespaced key string.
     */
    function nexus_trans_label(string $moduleName, ?string $label, string $fallbackName): string
    {
        $label = $label ?: $fallbackName;

        if (str_contains($label, '::')) {
            return __($label);
        }

        $key = \Illuminate\Support\Str::lower($label);
        $moduleKey = \Illuminate\Support\Str::lcfirst($moduleName).'::translate.'.$key;

        if (\Illuminate\Support\Facades\Lang::has($moduleKey)) {
            return __($moduleKey);
        }

        $sharedKey = 'nexus::translate.'.$key;
        if (\Illuminate\Support\Facades\Lang::has($sharedKey)) {
            return __($sharedKey);
        }

        return $label;
    }
}
