<?php

namespace Nodex\Nexus\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Nodex\Nexus\Services\Interfaces\SettingsProviderInterface;

class SettingsBuilder
{
    /**
     * Get a setting value.
     * 
     * @param string $module
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $module, string $key, mixed $default = null): mixed
    {
        $cacheKey = "nexus_settings_{$module}_{$key}";

        return Cache::rememberForever($cacheKey, function () use ($module, $key, $default) {
            // 1. Check in Laravel config first (nexus::module.key)
            $configValue = Config::get("nexus::{$module}.{$key}");
            if ($configValue !== null) {
                return $configValue;
            }

            // 2. Check if there's a registered settings provider
            if (app()->bound(SettingsProviderInterface::class)) {
                $provider = app(SettingsProviderInterface::class);
                return $provider->get($module, $key, $default);
            }

            return $default;
        });
    }

    /**
     * Set a setting value.
     * 
     * @param string $module
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $module, string $key, mixed $value): void
    {
        if (app()->bound(SettingsProviderInterface::class)) {
            $provider = app(SettingsProviderInterface::class);
            $provider->set($module, $key, $value);

            static::forget($module, $key);
        }
    }

    /**
     * Clear settings cache.
     * 
     * @param string $module
     * @param string|null $key
     * @return void
     */
    public static function forget(string $module, ?string $key = null): void
    {
        if ($key) {
            Cache::forget("nexus_settings_{$module}_{$key}");
        } else {
            // Ideally we should use tags, but as a fallback/simple version:
            // This needs to be handled by the provider if it knows about all keys
        }
    }
}
