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
